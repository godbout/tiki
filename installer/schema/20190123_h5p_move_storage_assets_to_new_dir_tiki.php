<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: 20180925_feature_jquery_superfish_pref_default_tiki.php 67647 2018-09-25 17:23:02Z jonnybradley $

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * Move h5p assets to the new dir if it doesn't exist
 *
 * @param Installer $installer
 */
function upgrade_20190123_h5p_move_storage_assets_to_new_dir_tiki($installer)
{
	$newH5Pdir = 'storage/public/h5p';

	if (! is_dir($newH5Pdir) && is_writable('storage/public')) {
		if (mkdir($newH5Pdir)) {
			foreach (['cachedassets', 'content', 'exports','libraries','temp'] as $dir) {

				if (is_dir('storage/public/' . $dir)) {
					rename('storage/public/' . $dir, $newH5Pdir . '/' . $dir);
				}
			}

		}
	} else {
		trigger_error(tr('H5P assets move script: Directory storage/public not wirtable'));
	}
}
