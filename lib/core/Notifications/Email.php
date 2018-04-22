<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Notifications;

require_once 'lib/notifications/notificationemaillib.php';

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
		if (! in_array($type, ['forum', 'blog post'])) {
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
		$headers['In-Reply-To'] = ! empty($parentInfo['Message-Id']) ? $parentInfo['Message-Id'] : $hash;
		$headers['References'] = ! empty($parentInfo['References']) ? $headers['In-Reply-To'] . ', ' . $parentInfo['References'] : $hash;

		return $headers;
	}

	/**
	 * Send email notification to tiki admins regarding scheduler run status (stalled/healed)
	 *
	 * @param string 			$subjectTpl	Email subject template file path
	 * @param string 			$txtTpl		Email body template file path
	 * @param \Scheduler_Item	$scheduler	The scheduler that if being notified about.
	 *
	 * @return int The number of sent emails
	 */
	public static function sendSchedulerNotification($subjectTpl, $txtTpl, $scheduler)
	{
		global $prefs;

		$smarty = \TikiLib::lib('smarty');
		$smarty->assign('schedulerName', $scheduler->name);
		$smarty->assign('stalledTimeout', $prefs['scheduler_stalled_timeout']);

		// Need to fetch users with email address listed
		$userlib = \TikiLib::lib('user');
		$adminUsers = $userlib->get_group_users('Admins', 0, -1, '*');

		return sendEmailNotification($adminUsers, null, $subjectTpl, null, $txtTpl);
	}
}
