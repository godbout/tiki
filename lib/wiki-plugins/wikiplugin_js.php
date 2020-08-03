<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_js_info()
{
    return [
        'name' => tra('JavaScript'),
        'documentation' => 'PluginJS',
        'description' => tra('Add JavaScript code or files'),
        'prefs' => [ 'wikiplugin_js' ],
        'body' => tra('JavaScript code'),
        'validate' => 'all',
        'filter' => 'rawhtml_unsafe',
        'iconname' => 'code',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'file' => [
                'required' => false,
                'name' => tra('File'),
                'description' => tra('JavaScript filename'),
                'since' => '3.0',
                'filter' => 'url',
                'default' => '',
            ],
            'lateload' => [
                'required' => false,
                'name' => tra('Late Load'),
                'description' => tra('Late load, use headerlib'),
                'since' => '9.1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'default' => '',
                'filter' => 'alpha',
                'advanced' => true,
            ],
        ],
    ];
}
function wikiplugin_js($data, $params)
{
    $headerlib = TikiLib::lib('header');
    extract($params, EXTR_SKIP);

    if (isset($lateload) && $lateload == 'y') {
        if (isset($file)) {
            $headerlib->add_jsfile($file);
        } elseif ($data) {
            $headerlib->add_js($data);
        }

        return '';
    }

    if (isset($file)) {
        $ret = "~np~<script type=\"text/javascript\" src=\"$file\"></script> ~/np~";
    } else {
        $ret = '';
    }
    if ($data) {
        $ret .= "~np~<script type=\"text/javascript\">" . $data . "</script>~/np~";
    }

    return $ret;
}
