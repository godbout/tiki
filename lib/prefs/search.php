<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_search_list()
{
	global $prefs;
	return  [
		'search_parsed_snippet' => [
			'name' => tra('Parse the results'),
			'warning' => tra('May impact performance'),
			'type' => 'flag',
			'default' => 'y',
		],
		'search_default_where' => [
			'name' => tra('Default where'),
			'description' => tra('When object filter is not on, limit to search one type of object'),
			'type' => 'multicheckbox',
			'options' => isset($prefs['feature_search_fulltext']) && $prefs['feature_search_fulltext'] === 'y' ?
					[
						'' => tra('Entire site'),
						'wikis' => tra('Wiki Pages'),
						'trackers' => tra('Trackers'),
					] : [
						'' => tra('Entire site'),
						'wiki page' => tra('Wiki pages'),
						'blog post' => tra('Blog posts'),
						'article' => tra('Articles'),
						'file' => tra('Files'),
						'forum post' => tra('Forums'),
						'trackeritem' => tra('Tracker items'),
						'sheet' => tra('Spreadsheets'),
					],
			'default' => [],
		],
		'search_default_interface_language' => [
			'name' => tra('Restrict search language by default'),
			'description' => tra('Only search content that is in the interface language, otherwise show the language menu.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'search_autocomplete' => [
			'name' => tra('Autocomplete page names'),
			'description' => tr('Automatically complete page names as the user starts typing. For example the user types the start of the wiki page name “Sear” and Tiki returns “Search”, “Search General Settings”, etc'),
			'type' => 'flag',
			'dependencies' => ['feature_jquery_autocomplete', 'javascript_enabled'],
			'warning' => tra('deprecated'),
			'default' => 'n',
		],
		'search_show_category_filter' => [
			'name' => tra('Category filter'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_categories',
			],
			'tags' => ['basic'],
		],
		'search_show_tag_filter' => [
			'name' => tra('Tag filter'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_freetags',
			],
			'tags' => ['basic'],
		],
		'search_show_sort_order' => [
			'name' => tra('Sort order'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['basic'],
		],
		'search_use_facets' => [
			'name' => tra('Use facets for default search interface'),
			'description' => tra('Facets are dynamic filters generated by the search engine to refine the search results. The feature may not be supported for all search engines.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'search_facet_default_amount' => [
			'name' => tra('Facet result count'),
			'description' => tra('Default number of facet results to obtain.'),
			'type' => 'text',
			'size' => 8,
			'filter' => 'digits',
			'units' => tra('facet results'),
			'default' => '10',
		],
		'search_index_outdated' => [
			'name' => tra('Search index outdated'),
			'description' => tra('Number of days to consider the search index outdated.'),
			'type' => 'text',
			'size' => 8,
			'filter' => 'digits',
			'default' => '2',
			'units' => tra('days'),
			'tags' => ['basic'],
		],
		'search_error_missing_field' => [
			'name' => tra('Show error on missing field'),
			'description' => tra('When using LIST plugin to specify certain fields, especially tracker fields, this check helps ensure their names were entered correctly.'),
			'type' => 'flag',
			'default' => 'y',
		],
	];
}
