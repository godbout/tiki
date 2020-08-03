<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_page_list()
{
    return [
        'page_bar_position' => [
            'name' => tra('Wiki buttons'),
            'description' => tra('Specify the location  of the wiki-specific options (such as Backlinks, Page Description, and so on)'),
            'type' => 'list',
            'options' => [
                'top' => tra('Top '),
                'bottom' => tra('Bottom'),
                'none' => tra('Neither'),
            ],
            'default' => 'bottom',
        ],
        'page_n_times_in_a_structure' => [
            'name' => tra('Pages can reoccur in structure'),
            'description' => tra('A page can be listed multiple times in a structure'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'page_content_fetch' => [
            'name' => tra('Fetch page content from incoming feeds'),
            'description' => tra('Page content from the source will be fetched before sending the content to the generators.'),
            'type' => 'flag',
            'default' => 'n',
            'packages_required' => ['j0k3r/php-readability' => 'Readability\Readability'],
        ],
    ];
}
