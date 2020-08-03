<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');
include_once('lib/taglines/taglinelib.php');

$access->check_permission('tiki_p_edit_cookies');

if (! isset($_REQUEST["cookieId"])) {
    $_REQUEST["cookieId"] = 0;
}
$smarty->assign('cookieId', $_REQUEST["cookieId"]);
if ($_REQUEST["cookieId"]) {
    $info = $taglinelib->get_cookie($_REQUEST["cookieId"]);
} else {
    $info = [];
    $info["cookie"] = '';
}
$smarty->assign('cookie', $info["cookie"]);
if (isset($_REQUEST["remove"]) && $access->checkCsrf(true)) {
    $result = $taglinelib->remove_cookie($_REQUEST["remove"]);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Cookie removed'));
    } else {
        Feedback::error(tr('Cookie not removed'));
    }
}
if (isset($_REQUEST["removeall"]) && $access->checkCsrf(true)) {
    $result = $taglinelib->remove_all_cookies();
    if ($result && $result->numRows()) {
        Feedback::success(tr('All cookies removed'));
    } else {
        Feedback::error(tr('No cookies removed'));
    }
}
if (isset($_REQUEST["upload"]) && $access->checkCsrf()) {
    if (isset($_FILES['userfile1']) && is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
        $fp = fopen($_FILES['userfile1']['tmp_name'], "r");
        $result = false;
        $resultCount = 0;
        while (! feof($fp)) {
            $data = fgets($fp, 65535);
            if (! empty($data)) {
                $data = str_replace("\n", "", $data);
                $result = $taglinelib->replace_cookie(0, $data);
                if ($result && $result->numRows()) {
                    $resultCount = $resultCount + $result->numRows();
                }
            }
        }
        fclose($fp);
        $size = $_FILES['userfile1']['size'];
        $name = $_FILES['userfile1']['name'];
        $type = $_FILES['userfile1']['type'];
        if ($resultCount) {
            $msg = $resultCount === 1 ? tr('File uploaded and one cookie created or replaced')
                : tr('File uploaded and %0 cookies created or replaced', $resultCount);
            Feedback::success($msg);
        } else {
            Feedback::error(tr('Upload failed - no cookies created'));
        }
    } else {
        Feedback::error(tr('Upload failed'));
    }
}
if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    $result = $taglinelib->replace_cookie($_REQUEST["cookieId"], $_REQUEST["cookie"]);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Cookie saved'));
    } else {
        Feedback::error(tr('Cookie not saved'));
    }
    $smarty->assign("cookieId", '0');
    $smarty->assign('cookie', '');
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'cookieId_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $taglinelib->list_cookies($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_cookies.tpl');
$smarty->display("tiki.tpl");
