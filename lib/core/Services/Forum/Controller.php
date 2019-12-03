<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

/**
 * Class Services_Forum_Controller
 */
class Services_Forum_Controller
{
	/**
	 * @var Comments
	 */
	private $lib;

	/**
	 * Filters for $input->replaceFilters() used in the Services_Utilities()->setVars method
	 *
	 * @var array
	 */
	private $filters = [
		'forumId'			=> 'digits',
		'comments_parentId'	=> 'digits',
		'toId'				=> 'digits',
		'remove_attachment'	=> 'digits',
	];

	function setUp()
	{
		Services_Exception_Disabled::check('feature_forums');
		$this->lib = TikiLib::lib('comments');
	}

	/**
	 * Admin forums "perform with checked" but with no action selected
	 *
	 * @param $input
	 * @throws Exception
	 * @throws Services_Exception
	 */
	public function action_no_action()
	{
		Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
	}

	/**
	 * Moderator action that locks a forum topic
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_lock_topic($input)
	{
		return $this->lockUnlock($input, 'lock');
	}

	/**
	 * Moderator action that unlocks a forum topic
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_unlock_topic($input)
	{
		return $this->lockUnlock($input, 'unlock');
	}

	/**
	 * Moderator action to merge selected forum topics or posts with another topic
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_merge_topic($input)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'forumtopic');
			//check number of topics on first pass
			if ($util->itemsCount > 0) {
				$util->items = $this->getTopicTitles($util->items);
				$toList = $this->lib->get_forum_topics($forumId, 0, -1);
				$toList = array_column($toList, 'title', 'threadId');
				$diff = array_diff_key($toList, $util->items);
				if (count($diff) > 0) {
					$object = count($util->items) > 1 ? 'topics' : 'topic';
					if (isset($input['comments_parentId'])) {
						unset($diff[$input['comments_parentId']]);
						$title = tr('Merge selected posts with another topic');
						$customMsg = count($util->items) === 1 ? tra('Merge this post:') : tra('Merge these posts:');
					} else {
						$title = tr('Merge selected topics with another topic');
						$customMsg = count($util->items) === 1 ? tra('Merge this topic:') : tra('Merge these topics:');
					}
					return [
						'FORWARD' => [
							'controller' => 'access',
							'action' => 'confirm_select',
							'confirmAction' => $util->action,
							'confirmController' => 'forum',
							'confirmButton' => tra('Merge'),
							'customMsg' => $customMsg,
							'toMsg' => tra('With this topic:'),
							'title' => $title,
							'items' => $util->items,
							'extra' => ['referer' => Services_Utilities::noJsPath()],
							'toList' => $diff,
							'object' => $object,
							'modal' => '1',
						]
					];
				} else {
					Services_Utilities::modalException(tra('All topics or posts were selected, leaving none to merge with. Please make your selection again.'));
				}
			} else {
				Services_Utilities::modalException(tra('No topics were selected. Please select the topics you wish to merge before clicking the merge button.'));
			}
		//second pass - after popup modal form has been submitted
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//perform merge
			$toId = $input['toId'];
			foreach ($util->items as $id => $topic) {
				if ($id !== $toId) {
					$this->lib->set_parent($id, $toId);
				}
			}
			$toComment = $this->getTopicTitles([$toId]);
			//prepare feedback
			if ($util->itemsCount == 1) {
				$msg = tr('The following post has been merged with the %0 topic:', $toComment[$toId]);
			} else {
				$msg = tr('The following posts have been merged with the %0 topic:', $toComment[$toId]);
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Moderator action to move one or more topics
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_move_topic($input)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'forumtopic');
			//check number of topics on first pass
			if ($util->itemsCount > 0) {
				$items = $this->getTopicTitles($util->items);
				$all_forums = $this->lib->list_forums(0, -1, 'name_asc', '');
				foreach ($all_forums['data'] as $key => $forum) {
					if ($this->lib->admin_forum($forum['forumId'])) {
						$toList[$forum['forumId']] = $forum['name'];
					}
				}
				$fromName = $toList[$forumId];
				unset($toList[$forumId]);
				$customMsg = count($items) === 1 ? tra('Move this topic:') : tra('Move these topics:');
				$toMsg = tr('From the %0 forum to the below forum:', $fromName);
				return [
					'FORWARD' => [
						'controller' => 'access',
						'action' => 'confirm_select',
						'title' => tra('Move selected topics to another forum'),
						'confirmAction' => $input['action'],
						'confirmController' => 'forum',
						'confirmButton' => tra('Move'),
						'customMsg' => $customMsg,
						'toMsg' => $toMsg,
						'toList' => $toList,
						'items' => $items,
						'extra' => [
							'id' => $forumId,
							'referer' => Services_Utilities::noJsPath()
						],
						'modal' => '1',
					]
				];
			} else {
				Services_Utilities::modalException(tra('No topics were selected. Please select the topics you wish to move before clicking the move button.'));
			}
		//second pass - after popup modal form has been submitted
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//perform topic move
			$toId = $input['toId'];
			foreach ($util->items as $id => $topic) {
				// To move a topic you just have to change the object
				$obj = 'forum:' . $toId;
				$this->lib->set_comment_object($id, $obj);
				// update the stats for the source and destination forums
				$this->lib->forum_prune($util->extra['forumId']);
				$this->lib->forum_prune($toId);
			}
			//prepare feedback
			$toName = $util->toList[$toId];
			if ($util->itemsCount == 1) {
				$msg = tr('The following topic has been moved to the %0 forum:', $toName);
			} else {
				$msg = tr('The following topics have been moved to the %0 forum:', $toName);
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Moderator action to delete one or more topics
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_delete_topic($input)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'forumtopic');
			//check number of topics on first pass
			if ($util->itemsCount > 0) {
				$util->items = $this->getTopicTitles($util->items);
				if (isset($input['comments_parentId'])) {
					$object = count($util->items) > 1 ? 'posts' : 'post';
				} else {
					$object = count($util->items) > 1 ? 'topics' : 'topic';
				}
				$msg = tr('Delete the following forum %0?', $object);
				return $util->confirm($msg, tra('Delete'), ['forumId' => $forumId]);
			} else {
				Services_Utilities::modalException(tra('No topics were selected. Please select the topics you wish to delete before clicking the delete button.'));
			}
		//second pass - after popup modal form has been submitted
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//perform delete
			foreach ($util->items as $id => $name) {
				if (is_numeric($id)) {
					$this->lib->remove_comment($id);
				}
			}
			$this->lib->forum_prune($util->extra['forumId']);
			//prepare feedback
			if ($util->itemsCount == 1) {
				$msg = tra('The following topic has been deleted:');
			} else {
				$msg = tra('The following topics have been deleted:');
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			if ($this->lib->count_comments('forum:' . $util->extra['forumId']) > 0) {
				return Services_Utilities::refresh($util->extra['referer']);
			} else {
				global $base_url;
				return Services_Utilities::redirect($base_url . 'tiki-forums.php' . $util->extra['anchor']);
			}
		}
	}

	/**
	 * Moderator action to delete a forum post attachment
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_delete_attachment($input)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters);
			if (isset($input['remove_attachment'])) {
				$util->items[$input['remove_attachment']] = $input['filename'];
				$msg = tra('Delete the following attachment?');
				return $util->confirm($msg, tra('Delete'));
			} else {
				Services_Utilities::modalException(tra('No attachments were selected. Please select an attachment to delete.'));
			}
		//second pass - after popup modal form has been submitted
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//perform attachment delete
			foreach ($util->items as $id => $name) {
				if (is_numeric($id)) {
					$this->lib->remove_thread_attachment($id);
				}
			}
			//prepare feedback
			if ($util->itemsCount == 1) {
				$msg = tra('The following attachment has been deleted:');
			} else {
				$msg = tra('The following attachments have been deleted:');
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Moderator action that archives a forum thread
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_archive_topic($input)
	{
		return $this->archiveUnarchive($input, 'archive');
	}

	/**
	 * Moderator action that archives a forum thread
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_unarchive_topic($input)
	{
		return $this->archiveUnarchive($input, 'unarchive');
	}

	/**
	 * Action to delete one or more forums
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 */
	function action_delete_forum($input)
	{
		$util = new Services_Utilities();
		$util->setVars($input, $this->filters, 'checked');
		$perms = Perms::get('forum', $util->items);
		if (! $perms->admin_forum) {
			throw new Services_Exception_Denied(tr('Reserved for forum administrators'));
		}
		if ($util->notConfirmPost()) {
			//check number of topics on first pass
			if ($util->itemsCount > 0) {
				$forumsNumber = $this->countSubForums($util->items);
				$util->items = $this->getForumNames($util->items);
				if (count($util->items) === 1) {
					$msg = tra('Delete the following forum?');
					if ($forumsNumber) {
						$msg .= tr('This forum has sub-forums, you must delete the included forums first.');
					}
				} else {
					$msg = tra('Delete the following forums?');
					if ($forumsNumber) {
						$msg .= tr('Some of these forums have sub-forums, you must delete the included forums first.');
					}
				}
				return $util->confirm($msg, tra('Delete'));
			} else {
				Services_Utilities::modalException(tra('No forums were selected. Please select a forum to delete.'));
			}
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			foreach ($util->items as $id => $name) {
				if (is_numeric($id)) {
					$this->lib->remove_forum($id);
				}
			}
			//prepare feedback
			if ($util->itemsCount === 1) {
				$msg = tra('The following forum has been deleted:');
			} else {
				$msg = tra('The following forums have been deleted:');
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer'], 'queryAndAnchor');
		}
	}

	private function checkPerms($forumId)
	{
		$perm = $this->lib->admin_forum($forumId);
		if (! $perm) {
			throw new Services_Exception_Denied(tr('Reserved for forum administrators and moderators'));
		}
	}

	/**
	 * Utility to get topic names
	 *
	 * @param $topicIds
	 * @return mixed
	 * @throws Exception
	 */
	private function getTopicTitles(array $topicIds)
	{
		foreach ($topicIds as $id) {
			$info = $this->lib->get_comment((int) $id);
			if (! empty($info['title'])) {
				$ret[(int) $id] = $info['title'];
			} else {
				$ret[(int) $id] = TikiLib::lib('tiki')->get_snippet($info['data'], "", false, "", 60);
			}
		}
		return $ret;
	}

	/**
	 * Utility to get forum names
	 *
	 * @param $forumIds
	 * @return mixed
	 * @throws Exception
	 */
	private function getForumNames(array $forumIds)
	{
		foreach ($forumIds as $id) {
			$info = $this->lib->get_forum((int) $id);
			$ret[(int) $id] = $info['name'];
		}
		return $ret;
	}

	/**
	 * Utility to count sub forums in a specified forum
	 *
	 * @param $forumIds
	 * @return mixed
	 * @throws Exception
	 */
	private function countSubForums(array $forumIds)
	{
		$forumNumbers = 0;
		foreach ($forumIds as $id) {
			$info = $this->lib->get_sub_forums((int) $id);
			$forumNumbers = count($info);
			if ($forumNumbers > 0) {
				break;
			}
		}
		return $forumNumbers;
	}


	/**
	 * Utility used by action_lock_topic and action_unlock_topic since the code for both is similar
	 * @param $input
	 * @param $type
	 * @return array
	 * @throws Exception
	 */
	private function lockUnlock($input, $type)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'forumtopic');
			//check number of topics on first pass
			if ($util->itemsCount > 0) {
				$util->items = $this->getTopicTitles($util->items);
				//tra('Lock') tra('Unlock')
				$transtype = tra(ucfirst($type));
				if (count($util->items) === 1) {
					$msg = tr('%0 the following topic?', $transtype);
				} else {
					$msg = tr('%0 the following topics?', $transtype);
				}
				return $util->confirm($msg, $transtype);
			} else {
				Services_Utilities::modalException(tr('No topics were selected. Please select the topics you wish to %0 before clicking the %0 button.', tra($type)));
			}
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			$fn = $type . '_comment';
			//do the locking/unlocking
			foreach ($util->items as $id => $topic) {
				$this->lib->$fn($id);
			}
			//prepare feedback
			$typedone = $type == 'lock' ? tra('locked') : tra('unlocked');
			if ($util->itemsCount == 1) {
				$msg = tr('The following topic has been %0:', $typedone);
			} else {
				$msg = tr('The following topics have been %0:', $typedone);
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Utility used by action_archive_topic and action_unarchive_topic since the code for both is similar
	 * @param $input
	 * @param $type
	 * @return array
	 * @throws Exception
	 */
	private function archiveUnarchive($input, $type)
	{
		$forumId = $input['forumId'];
		$this->checkPerms($forumId);
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters);
			if ($input['comments_parentId']) {
				$topicId = $input['comments_parentId'];
				$util->items = $this->getTopicTitles([$topicId]);
				return [
					'FORWARD' => [
						'controller' => 'access',
						'action' => 'confirm',
						'confirmAction' => $type . '_topic',
						'confirmController' => 'forum',
						//tra('Archive') tra('Unarchive')
						'customMsg' => tr('%0 the following thread?', tra(ucfirst($type))),
						'customObject' => tra('thread'),
						'items' => $util->items,
						'extra' => [
							'comments_parentId' => $topicId,
							'referer' => Services_Utilities::noJsPath()
						],
						'modal' => '1',
					]
				];
			} else {
				Services_Utilities::modalException(tr('No threads were selected. Please select the threads you wish to %0.', tra($type)));
			}
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//perform archive/unarchive
			$fn = $type . '_thread';
			$this->lib->$fn($util->extra['comments_parentId']);
			//prepare feedback
			$typedone = $type == 'archive' ? tra('archived') : tra('unarchived');
			if ($util->itemsCount == 1) {
				$msg = tr('The following thread has been %0:', $typedone);
			} else {
				$msg = tr('The following thread have been %0:', $typedone);
			}
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $util->items,
			];
			Feedback::success($feedback);
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}
}
