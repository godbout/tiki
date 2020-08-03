<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_captcha_list()
{
    return  [
        'captcha_wordLen' => [
            'name' => tra('CAPTCHA image word length'),
            'description' => tra('Number of characters the CAPTCHA will display.'),
            'type' => 'text',
            'default' => 6,
            'units' => tra('characters'),
        ],
        'captcha_width' => [
            'name' => tra('CAPTCHA image width'),
            'description' => tra('Width of the CAPTCHA image in pixels.'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => 180,
        ],
        'captcha_noise' => [
            'name' => tra('CAPTCHA image noise'),
            'description' => tra('Level of noise of the CAPTCHA image.'),
            'hint' => tra('Choose a smaller number for less noise and easier reading.'),
            'type' => 'text',
            'default' => 100,
        ],
        'captcha_questions_active' => [
            'name' => tra('CAPTCHA questions'),
            'description' => tra('Requires anonymous visitors to enter the answer to a question.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_antibot',
            ],
            'default' => 'n',
        ],
        'captcha_questions' => [
            'name' => tra('CAPTCHA questions and answers'),
            'description' => tra('Add some simple questions that only humans should be able to answer, in the format: "Question?: Answer" with one per line'),
            'hint' => tra('One question per line with a colon separating the question and answer'),
            'type' => 'textarea',
            'size' => 6,
            'dependencies' => [
                'captcha_questions_active',
            ],
            'default' => '',
        ],
    ];
}
