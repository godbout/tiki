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
include_once('lib/featured_links/flinkslib.php');
$access->check_feature('feature_featuredLinks');
$access->check_permission('tiki_p_admin');
$smarty->assign('title', '');
$smarty->assign('type', 'f');
$smarty->assign('position', 1);
if (isset($_REQUEST["generate"])) {
    $flinkslib->generate_featured_links_positions();
}
if (! isset($_REQUEST["editurl"])) {
    $_REQUEST["editurl"] = 'n';
}
if ($_REQUEST["editurl"] != 'n') {
    //updating an existing link
    if (isset($_REQUEST['add']) && $_REQUEST['add'] == 'Save' && $access->checkCsrf()) {
        $result = $flinkslib->update_featured_link($_REQUEST["url"], $_REQUEST["title"], '', $_REQUEST["position"], $_REQUEST["type"]);
        if ($result && $result->numRows()) {
            Feedback::success(tr('Featured link saved'));
        } else {
            Feedback::error(tr('Featured link not saved'));
        }
    }
    //opening the form to edit a link
    $info = $flinkslib->get_featured_link($_REQUEST["editurl"]);
    if (! $info) {
        Feedback::errorPage(tr('Non-existent link'));
    }
    $smarty->assign('title', $info["title"]);
    $smarty->assign('position', $info["position"]);
    $smarty->assign('type', $info["type"]);
} elseif (isset($_REQUEST['add']) && $_REQUEST['add'] == 'Save' && ! empty($_REQUEST['url']) && $access->checkCsrf()) {
    //saving a new link
    $result = $flinkslib->add_featured_link(
        $_REQUEST["url"],
        $_REQUEST["title"],
        '',
        $_REQUEST["position"],
        $_REQUEST["type"]
    );
    if ($result && $result->numRows()) {
        Feedback::success(tr('Featured link saved'));
    } else {
        Feedback::error(tr('Featured link not saved'));
    }
}
$smarty->assign('editurl', $_REQUEST["editurl"]);

if (isset($_REQUEST["remove"]) && $access->checkCsrf(true)) {
    $result = $flinkslib->remove_featured_link($_REQUEST["remove"]);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Featured link removed'));
    } else {
        Feedback::error(tr('Featured link not removed'));
    }
}
$links = $tikilib->get_featured_links(999999);
$smarty->assign_by_ref('links', $links);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_links.tpl');
$smarty->display("tiki.tpl");
