<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_poll_list()
{
    return [
        'poll_comments_per_page' => [
            'name' => tra('Number per page'),
            'description' => tra('Number of comments to show on poll pages'),
            'type' => 'text',
            'units' => tra('comments'),
            'size' => '5',
            'filter' => 'digits',
            'default' => 10,
        ],
        'poll_comments_default_ordering' => [
            'name' => tra('Display order'),
            'description' => tra('The order in which order polls are displayed'),
            'type' => 'list',
            'options' => [
                'commentDate_desc' => tra('Newest first'),
                'commentDate_asc' => tra('Oldest first'),
                'points_desc' => tra('Points'),
            ],
            'default' => 'points_desc',
        ],
        'poll_list_categories' => [
            'name' => tra('Show categories'),
            'description' => tra('For polls that are categorized, the category is displayed.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_categories',
            ],
            'default' => 'n',
        ],
        'poll_list_objects' => [
            'name' => tra('Show objects'),
            'description' => tra('List wiki pages or other site objects that the poll is attached to.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'poll_multiple_per_object' => [
            'name' => tra('Multiple polls per object'),
            'description' => tra('When used with the rating features, allow multiple polls to be attached to a single object.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'poll_surveys_textarea_hidetoolbar' => [
            'name' => tra('Disable textarea toolbar'),
            'description' => tra('Don\'t display the toolbar on text inputs in surveys.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'poll_percent_decimals' => [
            'name' => tra('Precision of survey results calculation'),
            'description' => tra('Number of decimal places in the percentage figures'),
            'type' => 'text',
            'size' => '2',
            'filter' => 'digits',
            'units' => tra('decimal places'),
            'default' => 2,
        ],
    ];
}
