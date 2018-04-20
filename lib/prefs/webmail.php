<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_webmail_list()
{
	return [
		'webmail_view_html' => [
			'name' => tra('HTML email'),
			'type' => 'flag',
			'description' => tra('Allow viewing HTML emails.'),
			'default' => 'y',
		],
		'webmail_max_attachment' => [
			'name' => tra('Maximum attachment size'),
			'type' => 'list',
			'description' => tra('Maximum size for each attachment.'),
			'options' => [
				'500000' => tra('500Kb'),
				'1000000' => tra('1Mb'),
				'1500000' => tra('1.5Mb'),
				'2000000' => tra('2Mb'),
				'2500000' => tra('2.5Mb'),
				'3000000' => tra('3Mb'),
				'100000000' => tra('Unlimited'),
			],
			'default' => 1500000,
		],
		'webmail_quick_flags' => [
			'name' => tra('Checkbox per email'),
			'type' => 'flag',
			'description' => tra('Enable easy selecting multiple mails for common actions.'),
			'default' => 'n',
		],
	];
}
