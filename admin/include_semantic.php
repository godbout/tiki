<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}
$semanticlib = TikiLib::lib('semantic');

//*** begin state-changing actions
if (isset($_POST['save']) && $access->checkCsrf()) {
	$result = $semanticlib->replaceToken($_POST['token'], $_POST['newName'], $_POST['label'], $_POST['invert']);
	if ($result === true) {
		$_REQUEST['token'] = $_POST['newName'];
	} else {
		$smarty->assign('save_message', $result);
	}
}
if (isset($_POST['remove']) && $access->checkCsrf()) {
	$list = [];
	if (isset($_POST['select'])) {
		$list = (array) $_POST['select'];
	}
	foreach ($list as $token) {
		$semanticlib->removeToken($token);
	}
}
if (isset($_POST['removeclean']) && $access->checkCsrf()) {
	$list = [];
	if (isset($_POST['select'])) {
		$list = (array) $_POST['select'];
	}
	foreach ($list as $token) {
		$semanticlib->removeToken($token, true);
	}
}
if (isset($_POST['clean']) && $access->checkCsrf()) {
	$list = [];
	if (isset($_POST['select'])) {
		$list = (array) $_POST['select'];
	}
	foreach ($list as $token) {
		$semanticlib->cleanToken($token);
	}
}
if (isset($_POST['oldName']) && $access->checkCsrf()) {
	$semanticlib->renameToken($_POST['oldName'], $_POST['token']);
}
//*** end state-changing actions

$smarty->assign('tokens', $semanticlib->getTokens());
$smarty->assign('new_tokens', $semanticlib->getNewTokens());

if (isset($_POST['select'])) {
	$smarty->assign('select', $_POST['select']);
}
if (isset($_REQUEST['token']) && $semanticlib->isValid($_REQUEST['token']) && (isset($_POST['create']) || false !== $semanticlib->getToken($_REQUEST['token']))) {
	$smarty->assign('selected_token', $_REQUEST['token']);
	$smarty->assign('selected_detail', $semanticlib->getToken($_REQUEST['token']));
}
if (isset($_REQUEST['rename'])) {
	$smarty->assign('rename', $_REQUEST['token']);
}
if (isset($_POST['list'])) {
	$lists = [];
	$list = [];
	if (isset($_POST['select'])) {
		$list = (array) $_POST['select'];
	}
	foreach ($list as $token) {
		$lists[$token] = $semanticlib->getLinksUsing($token);
	}
	$smarty->assign('link_lists', $lists);

	$_REQUEST['redirect'] = 0;
}
