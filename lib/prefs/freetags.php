<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_freetags_list()
{

	$freetags_sort_orders = [
		'name_asc' => tra('Name') . ' ' . tra('ascending'),
		'name_desc' => tra('Name') . ' ' . tra('descending'),
		'created_asc' => tra('Created') . ' ' . tra('ascending'),
		'created_desc' => tra('Created') . ' ' . tra('descending'),
		'description_asc' => tra('Description') . ' ' . tra('ascending'),
		'description_desc' => tra('Description') . ' ' . tra('descending'),
		'hits_asc' => tra('Hits') . ' ' . tra('ascending'),
		'hits_desc' => tra('Hits') . ' ' . tra('descending'),
		'href_asc' => tra('Href') . ' ' . tra('ascending'),
		'href_desc' => tra('Href') . ' ' . tra('descending'),
		'itemid_asc' => tra('Item ID') . ' ' . tra('ascending'),
		'itemid_desc' => tra('Item ID') . ' ' . tra('descending'),
		'objectid_asc' => tra('Object ID') . ' ' . tra('ascending'),
		'objectid_desc' => tra('Object ID') . ' ' . tra('descending'),
		'type_asc' => tra('Type') . ' ' . tra('ascending'),
		'type_desc' => tra('Type') . ' ' . tra('descending'),
		'comments_locked_asc' => tra('Comments locked') . ' ' . tra('ascending'),
		'comments_locked_desc' => tra('Comments locked') . ' ' . tra('descending'),
	];

	return  [
			'freetags_multilingual' => [
			'name' => tra('Multilingual tags'),
			'description' => tra('Permits translation management of tags'),
			'help' => 'Tags',
			'type' => 'flag',
			'dependencies' => [
				'feature_multilingual',
				'feature_freetags',
			],
			'default' => 'n',
			],
			'freetags_sort_mode' => [
			'name' => tra('Ordering of tagged objects'),
			'description' => tra('Default sort mode for tagged items'),
			'type' => 'list',
			'options' => $freetags_sort_orders,
			'default' => 'name_asc',
			],
			'freetags_browse_show_cloud' => [
			'name' => tra('Show tag cloud'),
			'description' => tra('Visibility of the full tag cloud in the list tags screen'),
			'type' => 'flag',
			'default' => 'y',
			],
			'freetags_browse_amount_tags_in_cloud' => [
			'name' => tra('Maximum number of tags in cloud'),
			'type' => 'text',
			'size' => '5',
			'filter' => 'digits',
			'units' => tra('tags'),
			'default' => '100',
			],
			'freetags_show_middle' => [
			'name' => tra('Show tags in middle column'),
			'description' => tra('Where pages have tag content, this option enables freetag display on that page at the top. Freetags can be set in the Properties tab when editing Wiki pages for example.'),
			'type' => 'flag',
			'default' => 'y',
			],
			'freetags_preload_random_search' => [
			'name' => tra('Preload random tag'),
			'description' => tra('A random tag will be selected when displaying the freetag search page (if no tag is pre-selected).'),
			'type' => 'flag',
			'default' => 'y',
			],
			'freetags_browse_amount_tags_suggestion' => [
			'name' => tra('Tag Suggestions'),
			'description' => tra('Number of tags to show in tag suggestions'),
			'type' => 'text',
			'size' => '4',
			'filter' => 'digits',
			'units' => tra('tags'),
			'default' => '10',
			],
			'freetags_normalized_valid_chars' => [
			'name' => tra('Valid characters pattern'),
			'description' => tra('Click on the links below to set or clear a pattern to limit Freetag characters accepted. '),
			'type' => 'text',
			'size' => '30',
			'hint' => 'Useful to eliminate characters such as “,” which users can enter by mistake instead of a space.',
			'default' => '',
			],
			'freetags_lowercase_only' => [
			'name' => tra('Lowercase tags only'),
			'description' => tra('Converts all uppercase characters into lower case. Another useful feature to make Freetags slightly more user friently.'),
			'type' => 'flag',
			'default' => 'y',
			],
	];
}
