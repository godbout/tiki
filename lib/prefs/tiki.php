<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_tiki_list()
{
    return [
        'tiki_version_check_frequency' => [
            'name' => tra('Check frequency'),
            'description' => tra('How often Tiki should check for updates. This field applies only if "Check for updates automatically" is enabled. '),
            'hint' => tra('Click "Check for Updates Now" to perform an update check.'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                '86400' => tra('Each day'),
                '604800' => tra('Each week'),
                '2592000' => tra('Each month'),
            ],
            'dependencies' => [
                'feature_version_checks',
            ],
            'default' => 604800,
            'tags' => ['basic'],
        ],
        'tiki_release_cycle' => [
            'name' => tr('Upgrade cycle'),
            'description' => tra('Tiki upgrade frequency for this site to check against.'),
            'type' => 'list',
            'default' => 'regular',
            'dependencies' => [
                'feature_version_checks',
            ],
            'options' => [
                'regular' => tr('Regular (8 months)'),
                'longterm' => tr('Long-Term Support'),
            ],
            'help' => 'Version+Lifecycle',
        ],
        'tiki_minify_javascript' => [
            'name' => tra('Minify JavaScript'),
            'description' => tra('Compress JavaScript files used in the page into a single file to be distributed statically. Changes to JavaScript files will require cache to be cleared. Uses http://code.google.com/p/minify/'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'tiki_minify_late_js_files' => [
            'name' => tra('Minify late JavaScript'),
            'description' => tra('Compress extra JavaScript files used in the page after tiki-setup into a separate file which may vary from page to page.'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
        ],
        'tiki_minify_css' => [
            'name' => tra('Minify CSS'),
            'description' => tra('Compress CSS files (notably by removing white space). Changes to CSS files will require cache to be cleared.') . ' ' . tra('Uses http://code.google.com/p/minify/'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'tiki_minify_css_single_file' => [
            'name' => tra('Minify CSS into a single file'),
            'description' => tra('In addition to reducing the size of the CSS files, reduce the number of files by consolidating them.'),
            'type' => 'flag',
            'perspective' => false,
            'warning' => tra('This setting may not work out of the box for all styles. Import needs to use @import url("...") and not @import "..."'),
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'tiki_prefix_css' => [
            'name' => tra('Prefix CSS'),
            'description' => tra('Use -prefix-free JavaScript library to add vendor specific css prefixes.'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
            'tags' => ['experimental'],
            'warning' => tra('May be replaced by a server-side option soon.'),
        ],
        'tiki_same_day_time_only' => [
            'name' => tra('Skip date for same day'),
            'description' => tra('When displaying short date and time, skip date for today. Only time will be displayed.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'tiki_cachecontrol_session' => [
            'name' => tra('Cache-control header'),
            'description' => tra('Custom HTTP header to use when a session is active'),
            'type' => 'text',
            'filter' => 'striptags',
            'hint' => tra('Example: no-cache, pre-check=0, post-check=0'),
            'default' => '',
        ],
        'tiki_cachecontrol_nosession' => [
            'name' => tra('Cache-control header (no session)'),
            'description' => tra('Custom HTTP header to use when no session is active'),
            'type' => 'text',
            'filter' => 'striptags',
            'dependencies' => [ 'session_silent' ],
            'default' => '',
        ],
        'tiki_cdn' => [
            'name' => tra('Content delivery networks'),
            'description' => tra('Use alternate domains to serve static files from this Tiki site to avoid sending cookies, improve local caching and generally improve user-experience performance.'),
            'hint' => tra('List of URI prefixes to include before static files (one per line), for example: http://cdn1.example.com'),
            'help' => 'Content+Delivery+Network',
            'type' => 'textarea',
            'size' => 4,
            'filter' => 'url',
            'default' => '',
        ],
        'tiki_cdn_ssl' => [
            'name' => tra('Content delivery networks in SSL'),
            'description' => tra('Use alternate domains to serve static files from this Tiki site to avoid sending cookies, improve local caching and generally improve user-experience performance. Leave empty to disable CDN in SSL mode.'),
            'hint' => tra('List of URI prefixes to include before static files (one per line), for example: https://sslcdn1.example.com'),
            'help' => 'Content+Delivery+Network',
            'type' => 'textarea',
            'size' => 4,
            'filter' => 'url',
            'default' => '',
        ],
        'tiki_cdn_check' => [
            'name' => tra('Check CDN files exists'),
            'description' => tra('Check that minified JS and CSS files exist before including them in the page.'),
            'help' => 'Content+Delivery+Network',
            'type' => 'flag',
            'filter' => 'alpha',
            'default' => 'y',
        ],
        'tiki_domain_prefix' => [
            'name' => tra('Domain prefix handling'),
            'description' => tra('Strip or automatically add the "www." prefix on domain names to standardize URLs.'),
            'type' => 'list',
            'options' => [
                'unchanged' => tra('Leave as-is'),
                'strip' => tra('Remove the www'),
                'force' => tra('Add the www'),
            ],
            'default' => 'unchanged',
            'tags' => ['basic'],
        ],
        'tiki_domain_redirects' => [
            'name' => tra('Domain redirects'),
            'description' => tra('When the site is accessed through specific domain names, redirect to an alternate domain preserving the URL. Useful for domain name transitions, like tikiwiki.org to tiki.org.'),
            'type' => 'textarea',
            'hint' => tra('One entry per line, with each entry a comma-separated list: old domain, new domain'),
            'size' => 8,
            'default' => '',
        ],
        'tiki_check_file_content' => [
            'name' => tra('Validate uploaded file content'),
            'description' => tra('Do not trust user input and open the files to verify their content.'),
            'type' => 'flag',
            'extensions' => ['fileinfo'],
            'default' => 'y',
        ],
        'tiki_allow_trust_input' => [
            'name' => tra('Allow the tiki_p_trust_input permission.'),
            'description' => tra('Bypass user input filtering.'),
            'warning' => tra('Note: all permissions are granted to the Admins group including this one, so if you enable this you may expose your site to XSS (Cross Site Scripting) attacks for admin users.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'tiki_object_selector_threshold' => [
            'name' => tr('Object selector threshold'),
            'description' => tr('Number of records after which the object selectors will request searching instead of selecting from a list.'),
            'type' => 'text',
            'size' => 6,
            'default' => 250,
            'units' => tra('records'),
            'filter' => 'int',
        ],
        'tiki_object_selector_searchfield' => [
            'name' => tr('Object selector search field'),
            'description' => tr('Field or (comma separated) fields to search when filtering in an object selector. e.g. "%0" (default "%1")', 'content', 'title'),
            'type' => 'text',
            'default' => 'title',
            'filter' => 'text',
        ],
        'tiki_key' => [
            'name' => tr('Client key for this site'),
            'type' => 'text',
            'description' => tra('This must match the shared key entered in the Master’s key field.'),
            'size' => 32,
            'filter' => 'text',
            'default' => '',
        ],
    ];
}
