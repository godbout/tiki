<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function validator_username($input, $parameter = '', $message = '')
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    if ($prefs['login_is_email'] === 'y') {
        if ($userlib->get_user_by_email($input)) {
            return tra("Email already in use");
        }
        if (! validate_email($input)) {
            return tra("Invalid email");
        }
    } else {
        if ($userlib->user_exists($input)) {
            return tra("User already exists");
        }
        if (! empty($prefs['username_pattern']) && ! preg_match($prefs['username_pattern'], $input)) {
            return tra("Invalid character combination for username");
        }
        if (strtolower($input) == 'anonymous' || strtolower($input) == 'registered') {
            return tra("Invalid username");
        }
        if (strlen($input) > $prefs['max_username_length']) {
            $error = tr("Username cannot contain more than %0 characters", $prefs['max_username_length']);

            return $error;
        }
        if (strlen($input) < $prefs['min_username_length']) {
            $error = tr("Username must be at least %0 characters long", $prefs['min_username_length']);

            return $error;
        }
        if ($prefs['lowercase_username'] === 'y' && preg_match('/[A-Z]/', $input) !== 0) {
            $error = tr("Username must be all lower case");

            return $error;
        }
    }

    return true;
}
