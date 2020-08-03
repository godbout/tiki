<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_long_list()
{
    return [
        'long_date_format' => [
            'name' => tra('Long date format'),
            'description' => tra('Specify how Tiki displays the date (longer version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%A %B %e, %Y',
            'tags' => ['basic'],
            //get_strings tra("%A %d of %B, %Y");
        ],
        'long_time_format' => [
            'name' => tra('Long time format'),
            'description' => tra('Specify how Tiki displays the time (longer version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M:%S %Z',
            'tags' => ['basic'],
            //get_strings tra("%H:%M:%S %Z");
        ],
        //get_strings tra("%A %d of %B, %Y %H:%M:%S %Z");
    ];
}
