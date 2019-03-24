<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'mytiki';
require_once('tiki-setup.php');

$auto_query_args = ['userId', 'view_user'];

$access->check_user($user);
$access->check_feature('feature_user_watches');

$headerlib->add_map();

if ($access->checkCsrf()) {
	if (isset($_REQUEST['user_calendar_watch_editor']) && $_REQUEST['user_calendar_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_article_watch_editor']) && $_REQUEST['user_article_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_article_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_article_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_wiki_watch_editor']) && $_REQUEST['user_wiki_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_blog_watch_editor']) && $_REQUEST['user_blog_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_tracker_watch_editor']) && $_REQUEST['user_tracker_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_comment_watch_editor']) && $_REQUEST['user_comment_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_category_watch_editor']) && $_REQUEST['user_category_watch_editor'] == true) {
		$result[] = $tikilib->set_user_preference($user, 'user_category_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_category_watch_editor', 'n');
	}
	if (isset($_REQUEST['user_plugin_approval_watch_editor'])
		&& $_REQUEST['user_plugin_approval_watch_editor'] == true)
	{
		$result[] = $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'y');
	} else {
		$result[] = $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'n');
	}
	if (! in_array(false, $result)) {
		Feedback::success(tr('Notification preferences set'));
	} else {
		Feedback::error(tr('Errors were encountered when setting notification preferences'));
	}
}

header('Location: tiki-user_watches.php');
die;
