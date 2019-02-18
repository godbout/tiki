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

$auto_query_args = ['types_section', 'types', 'show_types', 'sort_mode', 'offset', 'find', 'findfilter_approved'];

if (isset($_REQUEST['blogId'])) {
	$bloglib = TikiLib::lib('blog');
	$blogId = $_REQUEST['blogId'];
	$access->check_feature('feature_blogs');
	$bloglib->check_blog_exists($blogId);
	$tikilib->get_perm_object('blog', $blogId);

	if ($tiki_p_blog_admin != 'y') {
		$smarty->assign('msg', tra('You do not have permission to view the comments for this blog'));
		$smarty->display('error.tpl');
		die;
	}
} else {
	$access->check_permission('tiki_p_admin_comments');
}

$commentslib = TikiLib::lib('comments');
$title = tra('Comments');
$sections_keys = ['objectType' => 'commentsFeature', 'itemObjectType' => 'itemCommentsFeature'];

if (isset($blogId)) {
	$title .= ' - ' . $bloglib->get_title($blogId);
} elseif (isset($_REQUEST['types_section']) && isset($sections_enabled[$_REQUEST['types_section']])) {
	// types_section is used to limit the user to only one section (e.g. 'blogs')
	$title = $title . ' - ' . tra(ucwords($_REQUEST['types_section']));
	$smarty->assign_by_ref('types_section', $_REQUEST['types_section']);
}

if (isset($_REQUEST['types'])) {
	$requested_types = $_REQUEST['types'];
	$default_list_value = 'n';
} else {
	$requested_types = [];
	$default_list_value = 'y';
}
$smarty->assign_by_ref('title', $title);

$show_types = [];
$selected_types = [];
foreach ($sections_enabled as $k => $info) {
	if (isset($_REQUEST['types_section']) && $k != $_REQUEST['types_section']) {
		continue;
	}
	// The logic below obviously does not work for tracker comments, so let's handle them in a way that is simpler to understand
	if ($k == 'trackers' && $prefs['feature_trackers'] == 'y') {
		$show_types['trackeritem'] = 'Tracker Item';
		if ($default_list_value == 'y' || in_array('trackeritem', $requested_types)) {
			$selected_types[] = 'trackeritem';
		}
		continue;
	}
	foreach ($sections_keys as $stype => $sfeature) {
		if (isset($info[$sfeature]) && $prefs[$info[$sfeature]] == 'y' && isset($info[$stype])) {
			$comment_type = $info[$stype];
			$show_types[$comment_type] = ucwords($comment_type);
			if ($default_list_value == 'y' || in_array($comment_type, $requested_types)) {
				$selected_types[] = $comment_type;
			}
		}
	}
}

// No need to show types choices if there is only one choice that is already choosed
if (count($show_types) == 1 && count($selected_types) == 1) {
	$show_types = [];
}

$headers = ['title' => 'Title', 'objectType' => 'Type', 'object' => 'Object', 'userName' => 'Author', 'commentDate' => 'Date', 'data' => 'Comment',];
$more_info_headers = ['user_ip' => tra('IP'), 'email' => tra('Email'), 'website' => tra('Website')];

if (count($selected_types) == 1) {
	unset($headers['objectType']);
	$headers['object'] = tra(ucwords($selected_types[0]));
}

$smarty->assign_by_ref('show_types', $show_types);
$smarty->assign_by_ref('selected_types', $selected_types);
$smarty->assign_by_ref('headers', $headers);
$smarty->assign_by_ref('more_info_headers', $more_info_headers);

