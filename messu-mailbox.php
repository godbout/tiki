<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'user_messages';
require_once('tiki-setup.php');
$messulib = TikiLib::lib('message');
$access->check_user($user);
$access->check_feature('feature_messages');
$access->check_permission('tiki_p_messages');

$maxRecords = $messulib->get_user_preference($user, 'mess_maxRecords', 20);
// auto-archiving of read mails?
$mess_archiveAfter = $messulib->get_user_preference($user, 'mess_archiveAfter', 0);
$smarty->assign_by_ref('mess_archiveAfter', $mess_archiveAfter);
if ($mess_archiveAfter > 0) {
    // get date of last check. if not set yet, set it to 'before 10 minutes'
    $mess_archiveLast = $messulib->get_user_preference($user, 'mess_archiveLast', $tikilib->now - 600);
    // only run auto-archive job every 10 minutes:
    if ($tikilib->now - $mess_archiveLast >= 600) {
        $messulib->set_user_preference($user, 'mess_archiveLast', $tikilib->now);
        $messulib->archive_messages($user, $mess_archiveAfter);
    }
}

//set defaults
$sort_mode = 'date_desc';
$offset = 0;
$find = '';
$orig_or_reply = "r";

// Mark messages if the mark button was pressed
if (isset($_POST['flagmsg']) && $access->checkCsrf()) {
    $parts = explode("_", $_POST['flagmsg']);
    $messulib->flag_message($user, $parts[1], 'isFlagged', $parts[0]);
}

if (isset($_POST["mark"])) {
    if (isset($_POST["msg"]) && $access->checkCsrf()) {
        $i = 0;
        foreach (array_keys($_POST["msg"]) as $msg) {
            $parts = explode('_', $_POST['action']);
            $result = $messulib->flag_message($user, $msg, $parts[0], $parts[1]);
            $i = $i + $result->numRows();
        }
        if ($i) {
            $msg = $i === 1 ? tr('%0 message was marked', $i) : tr('%0 messages were marked', $i);
            Feedback::success($msg);
        } else {
            Feedback::error(tra('No messages were marked'));
        }
    } elseif (!isset($_POST["msg"])) {
        Feedback::error(tra('No messages were selected to mark'));
    }
}
// Delete messages if the delete button was pressed
if (isset($_POST["delete"])) {
    if (isset($_POST["msg"]) && $access->checkCsrf(true)) {
        $i = 0;
        foreach (array_keys($_POST["msg"]) as $msg) {
            $result = $messulib->delete_message($user, $msg);
            $i = $i + $result->numRows();
        }
        if ($i) {
            $msg = $i === 1 ? tr('%0 message was deleted', $i) : tr('%0 messages were deleted', $i);
            Feedback::success($msg);
        } else {
            Feedback::error(tra('No messages were deleted'));
        }
    } elseif (!isset($_POST["msg"])) {
        Feedback::error(tra('No messages were selected to delete'));
    }
}
// Archive messages if the archive button was pressed
if (isset($_POST["archive"])) {
    if (isset($_POST["msg"]) && $access->checkCsrf()) {
        $tmp = $messulib->count_messages($user, 'archive');
        $i = 0;
        foreach (array_keys($_POST["msg"]) as $msg) {
            if (($prefs['messu_archive_size'] > 0) && ($tmp + $i >= $prefs['messu_archive_size'])) {
                $smarty->assign('msg', tra("Archive is full. Delete some messages from archive first."));
                $smarty->display("error.tpl");
                die;
            }
            $result = $messulib->archive_message($user, $msg);
            $i = $i + $result->numRows();
        }
        if ($i) {
            $msg = $i === 1 ? tr('%0 message was archived', $i) : tr('%0 messages were archived', $i);
            Feedback::success($msg);
        } else {
            Feedback::error(tra('No messages were archived'));
        }
    } elseif (!isset($_POST["msg"])) {
        Feedback::error(tra('No messages were selected to archive'));
    }
}

// Download messages if the download button was pressed
if (isset($_REQUEST["download"])) {
    // if message ids are handed over, use them:
    if (isset($_REQUEST["msg"])) {
        foreach (array_keys($_REQUEST["msg"]) as $msg) {
            $tmp = $messulib->get_message($user, $msg, 'messages');
            $items[] = $tmp;
        }
    } else {
        $items = $messulib->get_messages($user, 'messages', '', '', '');
    }
    $smarty->assign_by_ref('items', $items);
    header("Content-Disposition: attachment; filename=tiki-msg-mailbox-" . time() . ".txt ");
    $smarty->display('messu-download.tpl', null, null, null, 'application/download');
    die;
}

if (isset($_REQUEST['filter'])) {
    if ($_REQUEST['flags'] != '') {
        $parts = explode('_', $_REQUEST['flags']);
        $_REQUEST['flag'] = $parts[0];
        $_REQUEST['flagval'] = $parts[1];
    }
}
if (isset($_REQUEST["sort_mode"])) {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (isset($_REQUEST["offset"])) {
    $offset = $_REQUEST["offset"];
}
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
}
if (isset($_REQUEST["origto"])) {
    $_REQUEST["replyto"] = $_REQUEST["origto"];
    $orig_or_reply = "o";
}
if (! isset($_REQUEST["replyto"])) {
    $_REQUEST["replyto"] = '';
}
if (! isset($_REQUEST["priority"])) {
    $_REQUEST["priority"] = '';
}
if (! isset($_REQUEST["flag"])) {
    $_REQUEST["flag"] = '';
}
if (! isset($_REQUEST["flagval"])) {
    $_REQUEST["flagval"] = '';
}
$smarty->assign_by_ref('flag', $_REQUEST['flag']);
$smarty->assign_by_ref('priority', $_REQUEST['priority']);
$smarty->assign_by_ref('flagval', $_REQUEST['flagval']);
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign('find', $find);
// What are we paginating: items
$items = $messulib->list_user_messages($user, $offset, $maxRecords, $sort_mode, $find, $_REQUEST["flag"], $_REQUEST["flagval"], $_REQUEST['priority'], '', $_REQUEST["replyto"], $orig_or_reply);
$smarty->assign_by_ref('cant_pages', $items["cant"]);
$smarty->assign_by_ref('mess_maxRecords', $maxRecords);
$smarty->assign_by_ref('items', $items["data"]);
$cellsize = 200;
$percentage = 1;
if ($prefs['messu_mailbox_size'] > 0) {
    $current_number = $messulib->count_messages($user);
    $smarty->assign('messu_mailbox_number', $current_number);
    $smarty->assign('messu_mailbox_size', $prefs['messu_mailbox_size']);
    $percentage = ($current_number / $prefs['messu_mailbox_size']) * 100;
    $cellsize = round($percentage / 100 * 200);
    if ($current_number > $prefs['messu_mailbox_size']) {
        $cellsize = 200;
    }
    if ($cellsize < 1) {
        $cellsize = 1;
    }
    $percentage = round($percentage);
}
$smarty->assign('cellsize', $cellsize);
$smarty->assign('percentage', $percentage);
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'messu-mailbox.tpl');
$smarty->display("tiki.tpl");
