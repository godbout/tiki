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
	];
}
