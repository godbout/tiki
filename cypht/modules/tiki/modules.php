<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki modules
 * @package modules
 * @subpackage tiki
 */

if (!defined('DEBUG_MODE')) { die(); }

/**
 * Load IMAP servers for message list page
 * @subpackage tiki/handler
 */
class Hm_Handler_load_data_sources extends Hm_Handler_Module {
    /**
     * Used by groupmail view
     */
    public function process() {
        $callback = 'tiki_groupmail_content';
        // TODO: check IMAP dependency and POP3 support
        foreach (imap_data_sources($callback, $this->user_config->get('custom_imap_sources', array())) as $vals) {
            $this->append('data_sources', $vals);
        }
    }
}

/**
 * Fetch messages for the Groupmail page
 * @subpackage tiki/handler
 */
class Hm_Handler_groupmail_fetch_messages extends Hm_Handler_Module {
    /**
     * Returns all messages for an IMAP server
     */
    public function process() {
        list($success, $form) = $this->process_form(array('imap_server_ids'));
        if ($success) {
            $limit = $this->user_config->get('all_email_per_source_setting', DEFAULT_PER_SOURCE);
            $ids = explode(',', $form['imap_server_ids']);
            $folder = bin2hex('INBOX');
            if (array_key_exists('folder', $this->request->post)) {
                $folder = $this->request->post['folder'];
            }
            list($status, $msg_list) = merge_imap_search_results($ids, 'ALL', $this->session, $this->config, array(hex2bin($folder)), $limit);
            $this->out('folder_status', $status);
            $this->out('groupmail_inbox_data', $msg_list);
            $this->out('imap_server_ids', $form['imap_server_ids']);
        }
    }
}

/**
 * Output the Tiki Groupmail section of the menu
 * @subpackage tiki/output
 */
class Hm_Output_groupmail_page_link extends Hm_Output_Module {
    /**
     * Displays the menu link
     */
    protected function output() {
        $res = '<li class="menu_groupmail"><a class="unread_link" href="?page=groupmail">';
        if (!$this->get('hide_folder_icons')) {
            $res .= '<img class="account_icon" src="'.$this->html_safe(Hm_Image_Sources::$people).'" alt="" width="16" height="16" /> ';
        }
        $res .= $this->trans('Groupmail').'</a></li>';
        if ($this->format == 'HTML5') {
            return $res;
        }
        $this->concat('formatted_folder_list', $res);
    }
}

/**
 * Output the heading for the groupmail page
 * @subpackage tiki/output
 */
class Hm_Output_groupmail_heading extends Hm_Output_Module {
    /**
     * Title and message controls
     */
    protected function output() {
        $source_link = '<a href="#" title="'.$this->trans('Sources').'" class="source_link"><img alt="Sources" class="refresh_list" src="'.Hm_Image_Sources::$folder.'" width="20" height="20" /></a>';
        $refresh_link = '<a class="refresh_link" title="'.$this->trans('Refresh').'" href="#"><img alt="Refresh" class="refresh_list" src="'.Hm_Image_Sources::$refresh.'" width="20" height="20" /></a>';

        $res = '';
        $res .= '<div class="groupmail"><div class="content_title">';
        $res .= '<div class="mailbox_list_title">'.$this->trans('Groupmail').'</div>';
        $res .= '<div class="list_controls">'.$refresh_link.$source_link.'</div>';
        $res .= list_sources($this->get('data_sources', array()), $this);
        $res .= '</div>';
        return $res;
    }
}

/**
 * Start the table for the groupmail page
 * @subpackage tiki/output
 */
class Hm_Output_groupmail_start extends Hm_Output_Module {
    /**
     * Uses the message_list_fields input to determine the format.
     */
    protected function output() {
        $res = '<table class="message_table">';
        $res .= '<colgroup>
            <col class="source_col">
            <col class="from_col">
            <col class="subject_col">
            <col class="date_col">
            <col class="icon_col">
            <col class="action_col">
        </colgroup>';
        $res .= '<thead><tr>
            <th class="source">Source</th>
            <th class="from">From</th>
            <th class="subject">Subject</th>
            <th class="msg_date">Date</th>
            <th></th>
            <th></th>
        </tr></thead>';
        $res .= '<tbody class="message_table_body">';
        return $res;
    }
}

/**
 * End the groupmail table
 * @subpackage tiki/output
 */
class Hm_Output_groupmail_end extends Hm_Output_Module {
    /**
     * Close the table opened in Hm_Output_groupmail_start
     */
    protected function output() {
        $res = '</tbody></table><div class="page_links"></div></div>';
        return $res;
    }
}

/**
 * Format message headers for the Groupmail page
 * @subpackage tiki/output
 */
