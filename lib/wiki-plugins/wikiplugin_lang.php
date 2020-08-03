<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_lang_info()
{
    return [
        'name' => tra('Language'),
        'documentation' => 'PluginLang',
        'description' => tra('Show alternative content based on the page language.'),
        'prefs' => [ 'feature_multilingual', 'wikiplugin_lang' ],
        'body' => tra('Content to show'),
        'iconname' => 'language',
        'introduced' => 1,
        'params' => [
            'lang' => [
                'required' => false,
                'name' => tra('Language'),
                'description' => tr('List of languages for which the block is displayed. Languages use the two letter
					language codes (ex: en, fr, es, ...). Use %0 to separate multiple languages.', '<code>+</code>'),
                'since' => '1',
                'default' => '',
            ],
            'notlang' => [
                'required' => false,
                'name' => tra('Not Language'),
                'description' => tr('List of languages for which the block is not displayed. Languages use the two
					letter language codes (ex: en, fr, es, ...). Use %0 to separate multiple languages.', '<code>+</code>'),
                'since' => '1',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_lang($data, $params)
{
    global $prefs;

    $reqlang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : $prefs['language'];
    extract($params, EXTR_SKIP);
    if (isset($lang)) {
        return in_array($reqlang, explode('+', $lang)) ? $data : '';
    }
    if (isset($notlang)) {
        return in_array($reqlang, explode('+', $notlang)) ? '' : $data;
    }

    return $data;
}
