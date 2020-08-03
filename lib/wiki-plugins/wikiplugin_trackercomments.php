<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_trackercomments_info()
{
    return [
        'name' => tra('Tracker Comments'),
        'documentation' => 'PluginTrackerComments',
        'description' => tra('Display the number of comments for a tracker'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_trackercomments' ],
        'iconname' => 'comments',
        'introduced' => 5,
        'params' => [
            'trackerId' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '5.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'shownbitems' => [
                'required' => false,
                'name' => tra('Item Count'),
                'description' => tra('Determines whether the number of items will be shown (not shown by default)'),
                'since' => '5.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'view' => [
                'required' => false,
                'name' => tra('View'),
                'description' => tra('Enter a username to select the items of the current user'),
                'since' => '5.0',
                'accepted' => tra('a user name'),
                'filter' => 'text',
                'default' => ''
            ],
        ]
    ];
}
function wikiplugin_trackercomments($data, $params)
{
    $trklib = TikiLib::lib('trk');
    global $user;
    extract($params, EXTR_SKIP);
    $ret = '';
    if ($shownbitems == 'y') {
        $ret .= tra('Comments found:') . ' ' . $trklib->nbComments($user);
    }

    return $ret;
}
