<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_html_info()
{
    return [
        'name' => tra('HTML'),
        'documentation' => 'PluginHTML',
        'description' => tra('Add HTML to a page'),
        'prefs' => ['wikiplugin_html'],
        'body' => tra('HTML code'),
        'validate' => 'all',
        'filter' => 'rawhtml_unsafe',
        'iconname' => 'code',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [
            'tohead' => [
                'required' => false,
                'name' => tra('Move to HTML head'),
                'description' => tra('Insert the code in the HTML head section rather than in the body.'),
                'since' => '17.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
                'filter' => 'digits',
                'default' => '0',
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki Syntax'),
                'description' => tra('Parse wiki syntax within the HTML code.'),
                'since' => '3.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
                'filter' => 'digits',
                'default' => '0',
            ],
        ],
    ];
}

function wikiplugin_html($data, $params)
{
    if (! isset($params['wiki'])) {
        $params['wiki'] = 0;
    }

    // strip out sanitation which may have occurred when using nested plugins
    $html = str_replace('<x>', '', $data);

    // parse using is_html if wiki param set, or just decode html entities
    if ($params['wiki'] == 1) {
        $html = TikiLib::lib('parser')->parse_data($html, ['is_html' => true, 'parse_wiki' => true]);
    } else {
        $html = html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }

    if (isset($params['tohead']) && $params['tohead'] == 1) {
        // Insert in HTML head rather than in body
        TikiLib::lib('header')->add_rawhtml($html);
    } else {
        return '~np~' . $html . '~/np~';
    }
}
