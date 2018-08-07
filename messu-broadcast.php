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
$inputConfiguration = [[
	'staticKeyFilters'	=> [
		'body'			=> 'text',
		'groupbr'		=> 'groupname',
		'priority'		=> 'digits',
		'replyto_hash'	=> 'alnumdash',
		'preview'		=> 'alphaspace',
		'send'			=> 'alphaspace',
		'subject'		=> 'text',
	],
	'catchAllUnset' => null,
]];
require_once('tiki-setup.php');
$messulib = TikiLib::lib('message');
$access->check_user($user);
$access->check_feature('feature_messages');
$auto_query_args = ['subject', 'body', 'priority', 'replyto_hash', 'groupbr'];

if (! isset($_POST['subject'])) {
	$_POST['subject'] = '';
}
if (! isset($_POST['body'])) {
	$_POST['body'] = '';
}
if (! isset($_POST['priority'])) {
	$_POST['priority'] = 3;
}
if (! isset($_POST['replyto_hash'])) {
	$_POST['replyto_hash'] = '';
}
$smarty->assign('subject', $_POST['subject']);
$smarty->assign('body', $_POST['body']);
$smarty->assign('priority', $_POST['priority']);
$smarty->assign('replyto_hash', $_POST['replyto_hash']);
$smarty->assign('mid', 'messu-broadcast.tpl');
$smarty->assign('sent', 0);
perm_broadcast_check($access, $userlib);
$groups = $userlib->get_user_groups($user);

if (in_array('Admins', $groups)) {
	//admins can write to members of all groups
	$groups = $userlib->list_all_groups();
	$groups = array_diff($groups, ['Registered', 'Anonymous']);
} else {
	//registered users can write to members of groups they belong to
	$groups = array_diff($groups, ['Registered', 'Anonymous']);
}

$smarty->assign('groups', $groups);

if ((isset($_POST['send']) && $access->checkCsrf()) || isset($_POST['preview'])) {
	$message = [];
	// Validation:
	// must have a subject or body non-empty (or both)
	if (empty($_POST['subject']) && empty($_POST['body'])) {
		Feedback::error(tra('The message must have either a subject or a body.'));
	} else {
		// Remove invalid users from the to, cc and bcc fields
		if (isset($_POST['groupbr'])) {
			if ($_POST['groupbr'] == 'all' && $tiki_p_broadcast_all == 'y') {
				$a_all_users = $userlib->get_users(0, -1, 'login_desc', '');
				$all_users = [];
				foreach ($a_all_users['data'] as $a_user) {
					$all_users[] = $a_user['user'];
				}
			} elseif (in_array($_POST['groupbr'], $groups)) {
				$all_users = $userlib->get_group_users($_POST['groupbr']);
			} else {
				$access->display_error('', tra("You do not have the permission that is needed to use this feature") . ": " . $permission, '403', false);
			}
			$smarty->assign('groupbr', $_POST['groupbr']);
		}

		$users = [];
		asort($all_users);
		foreach ($all_users as $a_user) {
			if (! empty($a_user)) {
				if ($userlib->user_exists($a_user)) {
					if (! $userlib->user_has_permission($a_user, 'tiki_p_messages')) {
						$message[] = sprintf(tra('User %s does not have the permission'), $a_user);
					} elseif ($tikilib->get_user_preference($a_user, 'allowMsgs', 'y') == 'y') {
						$users[] = $a_user;
					} else {
						$message[] = sprintf(tra("User %s does not want to receive messages"), $a_user);
					}
				} else {
					$message[] = tra("Invalid user") . "$a_user";
				}
			}
		}
		$users = array_unique($users);
		// Validation: either to, cc or bcc must have a valid user
		if (count($users) > 0) {
			if (isset($_POST['send'])) {
				$smarty->assign('sent', 1);
				$message[] = tra('The message has been sent to:') . ' ' . implode(', ', $users);
				// Insert the message in the inboxes of each user
				foreach ($users as $a_user) {
					$messulib->post_message($a_user, $user, $a_user, '', $_POST['subject'], $_POST['body'], $_POST['priority']);
					// if this is a reply flag the original messages replied to
					if ($_POST['replyto_hash'] <> '') {
						$messulib->mark_replied($a_user, $_POST['replyto_hash']);
					}
				}
				// Insert a copy of the message in the sent box of the sender
				$messulib->save_sent_message($user, $user, $_POST['groupbr'], null, $_POST['subject'], $_POST['body'], $_POST['priority'], $_POST['replyto_hash']);
				Feedback::success(['mes' => $message]);
				if ($prefs['feature_actionlog'] == 'y') {
					$logslib->add_action('Posted', '', 'message', 'add=' . strlen($_POST['body']));
				}
			} elseif (isset($_POST['preview'])) {
				$message[] = tra('The message will be sent to:') . ' ' . implode(', ', $users);
				$smarty->assign('confirm_detail', $message);
				$smarty->assign('confirmSubmitName', 'send');
				$smarty->assign('confirmSubmitValue', 1);
				unset($_POST['preview']);
				$access->checkCsrfForm(tra('See below for how the broadcast message will be handled upon confirmation'));
			}
		} else {
			$message[] = tra('No valid users to send the message to.');
			Feedback::error(['mes' => $message]);
		}
	}
}
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->display("tiki.tpl");

//TODO Seems to just check whether any group has broadcast permission. Not sure why regular perm checking wouldn't work
function perm_broadcast_check($access, $userlib)
{
//check permissions
	$groups_perm = $userlib->list_all_groups();
	$groups_perm = array_diff($groups_perm, ['Anonymous']);
	$groups_perm = array_filter(
		$groups_perm,
		function ($groupName) {
			$perms = Perms::get('group', $groupName);
			return $perms->broadcast;
		}
	);

	if (empty($groups_perm)) {
		$access->display_error('', tra("You do not have the permission that is needed to use this feature") . ": " . $permission, '403', false);
		exit;
	}
}
