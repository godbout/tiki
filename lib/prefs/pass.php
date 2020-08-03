<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function prefs_pass_list()
{
    $blackL = TikiLib::lib('blacklist');

    return [
        'pass_chr_num' => [
            'name' => tra('Require characters and numerals'),
            'description' => tra('For improved security, require users to include a mix of alphabetical characters and numerals in passwords.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'pass_blacklist_file' => [
            'name' => tra('Password file used'),
            'description' => tra('The automatically selected file is recommended unless you generate your own blacklist file.'),
            'type' => 'list',
            'default' => 'auto',
            'filter' => 'striptags',
            'options' => array_merge(
                ['auto' => 'Automatically select blacklist'],
                $blackL->genIndexedBlacks()
            )
        ],
        'pass_blacklist' => [
            'name' => tra('Prevent common passwords'),
            'description' => tra('For improved security, prevent users from creating blacklisted passwords. Use default blacklist or create custom blacklists through Control Panel -> Log in -> Password Blacklist.'),
            'help' => 'Password-Blacklists',
            'type' => 'flag',
            'default' => 'n',
        ],
        'pass_due' => [
            'name' => tra('Password expires after'),
            'description' => tra('The number of days after which a password will expire. Days are counted starting with the user’s first login. When the password expires, users will be required to select a new password when logging in.'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'int',
            'units' => tra('days'),
            'hint' => tra('Use "-1" for never'),
            'default' => -1,
        ],
        'pass_chr_case' => [
            'name' => tra('Require alphabetical characters in lower and upper case'),
            'description' => tra('Password must contain at least one lowercase alphabetical character like "a" and one uppercase character like "A". Use this option to require users to select stronger passwords.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'pass_chr_special' => [
            'name' => tra('Require special characters'),
            'description' => tra('Password must contain at least one special character in lower case like <b>" / $ % ? & * ( ) _ + .</b> Use this option to require users to select stronger passwords.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'pass_repetition' => [
            'name' => tra('Require no consecutive repetition of the same character'),
            'description' => tra('Password must not contain a consecutive repetition of the same character such as "111" or "aab".'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'pass_diff_username' => [
            'name' => tra('The password must be different from the user\'s log-in name'),
            'type' => 'flag',
            'default' => 'y',
        ],
    ];
}
