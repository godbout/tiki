<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_object_list()
{
	return [
		'object_maintainers_enable' => [
			'name' => tr('Object maintainers and freshness'),
			'description' => tr('Enable tiki objects to have maintainers, update frequency and freshness. Then, console.php objects:notify-maintainers can be used.'),
			'help' => 'Object+Maintainers+Freshness',
			'type' => 'flag',
			'default' => 'n',
			'keywords' => 'object maintainers freshness',
		],
		'object_maintainers_default_update_frequency' => [
			'name' => tr('Default update frequency'),
			'description' => tr('Default number of days for object update frequency.'),
			'dependencies' => ['object_maintainers_enable'],
			'help' => 'Object+Maintainers+Freshness',
			'type' => 'text',
			'filter' => 'digits',
			'units' => tra('days'),
			'size' => 4,
			'default' => '90',
			'keywords' => 'object maintainers freshness default update frequency',
		],
	];
}
