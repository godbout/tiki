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
 * Set creation date to scheduler
 * @param $installer
 * @return bool
 * @throws Exception
 */
function upgrade_20190425_set_creation_date_to_scheduler_tiki($installer)
{

	$schedLib = TikiLib::lib('scheduler');
	$result = $schedLib->get_scheduler(null, null, ['creation_date' => 0]);

	foreach ($result as $item) {
		$schedulerId = $item['id'];
		$end_time = $installer->getOne('SELECT end_time FROM tiki_scheduler_run where scheduler_id = ? and end_time > 0 ORDER BY id ASC', [$schedulerId]);

		if (isset($end_time)) {
			$item['creation_date'] = (int)$end_time;
		} else {
			$item['creation_date'] = time();
		}

		$schedulersTable = $schedLib->table('tiki_scheduler');
		$schedulersTable->update($item, ['id' => $schedulerId]);
	}

	return true;
}
