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
include_once('lib/ban/banlib.php');
$access->check_feature('feature_banning');
$access->check_permission('tiki_p_admin_banning');

$auto_query_args = [ 'banId' ];

if (isset($_REQUEST['del'])) {
	if (!isset($_REQUEST['delsec'])) {
		Feedback::error(tra('No rule selected for deletion. No deletions were performed.'));
	} elseif($access->checkCsrfForm(tr('Delete selected banning rules?'))) {
		$items = array_keys($_POST['delsec']);
		$resultRowsDeleted = 0;
		foreach ($items as $sec) {
			$result = $banlib->remove_rule($sec);
			$resultRowsDeleted += $result->numRows();
		}
		unset($_POST['banId']);
		if ($resultRowsDeleted) {
			$msg = $resultRowsDeleted === 1 ? tra('The selected banning rule has been deleted')
				: tr('%0 banning rules have been deleted', $resultRowsDeleted);
			Feedback::success($msg);
		} else {
			Feedback::error(tr('No actions were deleted from the log'));
		}
	}
}

if (isset($_POST["import"]) && isset($_FILES["fileCSV"]) && $access->checkCsrf()) {
	// import banning rules //
	$number_imported = $banlib->importCSV($_FILES["fileCSV"]["tmp_name"], isset($_REQUEST['import_as_new']));
	if ($number_imported > 0) {
		$smarty->assign('updated', "y");
		$smarty->assign('number_imported', $number_imported);
	}
	unset($_POST['banId']);
}

if (isset($_POST['save']) && $access->checkCsrf()) {
	if ($_POST['mode'] === 'user' && empty($_POST['userreg'])) {
		Feedback::error(tra("Not saved:") . ' ' . tra("Username pattern empty"));
	} elseif ($_POST['mode'] === 'ip'
		&& $_POST['ip1'] == 255
		&& $_POST['ip2'] == 255
		&& $_POST['ip3'] == 255
		&& $_POST['ip4'] == 255)
	{
		Feedback::error(tra("Not saved:") . ' ' . tra("Default IP pattern still set"));
	} else {
		$_POST['use_dates'] = isset($_POST['use_dates']) ? 'y' : 'n';
		$_POST['date_from'] = $tikilib->make_time(
			0,
			0,
			0,
			$_POST['date_fromMonth'],
			$_POST['date_fromDay'],
			$_POST['date_fromYear']
		);
		$_POST['date_to'] = $tikilib->make_time(
			0,
			0,
			0,
			$_POST['date_toMonth'],
			$_POST['date_toDay'],
			$_POST['date_toYear']
		);
		$sections = isset($_POST['section']) ? array_keys($_POST['section']) : [];
		$replaced = [];
		$resultRows = 0;
		// Handle case when many IPs are banned
		if ($_POST['mode'] == 'mass_ban_ip') {
			foreach ($_POST['multi_banned_ip'] as $ip => $value) {
				list($ip1,$ip2,$ip3,$ip4) = explode('.', $ip);
				$result = $banlib->replace_rule(
					$_POST['banId'],
					'ip',
					$_POST['title'],
					$ip1,
					$ip2,
					$ip3,
					$ip4,
					$_POST['userreg'],
					$_POST['date_from'],
					$_POST['date_to'],
					$_POST['use_dates'],
					$_POST['message'],
					$sections
				);
				$resultRows += $result->numRows();
				$replaced[] = $_POST['title'];
			}
		} else {
			$result = $banlib->replace_rule(
				$_POST['banId'],
				$_POST['mode'],
				$_POST['title'],
				$_POST['ip1'],
				$_POST['ip2'],
				$_POST['ip3'],
				$_POST['ip4'],
				$_POST['userreg'],
				$_POST['date_from'],
				$_POST['date_to'],
				$_POST['use_dates'],
				$_POST['message'],
				$sections
			);
			$resultRows += $result->numRows();
			$replaced[] = $_POST['title'];
		}
		$info['sections'] = [];
		$info['title'] = '';
		$info['mode'] = 'user';
		$info['ip1'] = 255;
		$info['ip2'] = 255;
		$info['ip3'] = 255;
		$info['ip4'] = 255;
		$info['use_dates'] = 'n';
		$info['date_from'] = $tikilib->now;
		$info['date_to'] = $tikilib->now + 7 * 24 * 3600;
		$info['message'] = '';
		$smarty->assign_by_ref('info', $info);
		unset($_REQUEST['banId']);

		$replacedCount = count($replaced);
		if ($resultRows > 0 && $resultRows === $replacedCount) {
			$msg = $resultRows === 1 ? tra('The following banning rule has been saved or replaced:')
				: tr('The following %0 banning rules have been saved or replaced:', $resultRows);
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $replaced,
			];
			Feedback::success($feedback);
		} elseif ($replaced > 0 && $resultRows < $replacedCount) {
			if (!$resultRows) {
				$msg = tra('No changes were made to the following selected banning rules:');
			} else {
				$msg = tr('Only %0 of the selected banning rules shown below were added or changed', $resultRows);
			}
			if (!empty($msg)) {
				$feedback = [
					'tpl' => 'action',
					'mes' => $msg,
					'items' => $replaced,
				];
				Feedback::warning($feedback);
			}
		} elseif ($replacedCount === 0) {
			Feedback::error(tr('No banning rules were selected'));
		}
	}
}

