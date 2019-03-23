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
 * In r68910 the file was added without "_tiki", and the fix in r69062 breaks updates when the DB state is between r68910 and r69062
 *
 * @param Installer $installer
 */
function upgrade_20190129_fix_ambiguity_on_oauthserver_id_column_tiki($installer)
{
	if (! empty($installer->query("SHOW COLUMNS FROM `tiki_oauthserver_clients` LIKE 'identifier';")->result)) {
		$installer->query('ALTER TABLE tiki_oauthserver_clients  CHANGE `identifier`  `id` INT(14) NOT NULL AUTO_INCREMENT;');
	}
}
