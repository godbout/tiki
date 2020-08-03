<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_generate_list()
{
    return [
        'generate_password' => [
            'name' => tra('Generate password'),
            'description' => tra('Display a button on the registration form to automatically generate a very secure password for the user.'),
            'type' => 'flag',
            'hint' => tra('The generated password may not include any restrictions (such as minimum/maximum length.'),
            'default' => 'n',
        ],
    ];
}
