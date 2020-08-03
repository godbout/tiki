<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_log_list()
{
    return [
        'log_mail' => [
            'name' => tra('Log mail in Tiki logs'),
            'description' => tra('A line of type mail will be included in the System Log with the destination address and subject of each email sent.'),
            'type' => 'flag',
            'warning' => tra('May impact performance'),
            'help' => 'System+Log',
            'default' => 'n',
        ],
        'log_tpl' => [
            'name' => tra('Smarty template usage indicator'),
            'description' => tra('Add HTML comment at start and end of each Smarty template (.tpl file)'),
            'hint' => tra('Use only for development, not in production at a live site, because these warnings are added to emails as well, and are visible to users in the page source.'),
            'detail' => tra('You need to clear your Tiki template cache for this change to take effect'),
            'type' => 'flag',
            'warning' => tra('May impact performance'),
            'default' => 'n',
        ],
        'log_sql' => [
            'name' => tra('Log SQL'),
            'description' => tra('All SQL queries will be registered in the database in the adodb_logsql table. '),
            'warning' => tra('Do not enable this feature all the time. It can be very resource intensive and will impact performance.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'log_sql_perf_min' => [
            'name' => tra('Log queries using more than'),
            'description' => tra('Use to log only queries that exceed a specific amount of time.'),
            'warning' => tra('May impact performance'),
            'type' => 'text',
            'units' => tra('seconds'),
            'size' => 5,
            'dependencies' => [
                'log_sql',
            ],
            'default' => '0.05',
        ],
    ];
}
