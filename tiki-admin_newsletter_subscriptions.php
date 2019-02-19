<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'newsletters';
require_once('tiki-setup.php');
include_once('lib/newsletters/nllib.php');
$auto_query_args = [
	'sort_mode',
	'offset',
	'find',
	'nlId',
	'sort_mode_g',
	'offset_g',
	'find_g'
];

$access->check_feature('feature_newsletters');

if (! isset($_REQUEST["nlId"])) {
	Feedback::error(tr('No newsletter indicated'));
}

$info = $nllib->get_newsletter($_REQUEST["nlId"]);

if (empty($info)) {
	Feedback::error(tr('Newsletter does not exist'));
}

$smarty->assign('nlId', $_REQUEST["nlId"]);

$tikilib->get_perm_object($_REQUEST['nlId'], 'newsletter');

$access->check_permission('tiki_p_admin_newsletters');

if (isset($_REQUEST['action'])
	&& $_REQUEST['action'] === 'delsel_x'
	&& isset($_REQUEST['checked'])
	&& $access->checkCsrfForm(tr('Remove selected subscriptions?')))
{
	$i = 0;
	foreach ($_REQUEST['checked'] as $check) {
		$result = $nllib->remove_newsletter_subscription_code($check);
		if ($result && $result->numRows()) {
			$i += $result->numRows();
		}
	}
	if ($i) {
		$word = $i === 1 ? tr('subscription') : tr('subscriptions');
		Feedback::success(tr("%0 $word removed", $i));
	} else {
		Feedback::error(tr('No subscriptions removed'));
	}
}

$smarty->assign('nl_info', $info);
if (isset($_REQUEST["remove"]) && $access->checkCsrfForm(tr('Remove subscription?')) ) {
	$result = false;
	if (isset($_REQUEST["email"])) {
		$result = $nllib->remove_newsletter_subscription($_REQUEST["remove"], $_REQUEST["email"], "n");
	} elseif (isset($_REQUEST["subuser"])) {
		$result = $nllib->remove_newsletter_subscription($_REQUEST["remove"], $_REQUEST["subuser"], "y");
	} elseif (isset($_REQUEST["group"])) {
		$result = $nllib->remove_newsletter_group($_REQUEST["remove"], $_REQUEST["group"]);
	} elseif (isset($_REQUEST["included"])) {
		$result = $nllib->remove_newsletter_included($_REQUEST["remove"], $_REQUEST["included"]);
	} elseif (isset($_REQUEST['page'])) {
		$result = $nllib->remove_newsletter_page($_REQUEST['remove'], $_REQUEST['page']);
	}
	if ($result && $result->numRows()) {
		Feedback::success(tr('Subscription removed', $i));
	} else {
		Feedback::error(tr('Subscription not removed'));
	}
}

if (isset($_REQUEST["valid"]) && $access->checkCsrfForm(tr('Mark subscription as valid?'))) {
	if (isset($_REQUEST["email"])) {
		$result = $nllib->valid_subscription($_REQUEST["valid"], $_REQUEST["email"], "n");
		if ($result && $result->numRows()) {
			Feedback::success(tr('Subscription marked as valid'));
		} else {
			Feedback::error(tr('Subscription not marked as valid'));
		}
	} elseif (isset($_REQUEST["subuser"])) {
		$result = $nllib->valid_subscription($_REQUEST["valid"], $_REQUEST["subuser"], "y");
		if ($result && $result->numRows()) {
			Feedback::success(tr('Subscription marked as valid'));
		} else {
			Feedback::error(tr('Subscription not marked as valid'));
		}
	} else {
		Feedback::error(tr('Subscription not marked as valid'));
	}
}

if (isset($_REQUEST["confirmEmail"]) && $_REQUEST["confirmEmail"] == "on") {
	$confirmEmail = "n";
} else {
	$confirmEmail = $info["validateAddr"];
}

if (isset($_REQUEST["addemail"]) && $_REQUEST["addemail"] == "y") {
	$addEmail = "y";
} else {
	$addEmail = "n";
}

