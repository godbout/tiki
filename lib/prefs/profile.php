<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_profile_list()
{
    return [
        'profile_sources' => [
            'name' => tra('Repository URLs'),
            'description' => tra('List of URLs for the profile repositories that will be used'),
            'type' => 'textarea',
            'size' => 5,
            'hint' => tra('Enter multiple repository URLs, one per line.'),
            'default' => 'http://profiles.tiki.org/profiles',
        ],
        'profile_channels' => [
            'name' => tra('Data channels'),
            'description' => tra('Data channels are templates that can be applied from a post request. They can be used to automate work on more complex installations.'),
            'type' => 'textarea',
            'size' => 5,
            'hint' => tra('Data channels create a named pipe to run profiles from user space. One channel per line. Each line is comma delimited and contains __channel name, domain, profile, allowed groups, (optional) $profilerequest:input$ matches to groups__.'),
            'help' => 'http://profiles.tiki.org/Data+Channels',
            'warning' => tra('There are security considerations related to using data channels. Make sure the profile page is controlled by administrators only.'),
            'default' => '',
        ],
        'profile_unapproved' => [
            'name' => tra('Developer mode'),
            'description' => tra('For profiles under an approval workflow, always use the latest version, even if not approved.'),
            'type' => 'flag',
            'warning' => tra('Make sure you review the profiles you install.'),
            'default' => 'n',
        ],
        'profile_autoapprove_wikiplugins' => [
            'name' => tra('Automatically approve wiki-plugins on pages installed by profiles'),
            'description' => tra('Some wiki-plugins require admin approval before they are executable. If turned on, then all wiki-plugins that are on wiki pages created via profiles are automatically approved.'),
            'warning' => tra('Make sure your profiles are not executable or editable by untrusted users.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
    ];
}
