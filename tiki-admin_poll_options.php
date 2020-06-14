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
$polllib = TikiLib::lib('poll');
$access->check_feature('feature_polls');
$access->check_permission('tiki_p_admin_polls');

if (! isset($_REQUEST["pollId"])) {
	Feedback::error(tr('No poll indicated'));
}
$smarty->assign('pollId', $_REQUEST["pollId"]);
$menu_info = $polllib->get_poll($_REQUEST["pollId"]);
$smarty->assign('menu_info', $menu_info);
if (! isset($_REQUEST["optionId"])) {
	$_REQUEST["optionId"] = 0;
}
if (isset($_REQUEST["remove"]) && $access->checkCsrf(true)) {
	$result = $polllib->remove_poll_option($_REQUEST["remove"]);
	if ($result && $result->numRows()) {
		Feedback::success(tr('Poll option removed'));
	} else {
		Feedback::error(tr('Poll option not removed'));
	}
}
if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
	$result = $polllib->replace_poll_option($_REQUEST["pollId"], $_REQUEST["optionId"], $_REQUEST["title"], $_REQUEST['position']);
	if ($result && $result->numRows()) {
		Feedback::success(tr('Poll option added or changed'));
	} else {
		Feedback::error(tr('No poll options added or changed'));
	}
	$_REQUEST["optionId"] = 0;
}
$smarty->assign('optionId', $_REQUEST["optionId"]);
if ($_REQUEST["optionId"]) {
	$info = $polllib->get_poll_option($_REQUEST["optionId"]);
} else {
	$info = [];
	$info["title"] = '';
	$info["votes"] = 0;
	$info["position"] = '';
}
$smarty->assign('title', $info["title"]);
$smarty->assign('votes', $info["votes"]);
$channels = $polllib->list_poll_options($_REQUEST["pollId"]);
$smarty->assign('ownurl', $tikilib->httpPrefix() . $_SERVER["REQUEST_URI"]);
$smarty->assign_by_ref('channels', $channels);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_poll_options.tpl');
$smarty->display("tiki.tpl");
