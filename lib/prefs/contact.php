<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_contact_list()
{
    return  [
        'contact_anon' => [
            'name' => tra('Allow anonymous visitors to use the "Contact Us" feature.'),
            'description' => tra('Allow anonymous visitors to use the "Contact Us" feature.'),
            'type' => 'flag',
            'help' => 'Contact+us',
            'dependencies' => [
                'feature_contact',
            ],
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'contact_priority_onoff' => [
            'name' => tra('Display contact priority'),
            'description' => tra('Display contact priority option'),
            'type' => 'flag',
            'help' => 'Contact+us',
            'dependencies' => [
                'feature_contact',
            ],
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'contact_user' => [
            'name' => tra('Contact user'),
            'description' => tra('User to contact'),
            'type' => 'text',
            'size' => 40,
            'dependencies' => [
                'feature_contact',
            ],
            'default' => 'admin',
        ],
    ];
}
