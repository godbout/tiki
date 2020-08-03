<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_newsletter_list()
{
    return [
        'newsletter_throttle' => [
            'name' => tra('Throttle newsletter send rate'),
            'description' => tra('Pause for a given amount of seconds before each batch to avoid overloading the mail server.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_newsletters',
            ],
        ],
        'newsletter_pause_length' => [
            'name' => tra('Newsletter pause length'),
            'description' => tra('Number of seconds delay before each batch'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'units' => tra('seconds'),
            'default' => 60,
        ],
        'newsletter_batch_size' => [
            'name' => tra('Newsletter batch size'),
            'description' => tra('Number of emails to send in each batch'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'units' => tra('emails'),
            'default' => 5,
        ],
        'newsletter_external_client' => [
            'name' => tra('Allow sending newsletters through external clients'),
            'description' => tra('Generate mailto links using the recipients as the BCC list.'),
            'type' => 'flag',
            'default' => 'n',
            'warning' => tra('This will expose the list if email addresses to all users allowed to send newsletters.'),
            'dependencies' => [
                'feature_newsletters',
            ],
        ],
    ];
}
