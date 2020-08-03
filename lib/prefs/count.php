<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_count_list()
{
    return [
        'count_admin_pvs' => [
            'name' => tra('Count admin pageviews'),
            'description' => tra('Include pageviews by Admin when reporting stats.'),
            'type' => 'flag',
            'default' => 'n',
        ],
    ];
}
