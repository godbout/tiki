<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_mustread_list()
{
    return [
        'mustread_enabled' => [
            'name' => tr('Must read'),
            'description' => tr('Allow assignment of mandatory readings and track progress.'),
            'help' => 'Must+Reads',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_search', 'feature_trackers'],
        ],
        'mustread_tracker' => [
            'name' => tr('Must read tracker'),
            'description' => tr('Tracker containing the individual read requests.'),
            'hint' => tr('Tracker ID'),
            'type' => 'text',
            'filter' => 'int',
            'size' => 6,
            'profile_reference' => 'tracker',
            'default' => '',
        ],
    ];
}
