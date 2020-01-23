<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki groupmail modules
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
 * Check whether Groupmail is enabled or not
 * @subpackage tiki/handler
 */
class Hm_Handler_check_groupmail_setting extends Hm_Handler_Module {
    /**
     * Sets flag based on session
     */
    public function process() {
        $this->out('groupmail_enabled', $this->session->get('groupmail') == 'y');
    }
}

/**
 * Prepare Groupmail session settings for output modules
 * @subpackage tiki/handler
 */
class Hm_Handler_prepare_groupmail_settings extends Hm_Handler_Module {
    /**
     * Sets settings based on session
     */
    public function process() {
        foreach(['group', 'trackerId', 'fromFId', 'subjectFId', 'messageFId', 'contentFId', 'accountFId', 'datetimeFId', 'operatorFId'] as $field) {
            $this->out($field, $this->session->get($field));
        }
    }
}

/**
 * Take a groupmail message
 * @subpackage tiki/handler
 */
class Hm_Handler_take_groupmail extends Hm_Handler_Module {
    /**
     * Take a message
     */
    public function process() {
        list($success, $form) = $this->process_form(array('msgid', 'imap_msg_uid', 'imap_server_id', 'folder'));
        if (! $success) {
            return;
        }

        $cache = Hm_IMAP_List::get_cache($this->cache, $form['imap_server_id']);
        $imap = Hm_IMAP_List::connect($form['imap_server_id'], $cache);
        if (! imap_authed($imap)) {
            return;
        }

        $imap->read_only = $prefetch;
        if (! $imap->select_mailbox(hex2bin($form['folder']))) {
            return;
        }

        $msg_struct = $imap->get_message_structure($form['imap_msg_uid']);
        if (!$this->user_config->get('text_only_setting', false)) {
            list($part, $msg_text) = $imap->get_first_message_part($form['imap_msg_uid'], 'text', 'html', $msg_struct);
            if (!$part) {
                list($part, $msg_text) = $imap->get_first_message_part($form['imap_msg_uid'], 'text', false, $msg_struct);
            }
        }
        else {
            list($part, $msg_text) = $imap->get_first_message_part($form['imap_msg_uid'], 'text', false, $msg_struct);
        }

        $struct = $imap->search_bodystructure( $msg_struct, array('imap_part_number' => $part));
        $msg_struct_current = array_shift($struct);
        if (!trim($msg_text)) {
            if (is_array($msg_struct_current) && array_key_exists('subtype', $msg_struct_current)) {
                if ($msg_struct_current['subtype'] == 'plain') {
                    $subtype = 'html';
                }
                else {
                    $subtype = 'plain';
                }
                list($part, $msg_text) = $imap->get_first_message_part($form['imap_msg_uid'], 'text', $subtype, $msg_struct);
                $struct = $imap->search_bodystructure($msg_struct, array('imap_part_number' => $part));
                $msg_struct_current = array_shift($struct);
            }
        }
        if (isset($msg_struct_current['subtype']) && strtolower($msg_struct_current['subtype'] == 'html')) {
            $msg_text = add_attached_images($msg_text, $form['imap_msg_uid'], $msg_struct, $imap);
        }
        $msg_headers = $imap->get_message_headers($form['imap_msg_uid']);

        global $prefs, $user;

        $contactlib = TikiLib::lib('contact');
        $categlib = TikiLib::lib('categ');
        $tikilib = TikiLib::lib('tiki');
        $trklib = TikiLib::lib('trk');

        // make tracker item
        $from       = $msg_headers['From'];
        $subject    = $msg_headers['Subject'];
        $realmsgid  = $form['msgid'];
        $maildate   = $msg_headers['Date'];
        $maildate   = strtotime($maildate);

        $parsed_from = preg_split('/[<>]/', $from, -1, PREG_SPLIT_NO_EMPTY);
        $sender = ['name' => $parsed_from[0], 'email' => $parsed_from[1]];

        // check if already taken
        $itemid = $trklib->get_item_id($this->get('trackerId'), $this->get('messageFId'), $realmsgid);
        if ($itemid > 0) {
            Hm_Msgs::add('ERR'.tr('Sorry, that mail has been taken by another operator.'));
            return;
        } else {
            $charset = $prefs['default_mail_charset'];
            if (empty($charset)) {
                $charset = 'UTF-8';
            }

            $items['data'][0]['fieldId'] = $this->get('fromFId');
            $items['data'][0]['type'] = 't';
            $items['data'][0]['value'] = $from;
            $items['data'][1]['fieldId'] = $this->get('operatorFId');
            $items['data'][1]['type'] = 'u';
            $items['data'][1]['value'] = $user;
            $items['data'][2]['fieldId'] = $this->get('subjectFId');
            $items['data'][2]['type'] = 't';
            $items['data'][2]['value'] = $subject;
            $items['data'][3]['fieldId'] = $this->get('messageFId');
            $items['data'][3]['type'] = 't';
            $items['data'][3]['value'] = $realmsgid;
            $items['data'][4]['fieldId'] = $this->get('contentFId');
            $items['data'][4]['type'] = 'a';
            $items['data'][4]['value'] = htmlentities($msg_text, ENT_QUOTES, $charset);
            $items['data'][5]['fieldId'] = $this->get('accountFId');
            $items['data'][5]['type'] = 't';
            $items['data'][5]['value'] = $form['imap_server_id'];
            $items['data'][6]['fieldId'] = $this->get('datetimeFId');
            $items['data'][6]['type'] = 'f';    // f?
            $items['data'][6]['value'] = $maildate;
            $trklib->replace_item($this->get('trackerId'), 0, $items);
        }

        // make name for wiki page
        $pageName = str_replace('@', '_AT_', $sender['email']);
        $contId = $contactlib->get_contactId_email($sender['email'], $user);

        // add or update (?) contact
        $ext = $contactlib->get_ext_by_name($user, tra('Wiki Page'), $contId);
        if (! $ext) {
            $contactlib->add_ext($user, tra('Wiki Page'), true);    // a public field
            $ext = $contactlib->get_ext_by_name($user, tra('Wiki Page'), $contId);
        }

        $arr = explode(" ", trim(html_entity_decode($sender['name']), '"\' '), 2);
        if (count($arr) < 2) {
            $arr[] = '';
        }
        $contactlib->replace_contact($contId, $arr[0], $arr[1], $sender['email'], '', $user, [$this->get('group')], [$ext['fieldId'] => $pageName], true);
        if (! $contId) {
            $contId = $contactlib->get_contactId_email($sender['email'], $user);
        }

        // make or update wiki page
        $wikilib = TikiLib::lib('wiki');

        if (! $wikilib->page_exists($pageName)) {
            $comment = 'Generated by GroupMail on ' . date(DATE_RFC822);
            $description = "Page $comment for " . $sender['email'];
            $data = '!GroupMail case with ' . $sender['email'] . "\n";
            $data .= "''$comment''\n\n";
            $data .= "!!Info\n";
            $data .= "Contact info: [tiki-contacts.php?contactId=$contId|" . $sender['name'] . "]\n\n";
            $data .= "!!Logs\n";
            $data .= '{trackerlist trackerId="' . $this->get('trackerId') . '" ' . 'fields="' . $this->get('fromFId') . ':' . $this->get('operatorFId') . ':' . $this->get('subjectFId') . ':' . $this->get('datetimeFId') . '" ' . 'popup="' . $this->get('fromFId') . ':' . $this->get('contentFId') . '" stickypopup="n" showlinks="y" shownbitems="n" showinitials="n"' . 'showstatus="n" showcreated="n" showlastmodif="n" filterfield="' . $this->get('fromFId') . '" filtervalue="' . $sender['email'] . '"}';
            $data .= "\n\n";

            $tikilib->create_page($pageName, 0, $data, $tikilib->now, $comment, $user, $tikilib->get_ip_address(), $description);
            $categlib->update_object_categories([$categlib->get_category_id('Help Team Pages')], $pageName, 'wiki page');       // TODO remove hard-coded cat name
        }

        $this->out('operator', $user);
    }
}

