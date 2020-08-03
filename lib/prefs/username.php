<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_username_list()
{
    return [
        'username_pattern' => [
            'name' => tra('Username pattern'),
            'description' => tr('This regex pattern requires or forbids the use of certain characters for username. For example, to add Hebrew, use: /&#94;&#91; \'\-_a-zA-Z0-9@\.א-ת]*$/ or, for Chinese, use: /&#94;&#91; \'\-_a-zA-Z0-9@\.\x00-\xff]*$/'),
            'type' => 'text',
            'size' => 25,
            'perspective' => false,
            'default' => '/^[ \'\-_a-zA-Z0-9@\.]*$/',
        ],
    ];
}
