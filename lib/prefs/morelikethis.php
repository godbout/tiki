<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_morelikethis_list()
{
	return [

		// Used in templates/tiki-admin-include-freetags.tpl
		'morelikethis_algorithm' => [
			'name' => tra('"More Like This" algorithm'),
			'description' => tra('enables tagged material to offer a find potentially related content. Basic will present content where a minimum number of tags match the item or page being viewed. Weighted is the same, but items are presented in Weighted (highest value first) sort order.'),
			'type' => 'list',
			'options' => [
				'basic' => tra('Basic'),
				'weighted' => tra('Weighted'),
			],
			'default' => 'basic',
		],
		'morelikethis_basic_mincommon' => [
			'name' => tra('Minimum number of tags in common'),
			'description' => tra('Used with the above option to determine the minimum number of matching tags required for an item to be presented in the “More Like This” feature.'),
			'type' => 'list',
			'units' => tra('tags'),
			'options' => [
				'1' => tra('1'),
				'2' => tra('2'),
				'3' => tra('3'),
				'4' => tra('4'),
				'5' => tra('5'),
				'6' => tra('6'),
				'7' => tra('7'),
				'8' => tra('8'),
				'9' => tra('9'),
				'10' => tra('10'),
			],
			'default' => '2',
		],
	];
}
