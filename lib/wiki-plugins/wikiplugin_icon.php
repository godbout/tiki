<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_icon_info()
{
    return [
        'name' => tra('Icon'),
        'documentation' => 'PluginIcon',
        'description' => tra('Display an icon'),
        'prefs' => ['wikiplugin_icon'],
        'iconname' => 'information',
        'tags' => ['basic'],
        'format' => 'html',
        'introduced' => 14.1,
        'extraparams' => true,
        'params' => [
            'name' => [
                'required' => true,
                'name' => tra('Name'),
                'description' => tra('Name of the icon'),
                'since' => '14.1',
                'filter' => 'text',
                'accepted' => tra('Valid icon name'),
                'default' => '',
            ],
            'size' => [
                'required' => false,
                'name' => tra('Size'),
                'description' => tra('Size of the icon (greater than 0 and less than 10).'),
                'since' => '14.1',
                'default' => 1,
                'filter' => 'digits',
                'accepted' => tra('greater than 0 and less than 10'),
                'type' => 'digits',
            ],
            'rotate' => [
                'required' => false,
                'name' => tra('Rotate'),
                'description' => tra('Rotate the icon (90, 180 or 270 degrees) or flip it (horizontal or vertical).'),
                'since' => '17.1',
                'default' => '',
                'filter' => 'text',
                'accepted' => tra('90, 180, 270, horizontal, vertical'),
                'type' => 'text',
            ],
            'style' => [
                'required' => false,
                'name' => tra('Style'),
                'description' => tra('Style supported by the current icon set.'),
                'since' => '19.0',
                'default' => '',
                'filter' => 'text',
                'type' => 'text',
            ],
        ]
    ];
}

function wikiplugin_icon($data, $params)
{
    $smarty = TikiLib::lib('smarty');
    $smarty->loadPlugin('smarty_function_icon');

    return smarty_function_icon($params, $smarty->getEmptyInternalTemplate());
}
