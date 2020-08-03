<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_direct_list()
{
    return [
        'direct_pagination' => [
            'name' => tra('Use direct pagination links'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'direct_pagination_max_middle_links' => [
            'name' => tra('Maximum number of links around the current item'),
            'type' => 'text',
            'units' => tra('links'),
            'size' => '4',
            'default' => 2,
        ],
        'direct_pagination_max_ending_links' => [
            'name' => tra('Maximum number of links after the first or before the last item'),
            'type' => 'text',
            'units' => tra('links'),
            'size' => '4',
            'default' => 0,
        ],
    ];
}
