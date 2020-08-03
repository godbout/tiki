<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_quantify_list()
{
    return [
        'quantify_changes' => [
            'name' => tra('Quantify change size'),
            'description' => tra('In addition to tracking the changes, track the change size and display approximately how up-to-date the page is.'),
            'type' => 'flag',
            'dependencies' => ['feature_multilingual'],
            'default' => 'n',
        ],
    ];
}
