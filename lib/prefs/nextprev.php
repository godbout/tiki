<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_nextprev_list()
{
    return [
        'nextprev_pagination' => [
            'name' => tra('Use relative (next / previous) pagination links'),
            'type' => 'flag',
            'default' => 'y',
        ],
    ];
}