// Handle actions
if (isset($_REQUEST['checked'])) {
	$checked = is_array($_REQUEST['checked']) ? $_REQUEST['checked'] : [$_REQUEST['checked']];
	if (isset($_REQUEST['action'])) {
		// Delete comment(s)
		// Use $_REQUEST and confirmation form because a link can generate this request if js is not enabled
		if ($_REQUEST['action'] === 'remove' && $access->checkCsrfForm(tra('Delete selected comments?'))) {
			foreach ($checked as $id) {
				$commentslib->remove_comment($id);
			}
			$msg = count($checked) === 1 ? tra('The following comment has been deleted:')
				: tra('The following comments have been deleted:');
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $checked,
			];
			Feedback::success($feedback);
		}
		// Ban IP addresses of multiple spammers
		if ($_POST['action'] === 'ban') {
			$mass_ban_ip = implode('|', $checked);
			header('Location: tiki-admin_banning.php?mass_ban_ip=' . $mass_ban_ip);
			exit;
		}
		// Ban IP addresses of multiple spammers and remove comments
		if ($_POST['action'] === 'ban_remove' && $access->checkCsrfForm(tra('Delete selected comments and ban?'))) {
			foreach ($checked as $id) {
				$commentslib->remove_comment($id);
			}
			$msg = count($checked) === 1 ? tra('The following comment has been deleted:')
				: tra('The following comments have been deleted:');
			$feedback = [
				'tpl' => 'action',
				'mes' => $msg,
				'items' => $checked,
				'toMsg' => tr('Users have been pre-selected for banning in the highlighted section of the form below.')
			];
			Feedback::success($feedback);
			$mass_ban_ip = implode('|', $checked);
			header('Location: tiki-admin_banning.php?mass_ban_ip=' . $mass_ban_ip);
			exit;
		}
		// Approve comment(s)
		if ($_POST['action'] === 'approve' && $prefs['feature_comments_moderation'] == 'y' && $access->checkCsrf()) {
			$approvedCount = 0;
			foreach ($checked as $id) {
				$result = $commentslib->approve_comment($id, 'y');
				if ($result) {
					$approvedCount++;
				}
			}
			if ($approvedCount) {
				if ($approvedCount === 1) {
					Feedback::success(tr('One comment approved'));
				} else {
					Feedback::success(tr('%0 comments approved', $approvedCount));
				}
			} else {
				Feedback::error(tr('No comments approved'));
			}
		}
		// Reject comment(s)
		if ($_POST['action'] === 'reject' && $prefs['feature_comments_moderation'] == 'y' && $access->checkCsrf()) {
			$rejectedCount = 0;
			foreach ($checked as $id) {
				$result = $commentslib->approve_comment($id, 'r');
				$rejected[$id] = $result;
				if ($result) {
					$rejectedCount++;
				}
			}
			$smarty->assign_by_ref('rejected', $rejected);
			if ($rejectedCount) {
				if ($rejectedCount === 1) {
					Feedback::success(tr('One comment rejected'));
				} else {
					Feedback::success(tr('%0 comments rejected', $rejectedCount));
				}
			} else {
				Feedback::error(tr('No comments rejected'));
			}
		}
		// Archive comment(s)
		// Use $_REQUEST and confirmation form because a link can generate this request if js is not enabled
		if ($_REQUEST['action'] === 'archive' && $prefs['comments_archive'] == 'y'
			&& $access->checkCsrf())
		{
			$i = 0;
			foreach ($checked as $id) {
				$result = $commentslib->archive_thread($id);
				if ($result && $result->numRows()) {
					$i++;
				}
			}
			if ($i) {
				Feedback::success($i === 1 ? tr('Comment archived') : tr('%0 comments archived', $i));
			} else {
				Feedback::error(tr('No comments archived'));
			}
		}
		// Unarchive comment(s)
		if ($_REQUEST['action'] === 'unarchive' && $prefs['comments_archive'] == 'y'
			&& $access->checkCsrf())
		{
			$i2 = 0;
			foreach ($checked as $id) {
				$result = $commentslib->unarchive_thread($id);
				if ($result && $result->numRows()) {
					$i2++;
				}
			}
			if ($i2) {
				Feedback::success($i2 === 1 ? tr('Comment unarchived') : tr('%0 comments unarchived', $i2));
			} else {
				Feedback::error(tr('No comments unarchived'));
			}
		}
	}
} elseif (!empty($_REQUEST['action'])) {
	Feedback::error(tra('Action not performed since no comments were selected'));
}

if (isset($_REQUEST["sort_mode"])) {
	$sort_mode = $_REQUEST["sort_mode"];
} else {
	$sort_mode = 'commentDate_desc';
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (isset($_REQUEST["offset"])) {
	$offset = $_REQUEST["offset"];
} else {
	$offset = 0;
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
	$find = strip_tags($_REQUEST["find"]);
} else {
	$find = '';
}
$smarty->assign_by_ref('find', $find);
if (! isset($_REQUEST['findfilter_approved'])) {
	$_REQUEST['findfilter_approved'] = '';
}
if ($prefs['feature_comments_moderation'] == 'y') {
	$filter_values = ['approved' => $_REQUEST['findfilter_approved']];
	$filter_names = ['approved' => tra('Approved Status')];
	$filters = ['approved' => ['n' => tra('Queued'), 'y' => tra('Approved'), 'r' => tra('Rejected')]];
	asort($filters['approved']);
} else {
	$filters = $filter_names = $filter_values = [];
}

$objectsIds = '';

if (isset($blogId)) {
	$objectsIds = $bloglib->get_blog_posts_ids($blogId);

	if (empty($objectsIds)) {
		$smarty->assign('msg', tra('This blog has no posts.'));
		$smarty->display('error.tpl');
		die;
	}

	$smarty->assign('blogId', $blogId);
}

$comments = $commentslib->get_all_comments($selected_types, $offset, $maxRecords, $sort_mode, $find, 'y', $_REQUEST['findfilter_approved'], false, $objectsIds);

$smarty->assign_by_ref('comments', $comments['data']);
$smarty->assign_by_ref('filters', $filters);
$smarty->assign_by_ref('filter_names', $filter_names);
$smarty->assign_by_ref('filter_values', $filter_values);
$smarty->assign_by_ref('cant', $comments['cant']);
$smarty->assign('mid', 'tiki-list_comments.tpl');
$smarty->display('tiki.tpl');
