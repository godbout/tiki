<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_ids_list()
{
    return [
        'ids_enabled' => [
            'name' => tra('Enable intrusion detection system'),
            'description' => tra('An intrusion detection system (IDS) is a device or software application that monitors a network or systems for malicious activity or policy violations.'),
            'type' => 'flag',
            'default' => 'n',
            'packages_required' => ['enygma/expose' => 'Expose\Manager'],
        ],
        'ids_mode' => [
            'name' => tra('Intrusion detection system mode'),
            'description' => tra('Define IDS operation mode, log only, or log and block with impact over a given threshold.'),
            'type' => 'list',
            'options' => [
                'log_only' => tra('Log only'),
                'log_block' => tra('Log and block requests'),
            ],
            'default' => 'log_only',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_threshold' => [
            'name' => tra('Intrusion detection system threshold'),
            'description' => tra('Define IDS threshold, when configured in "Log and block requests" more.'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'default' => '0',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_custom_rules_file' => [
            'name' => tra('Custom rules file'),
            'type' => 'text',
            'default' => 'temp/ids_custom_rules.json',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_log_to_file' => [
            'name' => tra('Log to file'),
            'type' => 'text',
            'default' => 'ids.log',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_log_to_database' => [
            'name' => tra('Log to database'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
    ];
}