$successCount = 0;
$errorCount = 0;
if (isset($_REQUEST["add"]) && $access->checkCsrf()) {
	if (isset($_REQUEST["email"]) && $_REQUEST["email"] != "") {
		if (strpos($_REQUEST["email"], ',')) {
			$emails = explode(',', $_REQUEST["email"]);
			foreach ($emails as $e) {
				if ($userlib->user_exists(trim($e))) {
					$result = $nllib->newsletter_subscribe($_REQUEST["nlId"], trim($e), "y", $confirmEmail, $addEmail);
				} else {
					$result = $nllib->newsletter_subscribe($_REQUEST["nlId"], trim($e), "n", $confirmEmail, "");
				}
			}
		} else {
			$result = $nllib->newsletter_subscribe($_REQUEST["nlId"], trim($_REQUEST["email"]), "n", $confirmEmail, "");
		}
		if ($result) {
			$successCount++;
		} else {
			$errorCount++;
		}
	}
	if (isset($_REQUEST['subuser']) && $_REQUEST['subuser'] != "") {
		$sid = $nllib->newsletter_subscribe($_REQUEST["nlId"], $_REQUEST["subuser"], "y", $confirmEmail, $addEmail);
		if ($sid) {
			$successCount++;
		} else {
			$errorCount++;
		}
	}
	if (isset($_REQUEST["addall"]) && $_REQUEST["addall"] == "on") {
		$result = $nllib->add_all_users($_REQUEST["nlId"], $confirmEmail, $addEmail);
		if ($result) {
			$successCount++;
		} else {
			$errorCount++;
		}
	}
	if (isset($_REQUEST['group']) && $_REQUEST['group'] != "") {
		$result = $nllib->add_group_users(
			$_REQUEST["nlId"], $_REQUEST['group'], $confirmEmail, $addEmail
		);
		if ($result) {
			$successCount++;
		} else {
			$errorCount++;
		}
	}
	if ($errorCount) {
		Feedback::error(tr('Errors encountered when attempting to add subscription'));
	} elseif ($successCount) {
		Feedback::success(tr('Subscription added'));
	}
}
	if ($errorCount) {
		Feedback::error(tr('Errors encountered when attempting to add subscription'));
	} elseif ($successCount) {
		Feedback::success(tr('Subscription added'));
	}

if (((isset($_REQUEST["addbatch"]) && isset($_FILES['batch_subscription']))
		|| (isset($_REQUEST['importPage']) && ! empty($_REQUEST['wikiPageName']))
		|| (isset($_REQUEST['tracker']))) && $tiki_p_batch_subscribe_email == 'y' && $tiki_p_subscribe_email == 'y')
{
	$success = '';
	$error = '';
	$successCount = 0;
	$errorCount = 0;
	if (isset($_REQUEST["addbatch"]) && $access->checkCsrf()) {
		if (! $emails = file($_FILES['batch_subscription']['tmp_name'])) {
			$error = tr('Error opening uploaded file');
		} else {
			$success = tr('File uploaded');
		}
	} elseif (isset($_REQUEST["importPage"]) && $access->checkCsrf()) {
		$emails = $nllib->get_emails_from_page($_REQUEST['wikiPageName']);

		if (! $emails) {
			$error = tr('Error importing from wiki page "%0"', htmlspecialchars($_REQUEST['wikiPageName']));
		} else {
			$success = tr('Wiki page "%0" imported', htmlspecialchars($_REQUEST['wikiPageName']));
		}
	} elseif (isset($_REQUEST['tracker']) && $access->checkCsrf()) {
		$emails = $nllib->get_emails_from_tracker($_REQUEST['tracker']);

		if (! $emails) {
			$error = tr('Error importing from tracker ID %0', (int) $_REQUEST['tracker']);
		} else {
			$success = tr('Tracker ID %0 imported', (int) ($_REQUEST['tracker']));
		}
	}

	foreach ($emails as $email) {
		$email = trim($email);
		if (empty($email)) {
			continue;
		}
		if ($nllib->newsletter_subscribe($_REQUEST["nlId"], $email, 'n', $confirmEmail, 'y')) {
			$successCount++;
		} else {
			$errorCount++;
		}
	}
	if (! empty($error)) {
		Feedback::error($error);
	} else {
		$msg = '';
		if (! empty($success)) {
			$msg = $success;
		}
		if ($errorCount) {
			if ($successCount) {
				$msg .= '. ' . tr('Not all subscriptions created.');
			} else {
				$msg = tr('Subscriptions not created.');
			}
			Feedback::error($msg);
		} elseif ($successCount) {
			Feedback::success($msg . '. ' . tr('Subscriptions created.'));
		}
	}
}

if (isset($_REQUEST["addgroup"]) && isset($_REQUEST['group']) && $_REQUEST['group'] != "" && $access->checkCsrf()) {
	$result = $nllib->add_group($_REQUEST["nlId"], $_REQUEST['group'], isset($_REQUEST['include_groups']) ? 'y' : 'n');
	if ($result && $result->numRows()) {
		Feedback::success(tr('Group "%0" subscribed', htmlspecialchars($_REQUEST['group'])));
	} else {
		Feedback::error(tr('Group "%0" not subscribed', htmlspecialchars($_REQUEST['group'])));
	}
}

