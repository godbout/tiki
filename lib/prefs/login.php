<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function prefs_login_list()
{
    return [
        'login_is_email' => [
            'name' => tra('Use email as username'),
            'description' => tra('Instead of creating new usernames, use the user\'s email address for authentication. On the registration form, there will be no Username field.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'login_is_email_obscure' => [
            'name' => tra('Obscure email when using email as username'),
            'description' => tra('This will attempt as much as possible to hide the email address, showing the real name or the truncated email address instead.'),
            'warning' => tra('Coverage will not be complete'),
            'type' => 'flag',
            'dependencies' => [
                'login_is_email',
            ],
            'default' => 'n',
        ],
        'login_allow_email' => [
            'name' => tra('User can login via username or email.'),
            'description' => tra('This will allow users to login using their email (as well as their username).'),
            'type' => 'flag',
            'dependencies' => [
                'user_unique_email',
            ],
            'default' => 'n',
        ],
        'login_autogenerate' => [
            'name' => tra('Auto-generate 6-digit username on registration'),
            'description' => tra('This will auto-generate a 6-digit username for users who sign up (they will normally log in with emails only).'),
            'type' => 'flag',
            'dependencies' => [
                'user_unique_email',
                'login_allow_email',
            ],
            'default' => 'n',
        ],
        'login_http_basic' => [
            'name' => tr('HTTP Basic Authentication'),
            'description' => tr('Check credentials from HTTP Basic Authentication, which is useful to allow webservices to use credentials.'),
            'type' => 'list',
            'filter' => 'alpha',
            'default' => 'n',
            'options' => [
                'n' => tr('Disable'),
                'ssl' => tr('SSL Only (Recommended)'),
                'always' => tr('Always'),
            ],
        ],
        'login_multiple_forbidden' => [
            'name' => tr('Prevent multiple log-ins by the same user'),
            'description' => tr('Users (other than admin) cannot log in simultaneously with multiple browsers.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'login_grab_session' => [
            'name' => tr('Grab session if already logged in'),
            'description' => tr('If users are blocked from logging in simultaneously, grab the session. Will force existing user to be logged out'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'login_multiple_forbidden',
            ],
            'tags' => ['advanced'],
        ],
        'login_autologin' => [
            'name' => tr('Enable autologin from remote Tiki'),
            'description' => tr('Used with autologin_remotetiki in the redirect plugin'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'Remote+Tiki+Autologin',
            'tags' => ['advanced'],
            'dependencies' => [
                'login_autologin_user',
                'login_autologin_group',
                'auth_token_access',
            ],
        ],
        'login_autologin_user' => [
            'name' => tr('System username to use to initiate autologin from remote Tiki'),
            'description' => tr('Specified user must exist and be configured in Settings...Tools...DSN/Content Authentication on remote Tiki. Used with autologin_remotetiki in the redirect plugin.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
        ],
        'login_autologin_group' => [
            'name' => tr('System groupname to use for auto login token'),
            'description' => tr('For security, please create a group that has no users and no permissions and specify its name here.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
        ],
        'login_autologin_createnew' => [
            'description' => tr('Create a new user account if the user that is trying to autologin does not exist on this Tiki.'),
            'type' => 'flag',
            'default' => 'y',
            'name' => tr('Create user if not registered in Tiki'),
            'tags' => ['advanced'],
        ],
        'login_autologin_allowedgroups' => [
            'name' => tr('Allowed groups from remote Tiki to autologin.'),
            'description' => tr('Comma-separated list of groups to allow autologin from remote Tiki. If empty, will allow everyone.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
        ],
        'login_autologin_syncgroups' => [
            'name' => tr('Sync these groups from remote Tiki on autologin.'),
            'description' => tr('Comma-separated list of groups to sync from remote Tiki on autologin. Group membership will be added or removed accordingly.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
        ],
        'login_autologin_logoutremote' => [
            'name' => tr('Automatically logout remote Tiki after logout.'),
            'description' => tr('When the user logs out of this Tiki, redirect the user to logout of the other Tiki as well.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced'],
        ],
        'login_autologin_redirectlogin' => [
            'name' => tr('Redirect direct logins to this site to remote Tiki'),
            'description' => tr('Redirect direct logins to this site to remote Tiki'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'login_autologin_redirectlogin_baseurl',
                'permission_denied_login_box',
            ],
            'tags' => ['advanced'],
        ],
        'login_autologin_redirectlogin_url' => [
            'name' => tr('URL of autologin page on remote Tiki to redirect user to login'),
            'description' => tr('URL of autologin page on remote Tiki to redirect user to login, e.g. https://www.remotetiki.com/PageWithRedirectPlugin'),
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
        ],
    ];
}
