<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_display_list()
{
    return [
        'display_field_order' => [
            'name' => tra('Fields display order'),
            'description' => tra('The order date field inputs should be listed in.'),
            'type' => 'list',
            'options' => [
                'DMY' => tra('Day') . ' ' . tra('Month') . ' ' . tra('Year'),
                'DYM' => tra('Day') . ' ' . tra('Year') . ' ' . tra('Month'),
                'MDY' => tra('Month') . ' ' . tra('Day') . ' ' . tra('Year'),
                'MYD' => tra('Month') . ' ' . tra('Year') . ' ' . tra('Day'),
                'YDM' => tra('Year') . ' ' . tra('Day') . ' ' . tra('Month'),
                'YMD' => tra('Year') . ' ' . tra('Month') . ' ' . tra('Day'),
            ],
            'default' => 'MDY',
            'tags' => ['basic'],
        ],
        'display_start_year' => [
            'name' => tra('Start year'),
            'description' => tra('Year to show first in dropdown lists.') . '<br>' .
                            tra('For example, use "-2" for the current year minus two, or "2010" for a specific year'),
            'units' => tra('year(s)'),
            'type' => 'text',
            'size' => 6,
            'default' => '-3',
        ],
        'display_end_year' => [
            'name' => tra('End year'),
            'description' => tra('Year to show last on dropdown lists.') . '<br>' .
                            tra('For example, use "+2" for the current year plus two, or "2016" for a specific year'),
            'units' => tra('year(s)'),
            'type' => 'text',
            'size' => 6,
            'default' => '+1',
        ],
    ];
}
