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
 * Check if templated groups are being used. If they are enable templated groups feature.
 * @param $installer
 * @return bool
 * @throws Exception
 */
function upgrade_20200710_feature_templated_groups_tiki($installer)
{

	$cant = $installer->getOne('SELECT count(*) FROM `users_groups` where isTplGroup = \'y\'');
	if ($cant > 0) {
		$pref = $installer->getOne('SELECT COUNT(*) FROM `tiki_preferences` WHERE `name` = \'feature_templated_groups\'');
		if ($pref > 0) {
			$installer->query('UPDATE `tiki_preferences` SET `value` = ? WHERE `name` = \'feature_templated_groups\';', 'y');
		} else {
			$installer->query('INSERT INTO `tiki_preferences` (`name`, `value`) VALUES (\'feature_templated_groups\', ? );', 'y');
		}
	}

	return true;
}
