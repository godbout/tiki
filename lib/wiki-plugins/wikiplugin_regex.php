<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_regex_info()
{
    return [
        'name' => tra('Regular Expression'),
        'documentation' => 'PluginRegex',
        'validate' => 'all',
        'description' => tra('Perform a regular expression search and replace'),
        'prefs' => [ 'wikiplugin_regex' ],
        'body' => tra('Each line of content is evaluated separately'),
        'iconname' => 'search',
        'introduced' => 1,
        'params' => [
            'pageName' => [
                'required' => true,
                'name' => tra('Page name'),
                'description' => tra('Name of page containing search and replace expressions separated by two colons.
					Example of syntax on that page:') . ' <code>/search pattern/::replacement text</code>',
                'since' => '1',
                'default' => 'pageName',
                'profile_reference' => 'wiki_page',
            ],
        ],
    ];
}

function wikiplugin_regex($data, $params)
{
    global $tikilib;

    extract($params, EXTR_SKIP);
    $pageName = (isset($pageName)) ? $pageName : 'pageName';//gets a page
    $info = $tikilib->get_page_info($pageName);
    $content = $info['data'];
    $lines = explode("\n", $content); // separate lines into array no emtpy lines at beginning mid or end
    $i = 0;
    foreach ($lines as $line) {
        list($pattern[$i], $replace[$i]) = explode("::", $line);// use two colons to separate your find and replace
        $i++;
    }

    $data = preg_replace($pattern, $replace, $data);
    $data = trim($data);

    return $data;
}