/**
 * Put back a groupmail message
 * @subpackage tiki/handler
 */
class Hm_Handler_put_back_groupmail extends Hm_Handler_Module {
    /**
     * Put back a message
     */
    public function process() {
        list($success, $form) = $this->process_form(array('msgid', 'imap_msg_uid', 'imap_server_id', 'folder'));
        if (! $success) {
            return;
        }

        global $user;

        $trklib = TikiLib::lib('trk');

        $itemid = $trklib->get_item_id($this->get('trackerId'), $this->get('messageFId'), $form['msgid']);
        if ($itemid > 0 && $user == $trklib->get_item_value($this->get('trackerId'), $itemid, $this->get('operatorFId'))) { // simple security check
            $trklib->remove_tracker_item($itemid);
            $this->out('item_removed', true);
        } else {
            Hm_Msgs::add('ERR'.tr('Tracker item not found!'));
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
        if (! $this->get('groupmail_enabled')) {
            return '';
        }
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
        $res = '<table class="message_table groupmail">';
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
                $itemid = $trklib->get_item_id($this->get('trackerId'), $this->get('messageFId'), $id);
                if ($itemid > 0) {
                    $operator = $trklib->get_item_value($this->get('trackerId'), $itemid, $this->get('operatorFId'));
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
                        array('take_callback', $id, $operator)
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
 * Ajax response for Take operation
 * @subpackage tiki/output
 */
class Hm_Output_take_groupmail_response extends Hm_Output_Module {
    /**
     * Send the response
     */
    protected function output() {
        $this->out('operator', $this->get('operator'));
    }
}

/**
 * Ajax response for Put back operation
 * @subpackage tiki/output
 */
class Hm_Output_put_back_groupmail_response extends Hm_Output_Module {
    /**
     * Send the response
     */
    protected function output() {
        $this->out('item_removed', $this->get('item_removed'));
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
    global $user;
    list($id, $operator) = $vals;
    if (! empty($operator)) {
        if ($operator == $user) {
            $output = sprintf('<a class="btn btn-outline-secondary btn-sm tips mod_webmail_action webmail_taken" title="%s" onclick="tiki_groupmail_put_back(this, \'%s\'); return false;" href="#">%s</a>',
                tr('Put this item back'),
                $id,
                $operator
            );
        } else {
            $output = sprintf('<span class="btn btn-outline-secondary btn-sm tips mod_webmail_action webmail_taken" title="%s">%s</span>&nbsp;',
                tr('Taken by %0', $operator),
                $operator
            );
        }
    } else {
        $output = sprintf(
            '<a class="btn btn-outline-secondary btn-sm tips mod_webmail_action" title="%s" onclick="tiki_groupmail_take(this, \'%s\'); return false;" href="#">%s</a>',
            tr('Take this email'),
            $vals[0],
            tr('TAKE')
        );
    }
    return sprintf('<td class="action">%s</td>', $output);
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
        $output = smarty_block_self_link([
                '_script' => $tikiroot.'tiki-contacts.php',
                'contactId' => $contactId,
                '_icon_name' => 'user',
                '_width' => 12,
                '_height' => 12
            ], tr('View contact'), $smarty).' ';
        if (! empty($wikiPage)) {
            $output .= smarty_block_self_link([
                '_script' => $tikiroot.smarty_modifier_sefurl($wikiPage),
                '_class' => "mod_webmail_from"
            ], $from, $smarty);
        } else {
            $output .= smarty_block_self_link([
                '_script' => $tikiroot.'tiki-contacts.php',
                'contactId' => $contactId,
                '_class' => "mod_webmail_from"
            ], $from, $smarty);
        }
    } else {
        $output = '<span class="mod_webmail_from">'.$from.'</span>';
    }
    return sprintf('<td class="%s" title="%s">%s</td>', $output_mod->html_safe($class), $output_mod->html_safe($from), $output);
}}
