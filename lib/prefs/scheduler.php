<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_scheduler_list($partial = false)
{
	return [
		'scheduler_stalled_timeout' => [
			'name' => tr('Scheduler stalled after (minutes)'),
			'description' => tr('Set a scheduler as stall if it is running for long time. Set 0 to disable stall detection.'),
			'type' => 'text',
			'filter' => 'digits',
			'default' => 15,
			'tags' => ['advanced'],
		],
		'scheduler_notify_on_stalled' => [
			'name' => tr('Notify admins on stalled schedulers'),
			'description' => tr('Send an email notification to tiki admins when a stalled scheduler is detected'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['advanced'],
		],
		'scheduler_healing_timeout' => [
			'name' => tr('Self healing after (minutes)'),
			'description' => tr('Self healing resets a stalled scheduler automatically after the timeout set. 0 disables self healing'),
			'type' => 'text',
			'size' => '5',
			'default' => 30,
			'filter' => 'digits',
			'tags' => ['advanced'],
		],
		'scheduler_notify_on_healing' => [
			'name' => tr('Notify admins on healed schedulers'),
			'description' => tr('Send an email notification to tiki admins when a stalled scheduler was healed.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['advanced'],
		],
		'scheduler_keep_logs' => [
			'name' => tr('Number of logs to keep'),
			'description' => tr('0 will keep all logs'),
			'type' => 'text',
			'size' => '5',
			'default' => 10000,
			'filter' => 'digits',
			'tags' => ['advanced'],
		],
	];
}
