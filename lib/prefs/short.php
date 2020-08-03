<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_short_list()
{
    return [
        'short_date_format' => [
            'name' => tra('Short date format'),
            'description' => tra('Specify how Tiki displays the date (shorter version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%Y-%m-%d',
            //get_strings tra("%Y-%m-%d");
            'tags' => ['basic'],
        ],
        'short_time_format' => [
            'name' => tra('Short time format'),
            'description' => tra('Specify how Tiki displays the time (shorter version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M',
            //get_strings tra("%H:%M");
            'tags' => ['basic'],
        ],
        'short_date_format_js' => [
            'name' => tra('Short JavaScript date format'),
            'description' => tra('Used in jQuery-UI date picker fields'),
            'help' => 'http://api.jqueryui.com/datepicker/#utility-formatDate',
            'type' => 'text',
            'size' => '30',
            'default' => 'yy-mm-dd',
            //get_strings tra("yy-mm-dd");
        ],
        'short_time_format_js' => [
            'name' => tra('Short JavaScript time format'),
            'description' => tra('Used in jQuery-UI datetime picker fields'),
            'help' => 'http://trentrichardson.com/examples/timepicker/#tp-formatting',
            'type' => 'text',
            'size' => '30',
            'default' => 'HH:mm',
            //get_strings tra("HH:mm");
        ],
        //get_strings tra("%Y-%m-%d %H:%M");
    ];
}
