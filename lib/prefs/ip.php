<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_ip_list()
{
    return [
        'ip_can_be_checked' => [
            'name' => tra('IP can be checked'),
            'description' => tra("Check anonymous votes by user's IP"),
            'type' => 'flag',
            'default' => 'n',
        ],
    ];
}
