<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_language_list($partial = false)
{
    $map = [];
    $adminMap = [];

    if (! $partial) {
        $langLib = TikiLib::lib('language');
        $languages = $langLib->list_languages(false, null, true);
        foreach ($languages as $lang) {
            $map[ $lang['value'] ] = $lang['name'];
        }
    }
    $adminMap[''] = tr('Default language');
    $adminMap = array_merge($adminMap, $map);

    return [
        'language' => [
            'name' => tra('Default language'),
            'description' => tra('The site language is used when no other language is specified by the user.'),
            'filter' => 'lang',
            'help' => 'I18n',
            'type' => 'list',
            'options' => $map,
            'default' => 'en',
            'tags' => ['basic'],
        ],
        'language_admin' => [
            'name' => tr('Default admin language'),
            'description' => tr('The site language is used in admin section when no other language is specified by the user.'),
            'filter' => 'lang',
            'help' => 'I18n',
            'type' => 'list',
            'options' => $adminMap,
            'default' => '',
            'tags' => ['basic'],
        ],
        'language_inclusion_threshold' => [
            'name' => tra('Language inclusion threshold'),
            'description' => tra('When the number of languages is restricted on the site, and is below this number, all languages will be added to the preferred language list, even if unspecified by the user. However, priority will be given to the specified languages.'),
            'help' => 'Internationalization',
            'type' => 'text',
            'filter' => 'digits',
            'units' => tra('languages'),
            'size' => 2,
            'dependencies' => ['restrict_language', ],
            'default' => 3,
        ],
    ];
}
