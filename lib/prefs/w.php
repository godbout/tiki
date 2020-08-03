<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_w_list()
{
    return [
        'w_displayed_default' => [
            'name' => tra('Display by default'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'w_use_dir' => [
            'name' => tra('Path (if stored in directory)'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
        ],
        'w_use_db' => [
            'name' => tra('Storage'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                'y' => tra('Store in database'),
                'n' => tra('Store in directory'),
            ],
            'default' => 'y',
        ],
    ];
}
