<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_scheduler_list($partial = false)
{
	return [
		'scheduler_stalled_timeout' => [
			'name' => tr('Scheduler stalled after (minutes)'),
			'description' => tr('Set a scheduler to stall if the running time is long. Set 0 to disable stall detection.'),
			'type' => 'text',
			'filter' => 'digits',
			'default' => 15,
			'tags' => ['advanced'],
		],
		'scheduler_notify_on_stalled' => [
			'name' => tr('Notify on stalled schedulers'),
			'description' => tr('Send an email notification when a stalled scheduler is detected.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['advanced'],
		],
		'scheduler_users_to_notify_on_stalled' => [
			'name' => tr('Users to notify on stalled task'),
			'description' => tr('List of users/emails separated by comma to be notified when a scheduler task is set to stalled.</br><code>Ex: admin,operations@example.com</code></br><strong>If empty, the email will be sent to all administrators.</strong>'),
			'type' => 'text',
			'default' => '',
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
			'name' => tr('Notify on healed schedulers'),
			'description' => tr('Send an email notification when a stalled scheduler is healed.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['advanced'],
		],
		'scheduler_users_to_notify_on_healed' => [
			'name' => tr('Users to notify on healed task'),
			'description' => tr('List of users/emails separated by comma to be notified when a scheduler task is set to healed.</br><code>Ex: admin,operations@example.com</code></br><strong>If empty, the email will be sent to all administrators.</strong>'),
			'type' => 'text',
			'default' => '',
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
