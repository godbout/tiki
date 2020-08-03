<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_wikipluginprefs_list()
{
    return [
        'wikipluginprefs_pending_notification' => [
            'name' => tra('Plugin pending approval notification'),
            'description' => tra('Send an email alert to users with permission to approve plugins when a plugin approval is pending'),
            'dependencies' => ['sender_email'],
            'type' => 'flag',
            'default' => 'n',
        ],
    ];
}
