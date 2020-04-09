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
 * Added in 2018 for tiki 19 - need to change the defaults for datetimes from 0000-00-00 to null for mysql > 5.6
 * @param $installer
 */
function upgrade_20111231_change_datetime_defaults_to_null_tiki($installer)
{

	// time_to_send was dropped in 20120123_remove_column_from_tiki_user_reports_tiki.sql
	$query = $installer->query("SHOW COLUMNS FROM `tiki_user_reports` LIKE 'time_to_send';");
	if ($query->result) {
		$installer->query("ALTER TABLE `tiki_user_reports` CHANGE `last_report` `last_report` DATETIME  NULL, CHANGE `time_to_send` `time_to_send` DATETIME  NULL;");
	} else {
		$installer->query("ALTER TABLE `tiki_user_reports` CHANGE `last_report` `last_report` DATETIME  NULL;");
	}

	$installer->query("ALTER TABLE `tiki_user_reports_cache` CHANGE `time` `time` DATETIME  NULL;");
	$installer->query("ALTER TABLE `tiki_payment_requests` CHANGE `due_date` `due_date` DATETIME  NULL;");
}
