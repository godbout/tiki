<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');

$access->check_user($user);
$access->check_feature('wikiplugin_groupedit');

if(isset($_POST['id']) && isset($_POST['name'])){

	$userlib = TikiLib::lib('user');

	if ($groupInfo = $userlib->get_groupId_info($_POST['id'])) {
		$groupName = $groupInfo['groupName'];
		$perms = Perms::get(['type' => 'group', 'object' => $groupName]);
		if (!$perms->edit_grouplimitedinfo) {
			$access->display_error(null, tr("Permission denied."), 403);
		}
	} else {
		$access->display_error(null, tr("Permission denied."), 403);
	}

	if(! $userlib->edit_group($_POST['id'], $_POST['name'], $_POST['desc'])){
		die;
	}
}else{
	die;
}
