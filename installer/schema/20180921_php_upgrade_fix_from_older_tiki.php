<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * @param $installer
 */
function upgrade_20180921_php_upgrade_fix_from_older_tiki($installer)
{
	// Fix upgrade from 9.x
	$installer->query('ALTER TABLE `tiki_secdb` DROP PRIMARY KEY;'); // it might not be set
	$installer->query('ALTER TABLE `tiki_secdb` ADD PRIMARY KEY (`filename`(215),`tiki_version`(40));');

	// Fix upgrade from 12.x
	$tablesToRename = [
		'metrics_assigned',
		'metrics_metric',
		'metrics_tab',
		'tiki_users_score',
	];

	foreach ($tablesToRename as $table) {
		if (! empty($installer->query("SHOW TABLES LIKE '" . $table . "';")->result)) {
			$installer->query('RENAME TABLE `' . $table . '` TO `zzz_unused_' . $table . '`;');
		}
	}

	if (! empty($installer->query("SHOW COLUMNS FROM `tiki_pages` LIKE 'status';")->result)) {
		$installer->query('ALTER TABLE tiki_pages DROP COLUMN status;');
	}
	if (! empty($installer->query("SHOW COLUMNS FROM `tiki_history` LIKE 'status';")->result)) {
		$installer->query('ALTER TABLE tiki_history DROP COLUMN status;');
	}

	// Fix upgrade from 15.x
	if (! empty($installer->query("SHOW COLUMNS FROM `users_users` LIKE 'challenge';")->result)) {
		$installer->query('ALTER TABLE users_users DROP COLUMN challenge;');
	}
}
