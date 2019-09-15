<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
	die('This script may only be included.');
}

if (isset($_REQUEST['pollVote']) && ! empty($_REQUEST['polls_pollId'])) {
	$ok = true;
	$voted = false;
	if (empty($_REQUEST['polls_optionId'])) {
		$ok = false;
		$error = tra('You must choose an option');
	} elseif ($tiki_p_vote_poll == 'y' && ($prefs['feature_poll_anonymous'] == 'y' || $user || $prefs['feature_antibot'] == 'y')) {
		$captchalib = TikiLib::lib('captcha');
		if (empty($user) && empty($_COOKIE)) {
			$ok = false;
			$error = tra('For you to vote, cookies must be allowed');
			$smarty->assign_by_ref('polls_optionId', $_REQUEST['polls_optionId']);
		} elseif (($prefs['feature_antibot'] == 'y' && empty($user)) && (! $captchalib->validate())) {
			$ok = false;
			$errors = $captchalib->getErrors();
			$smarty->assign_by_ref('polls_optionId', $_REQUEST['polls_optionId']);
		} else {
			$polllib = TikiLib::lib('poll');
			$poll = $polllib->get_poll($_REQUEST['polls_pollId']);
			if (empty($poll) || $poll['active'] == 'x') {
				$ok = false;
				$error = tra('This poll is closed.');
				$smarty->assign_by_ref('polls_optionId', $_REQUEST['polls_optionId']);
			} else {
				$previous_vote = $polllib->get_user_vote('poll' . $_REQUEST['polls_pollId'], $user);
				if ($tikilib->register_user_vote($user,
					'poll' . $_REQUEST['polls_pollId'],
					$_REQUEST['polls_optionId'],
					[],
					$prefs['feature_poll_revote'] == 'y'
					)
					&& $access->checkCsrf())
				{
					$result = $polllib->poll_vote($user, $_REQUEST['polls_pollId'], $_REQUEST['polls_optionId'], $previous_vote);
					if ($result) {
						if ($result === true) {
							Feedback::note(tr('Your vote for this option has already been recorded'));
						} elseif ($result->numRows()) {
							Feedback::success(tr('Vote recorded'));
						}
					} else {
						Feedback::error(tr('Vote not recorded'));
					}
				}
			}
		}
	}
	if (! empty($error)) {
		Feedback::error($error);
	}
	if ($ok && ! isset($_REQUEST['wikipoll']) && $tiki_p_view_poll_results == 'y' && empty($_REQUEST['showresult'])) {
		header('location: tiki-poll_results.php?pollId=' . $_REQUEST['polls_pollId']);
		die;
	}
}
