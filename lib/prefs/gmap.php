<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_gmap_list()
{
    return [
        'gmap_key' => [
            'name' => tra('Google Maps API Key'),
            'description' => tra('Needed for Street View or other advanced features'),
            'type' => 'text',
            'size' => 87,
            'help' => 'http://code.google.com/apis/maps/signup.html',
            'filter' => 'striptags',
            'default' => '',
        ],
        'gmap_defaultx' => [
            'name' => tra('Default x for map center'),
            'type' => 'text',
            'size' => 20,
            'filter' => 'striptags',
            'default' => '0',
        ],
        'gmap_defaulty' => [
            'name' => tra('Default y for map center'),
            'type' => 'text',
            'size' => 20,
            'filter' => 'striptags',
            'default' => '0',
        ],
        'gmap_defaultz' => [
            'name' => tra('Default zoom level'),
            'type' => 'list',
            'options' => [
                1 => tra('whole earth'),
                2 => 2,
                3 => 3,
                4 => 4,
                5 => tra('country size'),
                6 => 6,
                7 => 7,
                8 => 8,
                9 => 9,
                10 => 10,
                11 => tra('city size'),
                12 => 12,
                13 => 13,
                14 => 14,
                15 => 15,
                16 => 16,
                17 => 17,
                18 => tra('max zoom'),
            ],
            'default' => '1',
        ],
        'gmap_article_list' => [
            'name' => tra('Show map mode buttons in articles list'),
            'type' => 'flag',
            'dependencies' => [
                'geo_locate_article',
            ],
            'default' => 'n',
        ],
        'gmap_page_list' => [
            'name' => tra('Show map mode buttons in page list'),
            'type' => 'flag',
            'dependencies' => [
                'geo_locate_wiki',
            ],
            'default' => 'n',
        ],
    ];
}