if (isset($_REQUEST["addincluded"]) && isset($_REQUEST['included']) && $_REQUEST['included'] != ""
	&& $access->checkCsrf())
{
	$result = $nllib->add_included($_REQUEST["nlId"], $_REQUEST['included']);
	if ($result) {
		Feedback::success(tr('Subscribers added'));
	} else {
		Feedback::error(tr('Subscribers not added'));
	}
}

if (isset($_REQUEST["addPage"]) && ! empty($_REQUEST['wikiPageName']) && $access->checkCsrf()) {
	$result = $nllib->add_page($_REQUEST["nlId"], $_REQUEST['wikiPageName'], empty($_REQUEST['noConfirmEmail']) ? 'y' : 'n', empty($_REQUEST['noSubscribeEmail']) ? 'y' : 'n');
	if ($result && $result->numRows()) {
		Feedback::success(tr('Emails from wiki page "%0" subscribed', htmlspecialchars($_REQUEST['wikiPageName'])));
	} else {
		Feedback::error(tr('Emails from wiki page "%0" not subscribed', htmlspecialchars($_REQUEST['wikiPageName'])));
	}
}

if (isset($_REQUEST["addPage"]) || isset($_REQUEST["addPage"]) || isset($_REQUEST["addincluded"]) ||
		isset($_REQUEST["addgroup"]) || isset($_REQUEST["addbatch"]) || isset($_REQUEST["add"])) {
	$cookietab = 1;
}

if (isset($_REQUEST['export'])) {
	$users = $nllib->get_all_subscribers($_REQUEST['nlId'], 'y');
	$data = "email\n";
	foreach ($users as $u) {
		if (! empty($u['email'])) {
			$data .= $u['email'] . "\n";
		}
	}
	header('Content-type: text/plain');
	header('Content-Disposition: attachment; filename=' . $info['name'] . '.csv');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
	header('Pragma: public');
	echo $data;
	die;
}

if (! isset($_REQUEST["sort_mode"])) {
	$sort_mode = 'subscribed_desc';
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
$channels = $nllib->list_newsletter_subscriptions($_REQUEST["nlId"], $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
$sort_mode_g = (isset($_REQUEST["sort_mode_g"])) ? $_REQUEST["sort_mode_g"] : 'groupName_asc';
$smarty->assign_by_ref('sort_mode_g', $sort_mode_g);
$offset_g = (isset($_REQUEST["offset_g"])) ? $_REQUEST["offset_g"] : 0;
$smarty->assign_by_ref('offset_g', $offset_g);
$find_g = (isset($_REQUEST["find_g"])) ? $_REQUEST["find_g"] : '';
$smarty->assign('find_g', $find_g);
$groups_g = $nllib->list_newsletter_groups($_REQUEST["nlId"], $offset_g, $maxRecords, $sort_mode_g, $find_g);
$cant_pages_g = ceil($groups_g["cant"] / $maxRecords);
$smarty->assign_by_ref('cant_pages_g', $cant_pages_g);
$smarty->assign('actual_page_g', 1 + ($offset_g / $maxRecords));

if ($groups_g["cant"] > ($offset_g + $maxRecords)) {
	$smarty->assign('next_offset_g', $offset_g + $maxRecords);
} else {
	$smarty->assign('next_offset_g', -1);
}

if ($offset_g > 0) {
	$smarty->assign('prev_offset_g', $offset_g - $maxRecords);
} else {
	$smarty->assign('prev_offset_g', -1);
}

$smarty->assign_by_ref('groups_g', $groups_g["data"]);
$smarty->assign("nb_groups", $groups_g["cant"]);
$included_n = $nllib->list_newsletter_included($_REQUEST["nlId"], 0, -1);
$smarty->assign('included_n', $included_n);
$smarty->assign('nb_included', count($included_n));
$pages = $nllib->list_newsletter_pages($_REQUEST["nlId"], 0, -1);
$smarty->assign('pages', $pages['data']);
$smarty->assign('nb_pages', $pages['cant']);

$groups = $userlib->list_all_groups();
$smarty->assign_by_ref('groups', $groups);
$users = $userlib->list_all_users();
foreach ($channels['data'] as $aUser) {
	foreach ($users as $iId => $sEmail) {
		if ($aUser['email'] === $sEmail) {
			unset($users[$iId]);
		}
	}
}
$smarty->assign_by_ref('users', $users);
$newsletters = $nllib->list_newsletters(0, -1, "created_desc", false, '', '', 'n');
$smarty->assign_by_ref('newsletters', $newsletters['data']);

if (isset($tiki_p_admin_trackers) && $tiki_p_admin_trackers == 'y') {
	$trklib = TikiLib::lib('trk');
	$listTrackers = $trklib->list_trackers();
	$smarty->assign_by_ref('listTrackers', $listTrackers['data']);
}

include_once('tiki-section_options.php');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-admin_newsletter_subscriptions.tpl');
$smarty->display("tiki.tpl");
