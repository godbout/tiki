<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_center_list()
{
	return [
		'center_shadow_start' => [
			'name' => tra('Center shadow start'),
			'type' => 'textarea',
			'size' => '2',
			'default' => '',
		],
		'center_shadow_end' => [
			'name' => tra('Center shadow end'),
			'type' => 'textarea',
			'size' => '2',
			'default' => '',
		],
	];
}
