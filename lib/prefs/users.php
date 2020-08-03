<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_users_list()
{
    return [
        'users_serve_avatar_static' => [
            'name' => tra('Serve profile pictures statically'),
            'description' => tra('When enabled, feature checks and permission checks will be skipped.'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'y',
        ],
        'users_prefs_display_timezone' => [
            'name' => tra('Displayed time zone'),
            'description' => tra('Use time zone set from user preferences, or the automatically detected time zone for anonymous (if browser allows). Site default is used as fallback.'),
            'type' => 'radio',
            'options' => [
                'Site' => tra('Always the site default time zone.'),
                'Local' => tra('Use time zone set from user preferences, or the automatically detected time zone for anonymous (if browser allows). Site default is used as fallback.'),
            ],
            'default' => 'Local',
            'tags' => ['basic'],
        ],
        'users_prefs_userbreadCrumb' => [
            'name' => tra('Number of visited pages to remember'),
            'type' => 'list',
            'units' => tra('visited pages'),
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '10' => '10',
            ],
            'default' => '4',
        ],
        'users_prefs_user_information' => [
            'name' => tra('User information'),
            'type' => 'list',
            'description' => tr('Specify if the user\'s information is public or private.'),
            'options' => [
                'private' => tra('Private'),
                'public' => tra('Public'),
            ],
            'default' => 'private',
            'tags' => ['basic'],
        ],
        'users_prefs_display_12hr_clock' => [
            'name' => tra('Use 12-hour clock for time selectors'),
            'type' => 'flag',
            'description' => tra('Use the 12-hour clock (with AM and PM) in some edit screens to set the time for publishing new or edited blog posts, articles, etc.'),
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'users_prefs_diff_versions' => [
            'name' => tra('Use interface that shows differences in any versions'),
            'type' => 'flag',
            'dependencies' => [
                'feature_wiki',
            ],
            'default' => 'n',
        ],
        'users_prefs_show_mouseover_user_info' => [
            'name' => tra("Pre-set show user's info on mouseover"),
            'description' => tra("Set new users' info to display in a popup when their name is hovered over."),
            'type' => 'flag',
            'dependencies' => [
                'feature_community_mouseover',
            ],
            'default' => 'n',
        ],
        'users_prefs_tasks_maxRecords' => [
            'name' => tra('Tasks per page'),
            'type' => 'list',
            'units' => tra('tasks'),
            'options' => [
                '2' => '2',
                '5' => '5',
                '10' => '10',
                '20' => '20',
                '30' => '30',
                '40' => '40',
                '50' => '50',
            ],
            'dependencies' => [
                'feature_tasks',
            ],
            'help' => 'Tasks',
            'default' => '10',
        ],
        'users_prefs_mess_maxRecords' => [
            'name' => tra('Messages per page'),
            'type' => 'list',
            'units' => tra('messages'),
            'dependencies' => [
                'feature_messages',
            ],
            'options' => [
                '2' => '2',
                '5' => '5',
                '10' => '10',
                '20' => '20',
                '30' => '30',
                '40' => '40',
                '50' => '50',
            ],
            'default' => '10',
        ],
        'users_prefs_allowMsgs' => [
            'name' => tra('Allow messages from other users'),
            'type' => 'flag',
            'dependencies' => [
                'feature_messages',
            ],
            'default' => 'y',
        ],
        'users_prefs_mess_sendReadStatus' => [
            'name' => tra('Notify sender when reading mail'),
            'type' => 'flag',
            'dependencies' => [
                'feature_messages',
            ],
            'default' => 'n',
        ],
        'users_prefs_minPrio' => [
            'name' => tra('Send me an email for messages with priority equal to or greater than'),
            'type' => 'list',
            'units' => tra('priority'),
            'dependencies' => [
                'feature_messages',
            ],
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => tra('None'),
            ],
            'default' => '3',
        ],
        'users_prefs_mess_archiveAfter' => [
            'name' => tra('Auto-archive read messages after'),
            'type' => 'list',
            'description' => tr('The number of days after which Tiki will archive the messages that have been read by the user.'),
            'dependencies' => [
                'feature_messages',
            ],
            'options' => [
                '0' => tra('Never'),
                '1' => '1',
                '2' => '2',
                '5' => '5',
                '10' => '10',
                '20' => '20',
                '30' => '30',
                '40' => '40',
                '50' => '50',
                '60' => '60',
            ],
            'units' => tra('days'),
            'default' => '0',
        ],
        'users_prefs_mytiki_pages' => [
            'name' => tra('My pages'),
            'type' => 'flag',
            'description' => tr('List all wiki pages edited by the user.'),
            'dependencies' => [
                'feature_wiki',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_blogs' => [
            'name' => tra('My blogs'),
            'type' => 'flag',
            'description' => tr('List all blogs and blog posts edited by the user.'),
            'dependencies' => [
                'feature_blogs',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_gals' => [
            'name' => tra('My galleries'),
            'type' => 'flag',
            'description' => tr('List all galleries edited by the user.'),
            'dependencies' => [
                'feature_galleries',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_msgs' => [
            'name' => tra('My messages'),
            'type' => 'flag',
            'description' => tr('List all messages and replies by the user.'),
            'dependencies' => [
                'feature_messages',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_tasks' => [
            'name' => tra('My tasks'),
            'type' => 'flag',
            'description' => tr('List all tasks by the user.'),
            'dependencies' => [
                'feature_tasks',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_forum_topics' => [
            'name' => tra('My forum topics'),
            'type' => 'flag',
            'description' => tr('List all forum topics by the user.'),
            'dependencies' => [
                'feature_forums',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_forum_replies' => [
            'name' => tra('My forum replies'),
            'type' => 'flag',
            'description' => tr('List all forum replies by the user.'),
            'dependencies' => [
                'feature_forums',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mytiki_items' => [
            'name' => tra('My items'),
            'type' => 'flag',
            'description' => tr('List all tracker items by the user.'),
            'dependencies' => [
                'feature_trackers',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'users_prefs_mailCharset' => [
            'name' => tra('Character set for mail'),
            'type' => 'list',
            'options' => [
                '' => 'default',
                'utf-8' => 'utf-8',
                'iso-8859-1' => 'iso-8859-1',
            ],
            'default' => 'utf-8',
        ],
        'users_prefs_remember_closed_rboxes' => [
            'name' => tra('Keep closed remarksbox hidden'),
            'description' => tra("Remember the alert boxes that users have closed and don't display them again."),
            'type' => 'flag',
            'default' => 'n',
        ],
        'users_prefs_xmpp_jid' => [
            'name' => tra('XMPP account JID or username'),
            'description' => tra('XMPP account JID or username'),
            'keywords' => 'xmpp converse conversejs chat',
            'type' => 'text',
            'default' => '',
        ],
        'users_prefs_xmpp_password' => [
            'name' => tra('XMPP account password'),
            'description' => tra('XMPP account password'),
            'keywords' => 'xmpp converse conversejs chat',
            'type' => 'text',
            'default' => '',
        ],
        'users_prefs_xmpp_custom_server_http_bind' => [
            'name' => tra('XMPP http-bind URL'),
            'description' => tra('Full URL to the http-bind.'),
            'keywords' => 'xmpp converse conversejs chat',
            'type' => 'text',
            'size' => 40,
            'filter' => 'url',
            'hint' => tra('https://xmpp.example.org/http-bind/'),
            'tags' => ['basic'],
            'default' => '',
        ],
    ];
}
