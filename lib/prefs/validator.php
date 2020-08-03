<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_validator_list()
{
    return [
        'validator_emails' => [
            'name' => tra('Validator emails (separated by comma) if different than the sender email'),
            'type' => 'text',
            'size' => 20,
            'default' => '',
        ],
    ];
}
