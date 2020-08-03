<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_sub_info()
{
    return [
        'name' => tra('Subscript'),
        'documentation' => 'PluginSub',
        'description' => tra('Apply subscript font to text'),
        'prefs' => [ 'wikiplugin_sub' ],
        'body' => tra('text'),
        'iconname' => 'subscript',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => [
        ],
    ];
}

function wikiplugin_sub($data, $params)
{
    return "<sub>$data</sub>";
}
