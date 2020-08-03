<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_activity_list()
{
    return [
        'activity_basic_events' => [
            'name' => tr('Record basic events'),
            'description' => tr('Enable recording of basic internal Tiki events. This is primarily for entry level options. Using custom events is strongly encouraged.'),
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_tracker_update' => [
            'name' => tr('Record tracker item update'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_trackers'],
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_tracker_create' => [
            'name' => tr('Record tracker item creation'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_trackers'],
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_follow_add' => [
            'name' => tr('Record user following users'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_follow_incoming' => [
            'name' => tr('Record user being followed by users'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_friend_add' => [
            'name' => tr('Record user adding new friend'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_custom_events' => [
            'name' => tr('Custom activities'),
            'description' => tr('Allow the defining of custom behaviors in addition to internal events.'),
            'help' => 'Activity+Stream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_notifications' => [
            'name' => tr('Enable notifications through activities'),
            'description' => tr('Allow users to create notifications using activities.'),
            'help' => 'Activity+Notifications',
            'type' => 'flag',
            'default' => 'n',
        ],
    ];
}
