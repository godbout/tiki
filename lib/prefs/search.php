<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_search_list()
{
    global $prefs;

    return  [
        'search_parsed_snippet' => [
            'name' => tra('Parse search results'),
            'warning' => tra('May impact performance'),
            'description' => tra('When enabled search results are parsed so content formatting is visible in the search results'),
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
            'dependencies' => ['feature_multilingual'],
            'default' => 'n',
        ],
        'search_autocomplete' => [
            'name' => tra('Autocomplete page names'),
            'description' => tr('Automatically complete page names as the user starts typing. For example the user types the start of the wiki page name “Sear” and Tiki returns “Search”, “Search General Settings”, etc'),
            'type' => 'flag',
            'dependencies' => ['feature_jquery_autocomplete', 'javascript_enabled'],
            'warning' => tra('deprecated'),
            'default' => 'n',
            'tags' => ['deprecated'],
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
            'description' => tra('Default number of facet results to obtain'),
            'type' => 'text',
            'size' => 8,
            'filter' => 'digits',
            'units' => tra('facet results'),
            'default' => '10',
            'dependencies' => [
                'search_use_facets',
            ],
        ],
        'search_index_outdated' => [
            'name' => tra('Search index outdated'),
            'description' => tra('Number of days to consider the search index outdated'),
            'type' => 'text',
            'size' => 8,
            'filter' => 'digits',
            'default' => '2',
            'units' => tra('days'),
            'tags' => ['basic'],
        ],
        'search_error_missing_field' => [
            'name' => tra('Show error on missing field'),
            'description' => tra('When using List plugin to specify certain fields, especially tracker fields, this check helps ensure their names were entered correctly.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'search_file_thumbnail_preview' => [
            'name' => tra('File thumbnail preview'),
            'description' => tra('Have a preview of attachments in search results'),
            'type' => 'flag',
            'packages_required' => ['media-alchemyst/media-alchemyst' => 'MediaAlchemyst\Alchemyst'],
            'default' => 'n',
        ],
        'search_date_facets' => [
            'name' => tra('Use date histogram aggregations'),
            'description' => tr('Use date histogram aggregations (facets) when indexing, requires Elasticsearch'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'search_use_facets',
            ],
        ],
        'search_date_facets_interval' => [
            'name' => tra('Date histogram aggregations interval'),
            'description' => tr('Default interval for date histogram aggregations.') . '<br>' .
                tr(
                    'Use "year, quarter, month, week, day, hour, minute, second" or Elasticsearch Time units as descibed here %0',
                    'https://www.elastic.co/guide/en/elasticsearch/reference/5.6/common-options.html#time-units'
                ),
            'type' => 'text',
            'default' => 'year',
            'dependencies' => [
                'search_date_facets',
            ],
        ],
        'search_date_facets_ranges' => [
            'name' => tra('Date range aggregations ranges'),
            'description' => tr('Default ranges for date range aggregations.') . '<br>' .
                tr(
                    'Comma separated ranges, one per line using Elasticsearch Time date math as descibed here %0',
                    'https://www.elastic.co/guide/en/elasticsearch/reference/5.6/common-options.html#date-math'
                ),
            'type' => 'textarea',
            'default' => "
now-2y/y,now-1y/y,Last Year
now-1y/y,now,This Year
now-1m/m,now/m,Last Month
now/d,now+1d/d,Today
now+1d/d,now+2d/d,Tomorrow
now/w,now+1w/w,Next Week
now/m,now+1m/m,Next Month
",
            'dependencies' => [
                'search_date_facets',
            ],
        ],
        'search_excluded_facets' => [
            'name' => tra('Excluded facets'),
            'description' => tra('List of facets (a.k.a. aggregations) to exclude from the default search results'),
            'hint' => 'For example: object_type,title_initial,title_firstword,tracker_field_userName',
            'type' => 'text',
            'filter' => 'word',
            'separator' => ',',
            'default' => [],
            'dependencies' => [
                'search_use_facets',
            ],
        ],
        'search_avoid_duplicated_facet_labels' => [
            'name' => tra('Avoid Duplicated Facets'),
            'description' => tra('Avoid Facets appearing with the same name, usually by appending the Tracker name.'),
            'type' => 'flag',
            'filter' => 'alpha',
            'default' => 'y',
            'dependencies' => [
                'search_use_facets',
            ],
        ],
    ];
}
