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
function upgrade_20171010_action_log_info_new_column_tiki($installer)
{
	global $dbs_tiki;

	$result = $installer->getOne(
		"SELECT COUNT(*) FROM information_schema.COLUMNS " .
		" WHERE COLUMN_NAME='log' AND TABLE_NAME='tiki_actionlog' AND TABLE_SCHEMA='" .
		$dbs_tiki .
		"'"
	);

	if ($result != 0) {
		return true;
	}
	return $installer->query('ALTER TABLE `tiki_actionlog` ADD COLUMN `log` TEXT NULL DEFAULT NULL AFTER `client`');
}
