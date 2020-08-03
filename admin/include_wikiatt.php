<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

$wikilib = TikiLib::lib('wiki');
$auto_query_args = ['sort_mode', 'page'];

$find = '';
$offset = 0;
$sort_mode = 'created_desc';

//*** begin state-changing actions
if (isset($_POST['action']) and isset($_POST['attId'])) {
    $item = $wikilib->get_item_attachment($_POST['attId']);
    if ($_POST['action'] == 'move2db' && $access->checkCsrf()) {
        $wikilib->file_to_db($prefs['w_use_dir'] . $item['path'], $_POST['attId']);
    } elseif ($_POST['action'] == 'move2file' && $access->checkCsrf()) {
        $wikilib->db_to_file($item['filename'], $_POST['attId']);
    }
}

if (isset($_POST['all2db']) && $access->checkCsrf()) {
    $attachments = $wikilib->list_all_attachments();
    for ($i = 0; $i < $attachments['cant']; $i++) {
        if ($attachments['data'][$i]['path']) {
            $wikilib->file_to_db($prefs['w_use_dir'] . $attachments['data'][$i]['path'], $attachments['data'][$i]['attId']);
        }
    }
} elseif (isset($_POST['all2file']) && $access->checkCsrf()) {
    $attachments = $wikilib->list_all_attachments();
    for ($i = 0; $i < $attachments['cant']; $i++) {
        if (! $attachments['data'][$i]['path']) {
            $wikilib->db_to_file($attachments['data'][$i]['filename'], $attachments['data'][$i]['attId']);
        }
    }
}
//*** end state-changing actions

if (isset($_REQUEST['find'])) {
    $find = $_REQUEST['find'];
}
if (isset($_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
}
if (isset($_REQUEST['sort_mode'])) {
    $sort_mode = $_REQUEST['sort_mode'];
}

$smarty->assign_by_ref('find', $find);
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$attachments = $wikilib->list_all_attachments($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $attachments['cant']);
$smarty->assign_by_ref('attachments', $attachments['data']);
$urlquery['find'] = $find;
$urlquery['page'] = 'wikiatt';
$urlquery['sort_mode'] = $sort_mode;
$smarty->assign_by_ref('urlquery', $urlquery);