class Hm_Output_filter_groupmail_data extends Hm_Output_Module {
    /**
     * Build ajax response for the Groupmail message list
     */
    protected function output() {
        global $user;
        $trklib = TikiLib::lib('trk');
        $contactlib = TikiLib::lib('contact');
        if ($msg_list = $this->get('groupmail_inbox_data')) {
            $res = array();
            if ($msg_list === array(false)) {
                return $msg_list;
            }
            $show_icons = $this->get('msg_list_icons');
            $list_page = $this->get('list_page', 0);
            $list_sort = $this->get('list_sort');
            $list_filter = $this->get('list_filter');
            foreach($msg_list as $msg) {
                $row_class = 'email';
                $icon = 'env_open';
                $parent_value = sprintf('imap_%d_%s', $msg['server_id'], $msg['folder']);
                $id = sprintf("imap_%s_%s_%s", $msg['server_id'], $msg['uid'], $msg['folder']);
                if (!trim($msg['subject'])) {
                    $msg['subject'] = '[No Subject]';
                }
                $subject = $msg['subject'];
                $from = format_imap_from_fld($msg['from']);
                $nofrom = '';
                if (!trim($from)) {
                    $from = '[No From]';
                    $nofrom = ' nofrom';
                }
                $timestamp = strtotime($msg['internal_date']);
                $date = translate_time_str(human_readable_interval($msg['internal_date']), $this);
                $flags = array();
                if (!stristr($msg['flags'], 'seen')) {
                    $flags[] = 'unseen';
                    $row_class .= ' unseen';
                    if ($icon != 'sent') {
                        $icon = 'env_closed';
                    }
                }
                if (trim($msg['x_auto_bcc']) === 'cypht') {
                    $from = preg_replace("/(\<.+\>)/U", '', $msg['to']);
                    $icon = 'sent';
                }
                foreach (array('attachment', 'deleted', 'flagged', 'answered') as $flag) {
                    if (stristr($msg['flags'], $flag)) {
                        $flags[] = $flag;
                    }
                }
                $source = $msg['server_name'];
                $row_class .= ' '.str_replace(' ', '_', $source);
                if ($msg['folder'] && hex2bin($msg['folder']) != 'INBOX') {
                    $source .= '-'.preg_replace("/^INBOX.{1}/", '', hex2bin($msg['folder']));
                }
                $url = '?page=message&uid='.$msg['uid'].'&list_path='.sprintf('imap_%d_%s', $msg['server_id'], $msg['folder']).'&list_parent='.$parent_value;
                if ($list_page) {
                    $url .= '&list_page='.$this->html_safe($list_page);
                }
                if ($list_sort) {
                    $url .= '&sort='.$this->html_safe($list_sort);
                }
                if ($list_filter) {
                    $url .= '&filter='.$this->html_safe($list_filter);
                }
                if (!$show_icons) {
                    $icon = false;
                }
                // handle take/taken operator here
                $itemid = $trklib->get_item_id($this->session['trackerId'], $this->session['messageFId'], $id);
                if ($itemid > 0) {
                    $operator = $trklib->get_item_value($this->session['trackerId'], $itemid, $this->session['operatorFId']);
                } else {
                    $operator = '';
                }
                // check if sender is in contacts
                $from_email = '';
                foreach (process_address_fld($msg['from']) as $vals) {
                    if (trim($vals['email'])) {
                        $from_email = $vals['email'];
                    }
                }
                $contactId = $contactlib->get_contactId_email($from_email, $user);
                // check if there's a wiki page
                $ext = $contactlib->get_ext_by_name($user, tra('Wiki Page'), $contactId);
                if ($ext) {
                    $wikiPage = $contactlib->get_contact_ext_val($user, $contactId, $ext['fieldId']);
                } else {
                    $wikiPage = '';
                }
                $res[$id] = message_list_row(array(
                        array('safe_output_callback', 'source', $source, $icon),
                        array('sender_callback', 'from'.$nofrom, $from, $operator, $contactId, $wikiPage),
                        array('subject_callback', $subject, $url, $flags),
                        array('date_callback', $date, $timestamp),
                        array('icon_callback', $flags),
                        array('take_callback', $id)
                    ),
                    $id,
                    'email',
                    $this,
                    $row_class
                );
            }
            $this->out('formatted_message_list', $res);
        }
        elseif (!$this->get('formatted_message_list')) {
            $this->out('formatted_message_list', array());
        }
    }
}

/**
 * Callback for TAKE button in groupmail list page
 * @subpackage tiki/functions
 * @param array $vals data for the cell
 * @param string $style message list style
 * @param object $output_mod Hm_Output_Module
 * @return string
 */
if (!hm_exists('take_callback')) {
function take_callback($vals, $style, $output_mod) {
    $button = sprintf(
        '<a class="btn btn-outline-secondary btn-sm tips mod_webmail_action" title="%s" onclick="doTakeWebmail(\'%s\'); return false;" href="#">%s</a>',
        tr('Take this email'),
        $vals[0],
        tr('TAKE')
    );
    return sprintf('<td class="action">%s</td>', $button);
}}

/**
 * Callback for FROM column in groupmail list page
 * @subpackage tiki/functions
 * @param array $vals data for the cell
 * @param string $style message list style
 * @param object $output_mod Hm_Output_Module
 * @return string
 */
if (!hm_exists('sender_callback')) {
function sender_callback($vals, $style, $output_mod) {
    global $smarty, $tikiroot;
    $smarty->loadPlugin('smarty_block_self_link');
    $smarty->loadPlugin('smarty_modifier_sefurl');
    list($class, $from, $operator, $contactId, $wikiPage) = $vals;
    if ($contactId > 0) {
        if (! empty($wikiPage)) {
            $output = smarty_block_self_link([
                '_script' => $tikiroot.smarty_modifier_sefurl($wikiPage),
                '_class' => "mod_webmail_from"
            ], $from, $smarty);
        } else {
            $output = smarty_block_self_link([
                '_script' => $tikiroot.'tiki-contacts.php',
                'contactId' => $contactId,
                '_class' => "mod_webmail_from"
            ], $from, $smarty);
        }
        $output .= '<div style="float: right;">'.
            smarty_block_self_link([
                '_script' => $tikiroot.'tiki-contacts.php',
                'contactId' => $contactId,
                '_icon_name' => 'user',
                '_width' => 12,
                '_height' => 12
            ], tr('View contact'), $smarty)
            .'</div>';
    } else {
        $output = '<span class="mod_webmail_from">'.$from.'</span>';
    }
    return sprintf('<td class="%s" title="%s">%s</td>', $output_mod->html_safe($class), $output_mod->html_safe($from), $output);
}}

