<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_conditions_list()
{
    return [
        'conditions_enabled' => [
            'name' => tr('Terms and Conditions'),
            'description' => tr('Automatically present a terms of use page to be accepted by users accessing the site.'),
            'dependencies' => ['feature_wiki'],
            'help' => 'Terms+and+Conditions',
            'type' => 'flag',
            'default' => 'n',
        ],
        'conditions_page_name' => [
            'name' => tr('Terms of Use page name'),
            'description' => tr('Wiki page for Terms of Use content. The page may be translated using the multilingual feature.'),
            'type' => 'text',
            'filter' => 'pagename',
            'default' => 'Terms',
            'profile_reference' => 'wiki_page',
        ],
        'conditions_minimum_age' => [
            'name' => tr('Minimum age'),
            'description' => tr('When presenting the terms of use, request age.'),
            'hint' => tr('0 to ignore age validation'),
            'type' => 'text',
            'filter' => 'int',
            'units' => tra('years'),
            'default' => 0,
        ],
    ];
}
