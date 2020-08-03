<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_dailyreports_list()
{
    return [
        'dailyreports_enabled_for_new_users' => [
            'name' => tr('Enable daily reports for new users'),
            'description' => tra('Determines if daily reports will be automatically enabled for new users.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'Daily+Reports',
            'tags' => ['basic', 'tiki reporting feature'],
        ],
    ];
}
