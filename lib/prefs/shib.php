<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_shib_list()
{
    return [
        'shib_group' => [
            'name' => tra('Default group'),
            'description' => tr('The name of the default group'),
            'type' => 'text',
            'size' => 40,
            'perspective' => false,
            'default' => 'Shibboleth',
        ],
        'shib_usegroup' => [
            'name' => tra('Create with default group'),
            'A default group will be created. If no group is specified a default of Shibboleth will be used.',
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
        ],
        'shib_affiliation' => [
            'name' => tra('Valid affiliations'),
            'description' => tr('A list of affiliations which will allow users to log in to this wiki'),
            'type' => 'text',
            'size' => 40,
            'hint' => tra('Separate multiple affiliations with commas'),
            'perspective' => false,
            'default' => '',
        ],
        'shib_skip_admin' => [
            'name' => tra('Use Tiki authentication for Admin log-in'),
            'type' => 'flag',
            'description' => tra('The user “admin” will be authenticated by <b>only</b> using Tiki’s user database. This option has no effect on users other than “admin”.'),
            'perspective' => false,
            'default' => 'n',
        ],
        'shib_create_user_tiki' => [
            'name' => tra('Create user if not registered in Tiki'),
            'type' => 'flag',
            'perspective' => false,
            'description' => tr('If a user was externally authenticated, but not found in the Tiki user database, Tiki will create an entry in its user database.'),
            'default' => 'n',
        ],
    ];
}
