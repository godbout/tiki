<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_tr_info()
{
    return [
        'name' => tra('Translate'),
        'documentation' => 'PluginTR',
        'description' => tra('Translate text to the user language'),
        'prefs' => [ 'feature_multilingual', 'wikiplugin_tr' ],
        'body' => tra('string'),
        'iconname' => 'language',
        'introduced' => 2,
        'params' => [
        ],
    ];
}

function wikiplugin_tr($data)
{
    return tra($data);
}
