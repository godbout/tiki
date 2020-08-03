<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_layout_list()
{
    return [
        'layout_fixed_width' => [
            'name' => tra('Layout width'),
            'description' => tra('The width of the content area of the site, centered in the browser window.'),
            'type' => 'text',
            'hint' => tra('for example, 960px'),
            'dependencies' => [
                'feature_fixed_width',
            ],
            'tags' => ['basic'],
            'default' => '1170px',
        ],
        'layout_tabs_optional' => [
            'name' => tra('Tabs optional'),
            'description' => tra('Users can choose not to have tabs. A <b>no tabs</b> button will be displayed.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_tabs',
            ],
            'default' => 'y',
        ],
        'layout_add_body_group_class' => [
            'name' => tra('Add group CSS info'),
            'hint' => tra('Add CSS classes to the page BODY tag based on the user\'s group membership'),
            'description' => tra('Either grp_Anonymous or grp_Registered and possibly grp_Admins as well'),
            'type' => 'flag',
            'default' => 'n',
            'keywords' => 'body class html grp',
        ],
    ];
}
