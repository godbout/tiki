<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// This script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

$imagegallib = TikiLib::lib('imagegal');

if (isset($_REQUEST['galfeatures'])) {
	// Check for last character being a / or a \
	// My next commit is to create a clas to put this code into
	// TODO Do not modify $_REQUEST variable directly
	if (substr($_REQUEST['gal_use_dir'], -1) != '\\'
		&& substr($_REQUEST['gal_use_dir'], -1) != '/'
		&& $_REQUEST['gal_use_dir'] != ''
	) {
		$_REQUEST['gal_use_dir'] .= '/';
	}

	if (substr($_REQUEST['gal_batch_dir'], -1) != '\\'
		&& substr($_REQUEST['gal_batch_dir'], -1) != '/'
		&& $_REQUEST['gal_batch_dir'] != ''
	) {
		$_REQUEST['gal_batch_dir'] .= '/';
	}
}

//*** begin state-changing actions
if (isset($_POST['rmvorphimg']) && $access->checkCsrfForm(tra('Remove orphan images?'))) {
	$adminlib->remove_orphan_images();
	Feedback::success(tra('Orphan images successfully removed'));
}

if (isset($_POST['mvimg']) && isset($_POST['move_gallery']) && $access->checkCsrf()) {
	if (($_POST['mvimg'] == 'to_fs' && $prefs['gal_use_db'] == 'n')
		|| ($_POST['mvimg'] == 'to_db' && $prefs['gal_use_db'] == 'y')
	) {
		$mvresult = $imagegallib->move_gallery_store($_POST['move_gallery'], $_POST['mvimg']);
		$mvmsg = sprintf(tra('moved %d images, %d errors occurred.'), $mvresult['moved_images'], $mvresult['errors']);
		if ($mvresult['timeout']) {
			$mvmsg .= ' ' . tra('a timeout occurred. Hit the reload button to move the rest');
		}
		Feedback::note($mvmsg);
	}
}
//*** end state-changing actions

$galleries = $imagegallib->list_visible_galleries(0, -1, 'name_desc', 'admin', '');
$smarty->assign_by_ref('galleries', $galleries['data']);
$smarty->assign('max_img_upload_size', $imagegallib->max_img_upload_size());
