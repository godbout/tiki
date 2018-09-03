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
include_once('lib/htmlpages/htmlpageslib.php');
$access->check_feature('feature_html_pages');
$access->check_permission('tiki_p_edit_html_pages');
if (! isset($_REQUEST["pageName"])) {
	$_REQUEST["pageName"] = '';
}
$smarty->assign('pageName', $_REQUEST["pageName"]);
if ($_REQUEST["pageName"]) {
	$info = $htmlpageslib->get_html_page($_REQUEST["pageName"]);
} else {
	$info = [];
	$info["pageName"] = '';
	$info["content"] = '';
	$info["refresh"] = 0;
	$info["type"] = 's';
}
$smarty->assign('info', $info);
if (isset($_REQUEST["remove"]) && $access->checkCsrfForm(tr('Remove HTML page?'))) {
	$result = $htmlpageslib->remove_html_page($_REQUEST["remove"]);
	if ($result && $result->numRows()) {
		Feedback::success(tr('HTML page removed'));
	} else {
		Feedback::error(tr('HTML page not removed'));
	}
}
if (isset($_REQUEST["templateId"]) && $_REQUEST["templateId"] > 0) {
	$template_data = TikiLib::lib('template')->get_template($_REQUEST["templateId"]);
	$_REQUEST["content"] = $template_data["content"];
	$_REQUEST["preview"] = 1;
}
$smarty->assign('preview', 'n');
if (isset($_REQUEST["preview"])) {
	$smarty->assign('preview', 'y');
	//$parsed = TikiLib::lib('parser')->parse_data($_REQUEST["content"]);
	$parsed = $htmlpageslib->parse_html_page($_REQUEST["pageName"], $_REQUEST["content"]);
	$smarty->assign('parsed', $parsed);
	$info["content"] = $_REQUEST["content"];
	$info["refresh"] = $_REQUEST["refresh"];
	$info["pageName"] = $_REQUEST["pageName"];
	$info["type"] = $_REQUEST["type"];
	$smarty->assign('info', $info);
}
if (isset($_REQUEST["save"]) && ! empty($_REQUEST["pageName"]) && $access->checkCsrf()) {
	$tid = $htmlpageslib->replace_html_page($_REQUEST["pageName"], $_REQUEST["type"], $_REQUEST["content"], $_REQUEST["refresh"]);
	if ($tid) {
		Feedback::success(tr('HTML page saved'));
	} else {
		Feedback::error(tr('HTML page not saved'));
	}
	$smarty->assign("pageName", '');
	$info["pageName"] = '';
	$info["content"] = '';
	$info["regresh"] = 0;
	$info["type"] = 's';
	$smarty->assign('info', $info);
}
if (! isset($_REQUEST["sort_mode"])) {
	$sort_mode = 'created_desc';
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
$channels = $htmlpageslib->list_html_pages($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
$templates = TikiLib::lib('template')->list_templates('html', 0, -1, 'name_asc', '');

$smarty->assign_by_ref('templates', $templates["data"]);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_html_pages.tpl');
$smarty->display("tiki.tpl");
