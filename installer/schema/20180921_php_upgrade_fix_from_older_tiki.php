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
	// Fix upgrade from 15.x
	if (! empty($installer->query("SHOW COLUMNS FROM `users_users` LIKE 'challenge';")->result)) {
		$installer->query('ALTER TABLE users_users DROP COLUMN challenge;');
	}
}
