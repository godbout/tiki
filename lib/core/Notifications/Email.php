<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Notifications;

require_once __DIR__ . '/../../notifications/notificationemaillib.php';

class Email
{
	/**
	 * Fetch email headers to enable threading
	 *
	 * @param $type
	 *   Tiki object type (forum, blog post)
	 * @param $commentId
	 *   The comment id
	 * @param null $messageId
	 *   The comment message_id
	 * @return array
	 *   Return an array with the headers to enable threading or empty array if object not supported.
	 * @throws \Exception
	 */
	public static function getEmailThreadHeaders($type, $commentId, $messageId = null)
	{

		if ($type == 'blog') {
			$type = 'blog post';
		}

		//Only support Forum/Blog Post comments
		if (! in_array($type, ['forum', 'blog post', 'trackeritem'])) {
			return [];
		}

		/** @var \Comments $commentsLib */
		$commentsLib = \TikiLib::lib('comments');
		$comment = $commentsLib->get_comment($commentId, $messageId);

		$headers = [];
		$parentInfo = [];
		if ($comment['parentId']) {
			$parentInfo = self::getEmailThreadHeaders($type, $comment['parentId'], $comment['in_reply_to']);
		}

		$headers['Message-Id'] = $comment['message_id'];

		$hash = md5($comment['objectType'] . '.' . $comment['object']) . '@' . $_SERVER["SERVER_NAME"];

		$headers['In-Reply-To'] = ! empty($parentInfo['Message-Id']) ? $parentInfo['Message-Id'] : null;
		$headers['References'] = ! empty($parentInfo['References']) ? $parentInfo['References'] . ' ' . $headers['In-Reply-To'] : $hash;

		if (empty($headers['In-Reply-To'])) {
			unset($headers['In-Reply-To']);
		}

		return $headers;
	}

	/**
	 * Send email notification to tiki admins regarding scheduler run status (stalled/healed)
	 *
	 * @param string 			$subjectTpl		Email subject template file path
	 * @param string 			$txtTpl			Email body template file path
	 * @param \Scheduler_Item	$scheduler		The scheduler that if being notified about.
	 * @param array				$usersToNotify	An array of users to be notified
	 *
	 * @return int The number of sent emails
	 * @throws \Exception
	 */
	public static function sendSchedulerNotification($subjectTpl, $txtTpl, $scheduler, $usersToNotify = [])
	{
		$tikilib = \TikiLib::lib('tiki');
		$smarty = \TikiLib::lib('smarty');

		$smarty->assign('schedulerName', $scheduler->name);
		$smarty->assign('siteName', $tikilib->get_preference('browsertitle'));
		$smarty->assign('stalledTimeout', $tikilib->get_preference('scheduler_stalled_timeout'));
		$smarty->assign('healingTimeout', $tikilib->get_preference('scheduler_healing_timeout'));

		return sendEmailNotification($usersToNotify, null, $subjectTpl, null, $txtTpl);
	}

	/**
	 * Send email notification to tiki users when they are mentioned in tiki pages
	 *
	 * @param $subjectTpl
	 * @param $txtTpl
	 * @param $info
	 * @param $userToNotify
	 * @return int
	 * @throws \Exception
	 */
	public static function sendMentionNotification($subjectTpl, $txtTpl, $info, $userToNotify = [])
	{
		$tikilib = \TikiLib::lib('tiki');
		$smarty = \TikiLib::lib('smarty');

		$foo = parse_url($_SERVER['REQUEST_URI']);
		$machine = $tikilib->httpPrefix(true) . dirname($foo['path']);

		$smarty->assign('siteName', $info['siteName']);
		$smarty->assign('mentionedBy', $info['mentionedBy']);
		$smarty->assign('section', $machine . '/' . $info['section']);

		return sendEmailNotification($userToNotify, null, $subjectTpl, null, $txtTpl);
	}
}
