<?php
// (c) Copyright 2002-2009 by authors of the Tiki Wiki/CMS/Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: /cvsroot/tikiwiki/tiki/tiki-admin_hotwords.php,v 1.21.2.2 2007-11-25 21:42:35 sylvieg Exp $
require_once ('tiki-setup.php');
include_once ('lib/hotwords/hotwordlib.php');
$access->check_feature('feature_hotwords');
$access->check_permission('tiki_p_admin');

// Process the form to add a user here
if (isset($_REQUEST["add"])) {
	check_ticket('admin-hotwords');
	if (empty($_REQUEST["word"]) || empty($_REQUEST["url"])) {
		$smarty->assign('msg', tra("You have to provide a hotword and a URL"));
		$smarty->display("error.tpl");
		die;
	}
	$hotwordlib->add_hotword($_REQUEST["word"], $_REQUEST["url"]);
}
if (isset($_REQUEST["remove"]) && !empty($_REQUEST["remove"])) {
	$area = 'delhotword';
	if ($prefs['feature_ticketlib2'] != 'y' or (isset($_POST['daconfirm']) and isset($_SESSION["ticket_$area"]))) {
		key_check($area);
		$hotwordlib->remove_hotword($_REQUEST["remove"]);
	} else {
		key_get($area);
	}
}
if (!isset($_REQUEST["sort_mode"])) {
	$sort_mode = 'word_desc';
} else {
	$sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
if (!isset($_REQUEST["offset"])) {
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
$words = $hotwordlib->list_hotwords($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $words["cant"]);
// Get users (list of users)
$smarty->assign_by_ref('words', $words["data"]);
ask_ticket('admin-hotwords');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_hotwords.tpl');
$smarty->display("tiki.tpl");