if (! empty($_REQUEST['export'])) {
	$maxRecords = -1;
} elseif (isset($_REQUEST['max'])) {
	$maxRecords = $_REQUEST['max'];
} else {
	$maxRecords = $prefs['maxRecords'];
}

if (! empty($_REQUEST['banId'])) {
	$info = $banlib->get_rule($_REQUEST['banId']);
} else {
	$_REQUEST['banId'] = 0;
	$info['sections'] = [];
	$info['title'] = '';
	$info['mode'] = 'user';
	$info['user'] = '';
	$info['ip1'] = 255;
	$info['ip2'] = 255;
	$info['ip3'] = 255;
	$info['ip4'] = 255;
	$info['use_dates'] = 'n';
	$info['date_from'] = $tikilib->now;
	$info['date_to'] = $tikilib->now + 7 * 24 * 3600 * 100;
	$info['message'] = '';
}

// Handle case when coming from tiki-list_comments with a list of IPs to ban
if (! empty($_REQUEST['mass_ban_ip'])) {
	$commentslib = TikiLib::lib('comments');
	$smarty->assign('mass_ban_ip', $_REQUEST['mass_ban_ip']);
	$info['mode'] = 'mass_ban_ip';
	$info['title'] = tr('Multiple IP Banning');
	$info['message'] = tr('Access from your localization was forbidden due to excessive spamming.');
	$info['date_to'] = $tikilib->now + 365 * 24 * 3600;
	$banId_list = explode('|', $_REQUEST['mass_ban_ip']);
	// Handle case when coming from tiki-list_comments with a list of IPs to ban and also delete the related comments
	foreach ($banId_list as $id) {
		$ban_comment = $commentslib->get_comment($id);
		$ban_comments_list[$ban_comment['user_ip']][$id]['userName'] = $ban_comment['userName'];
		$ban_comments_list[$ban_comment['user_ip']][$id]['title'] = $ban_comment['title'];
	}
	$smarty->assign_by_ref('ban_comments_list', $ban_comments_list);
}

// Handle case when coming from tiki-admin_actionlog with a list of IPs to ban
if (! empty($_REQUEST['mass_ban_ip_actionlog'])) {
	$logslib = TikiLib::lib('logs');
	$smarty->assign('mass_ban_ip', $_REQUEST['mass_ban_ip_actionlog']);
	$info['mode'] = 'mass_ban_ip';
	$info['title'] = tr('Multiple IP Banning');
	$info['message'] = tr('Access from your localization was forbidden due to excessive spamming.');
	$info['date_to'] = $tikilib->now + 365 * 24 * 3600;
	$banId_list = explode('|', $_REQUEST['mass_ban_ip_actionlog']);
	foreach ($banId_list as $id) {
		$ban_actions = $logslib->get_info_action($id);
		$ban_comments_list[$ban_actions['ip']][$id]['userName'] = $ban_actions['user'];
	}
	$smarty->assign_by_ref('ban_comments_list', $ban_comments_list);
}

// Handle case when coming from tiki-adminusers with a list of IPs to ban
if (! empty($_REQUEST['mass_ban_ip_users'])) {
	$logslib = TikiLib::lib('logs');
	$smarty->assign('mass_ban_ip', $_REQUEST['mass_ban_ip_users']);
	$info['mode'] = 'mass_ban_ip';
	$info['title'] = tr('Multiple IP Banning');
	$info['message'] = tr('Access from your localization was forbidden due to excessive spamming.');
	$info['date_to'] = $tikilib->now + 365 * 24 * 3600;
	$banUsers_list = explode('|', $_REQUEST['mass_ban_ip_users']);
	foreach ($banUsers_list as $banUser) {
		$ban_actions = $logslib->get_user_registration_action($banUser);
		$ban_comments_list[$ban_actions['ip']][$banUser]['userName'] = $banUser;
	}
	$smarty->assign_by_ref('ban_comments_list', $ban_comments_list);
}

$smarty->assign('banId', $_REQUEST['banId']);
$smarty->assign_by_ref('info', $info);

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
$items = $banlib->list_rules($offset, $maxRecords, $sort_mode, $find);

if (isset($_REQUEST['export']) || isset($_REQUEST['csv'])) {
	// export banning rules //
	$csv = $banlib->export_rules($items['data']);

	header("Content-type: text/comma-separated-values; charset:UTF-8");
	header('Content-Disposition: attachment; filename="tiki-admin_banning.csv"');
	if (function_exists('mb_strlen')) {
		header('Content-Length: ' . mb_strlen($csv, '8bit'));
	} else {
		header('Content-Length: ' . strlen($csv));
	}
	echo $csv;
	die();
}

$smarty->assign('cant', $items['cant']);
$smarty->assign_by_ref('cant_pages', $items["cant"]);
$smarty->assign_by_ref('items', $items["data"]);
$smarty->assign('sections', $sections_enabled);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_banning.tpl');
$smarty->display("tiki.tpl");
