<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_googledoc_info()
{
    return [
        'name' => tra('Google Doc'),
        'documentation' => 'PluginGoogleDoc',
        'description' => tra('Display a Google document'),
        'prefs' => [ 'wikiplugin_googledoc' ],
        'body' => tra('Leave this empty.'),
//		'validate' => 'all',
        'iconname' => 'google',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [
            'type' => [
                'safe' => true,
                'required' => true,
                'name' => tra('Type'),
                'description' => tra('Type of Google document'),
                'since' => '3.0',
                'filter' => 'word',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Document'), 'value' => 'document'],
                    ['text' => tra('Slides'), 'value' => 'slides'],
                    ['text' => tra('Spreadsheet'), 'value' => 'spreadsheet'],
                    ['text' => tra('Forms'), 'value' => 'forms']
                ]
            ],
            'key' => [
                    'safe' => true,
                    'required' => true,
                    'name' => tra('Key'),
                    'description' => tra('Google doc key - for example:') . ' <code>pXsHENf1bGGY92X1iEeJJI</code>',
                    'since' => '3.0',
                    'filter' => 'text',
                    'default' => ''
                ],
            'name' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Name'),
                'description' => tra('Name of iframe. Default is "Frame" + the key'),
                'filter' => 'text',
                'since' => '3.0',
            ],
            'size' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Size'),
                'description' => tra('Size of frame. Use instead of width and height. The sizes will fit the Google
					presentations sizes exactly.'),
                'since' => '3.0',
                'filter' => 'word',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Small'), 'value' => 'small'],
                    ['text' => tra('Medium'), 'value' => 'medium'],
                    ['text' => tra('Large'), 'value' => 'large']
                ]
            ],
            'width' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels or %'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => 800
            ],
            'height' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels or %'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => 400
            ],
            'align' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Alignment'),
                'description' => tra('Position of frame on page'),
                'since' => '3.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Middle'), 'value' => 'middle'],
                    ['text' => tra('Bottom'), 'value' => 'bottom'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right']
                ]
            ],
            'frameborder' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Frame Border'),
                'description' => tra('Choose whether to show a border around the iframe'),
                'since' => '3.0',
                'default' => 0,
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'marginheight' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Margin Height'),
                'description' => tra('Margin height in pixels'),
                'filter' => 'digits',
                'since' => '3.0',
                'default' => ''
            ],
            'marginwidth' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Margin Width'),
                'description' => tra('Margin width in pixels'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => ''
            ],
            'scrolling' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Scrolling'),
                'description' => tra('Choose whether to add a scroll bar'),
                'since' => '3.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'yes'],
                    ['text' => tra('No'), 'value' => 'no'],
                    ['text' => tra('Auto'), 'value' => 'auto']
                ]
            ],
            'editLink' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Edit Link'),
                'description' => tra('Choose whether to show an edit link and set its location'),
                'since' => '3.0',
                'filter' => 'word',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Bottom'), 'value' => 'bottom'],
                    ['text' => tra('Both'), 'value' => 'both']
                ]
            ]
        ]
    ];
}

function wikiplugin_googledoc($data, $params)
{
    extract($params, EXTR_SKIP);

    if (empty($type)) {
        return tra('Required parameter "type" missing');
    }
    if (empty($key)) {
        return tra('Required parameter "key" missing');
    }

    if ($type == "spreadsheet") {
        $srcUrl = "\"https://docs.google.com/spreadsheets/d/$key\"";
        $editSrcUrl = "\"https://docs.google.com/spreadsheets/d/$key/edit#gid=0\"";
        $editHtml = " <p><a href=$editSrcUrl target=\"$frameName\">Edit this Google Document</a></p>";
    }
    if ($type == "document") {
        $srcUrl = "\"https://docs.google.com/document/d/$key\"";
        $editSrcUrl = "\"https://docs.google.com/document/d/$key/edit\"";
        $editHtml = " <p><a href=$editSrcUrl target=\"$frameName\">Edit this Google Document</a></p>";
    }
    if ($type == "slides") {
        $srcUrl = "\"https://docs.google.com/presentation/d/$key\"";
        $editSrcUrl = "\"https://docs.google.presentation/d/$key/edit\"";
        $editHtml = " <p><a href=$editSrcUrl target=\"$frameName\">Edit this Google Document</a></p>";
    }
    if ($type == "forms") {
        $srcUrl = "\"https://docs.google.com/forms/d/$key\"";
        $editSrcUrl = "\"https://docs.google.com/forms/d/$key/edit\"";
        $editHtml = " <p><a href=$editSrcUrl target=\"$frameName\">Edit this Google Document</a></p>";
    }

    $ret = "";

    if (isset($name)) {
        $frameName = $name;
    } else {
        $frameName = "Frame" . $key;
    }
    if ($editLink == 'both' or $editLink == 'top') {
        $ret .= $editHtml;
    }

    $ret .= '<iframe ';
    $ret .= " name=\"$frameName\"";

    if ($size == 'small') {
        $width = 410;
        $height = 342;
    }
    if ($size == 'medium') {
        $width = 555;
        $height = 451;
    }
    if ($size == 'large') {
        $width = 700;
        $height = 559;
    }

    if (isset($width)) {
        $ret .= " width=\"$width\"";
    } else {
        $ret .= " width=\"800\"";
    }
    if (isset($height)) {
        $ret .= " height=\"$height\"";
    } else {
        $ret .= " height=\"400\"";
    }

    if (isset($align)) {
        $ret .= " align=\"$align\"";
    }
    if (isset($frameborder)) {
        $ret .= " frameborder=\"$frameborder\"";
    } else {
        $ret .= " frameborder=0";
    }
    if (isset($marginheight)) {
        $ret .= " marginheight=\"$marginheight\"";
    }
    if (isset($marginwidth)) {
        $ret .= " marginwidth=\"$marginwidth\"";
    }
    if (isset($scrolling)) {
        $ret .= " scrolling=\"$scrolling\"";
    }
    if (isset($key)) {
        $ret .= " src=$srcUrl></iframe>";
    }
    if ($editLink == 'both' or $editLink == 'bottom') {
        $ret .= $editHtml;
    }

    $ret .= "";

    return $ret;
}
