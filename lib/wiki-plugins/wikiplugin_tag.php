<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_tag_info()
{
    return [
        'name' => tra('Tag'),
        'documentation' => 'PluginTag',
        'description' => tra('Apply an HTML tag to content'),
        'prefs' => [ 'wikiplugin_tag' ],
        'validate' => 'all',
        'body' => tra('text'),
        'iconname' => 'code',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => [
            'tag' => [
                'required' => false,
                'name' => tra('Tag Name'),
                'description' => tr('Any valid HTML tag, 0% by default', '<code>span</code>'),
                'since' => '1',
                'filter' => 'text',
                'default' => 'span',
            ],
            'style' => [
                'required' => false,
                'name' => tra('CSS Style'),
                'description' => tra('Equivalent to the style attribute of an HTML tag.'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_tag($data, $params)
{
    extract($params, EXTR_SKIP);
    if (! isset($tag)) {
        $tag = 'span';
    } else {
        // remove eveyrything what's not a word to allow only tags without attributes
        $tag = preg_replace("/[^\w]/", "", $tag);
    }

    if (isset($style)) {
        // trim quotes from the begin and end of style
        $style = ' style="' . trim($style, "\'\"") . '"';
    } else {
        $style = '';
    }

    return "<$tag$style>$data</$tag>";
}
