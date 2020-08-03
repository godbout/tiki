<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_webdocviewer_info()
{
    return [
        'name' => tra('Web Document Viewer'),
        'documentation' => 'PluginWebDocViewer',
        'description' => tra('View a document in a page'),
        'prefs' => [ 'wikiplugin_webdocviewer' ],
        'tags' => [ 'basic' ],
        'iconname' => 'copy',
        'introduced' => 12,
        'params' => [
            'fileId' => [
                'required' => false,
                'name' => tra('File Id'),
                'description' => tra('The FileId of a file in a File Gallery of the file you wish to embed in the viewer.'),
                'since' => '12.0',
                'filter' => 'digits',
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tr('The URL of the file you wish to embed in the viewer. If the file is stored in
					File Galleries, please use the %0 parameter', '<code>fileId</code>'),
                'since' => '12.0',
                'filter' => 'url',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels'),
                'since' => '12.0',
                'filter' => 'digits',
                'default' => 425,
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels'),
                'since' => '12.0',
                'filter' => 'digits',
                'default' => 350,
            ],
        ],
    ];
}

function wikiplugin_webdocviewer($data, $params)
{
    global $tikilib;
    global $tikipath, $tikiroot;

    $plugininfo = wikiplugin_webdocviewer_info();
    foreach ($plugininfo['params'] as $key => $param) {
        if (isset($param['default'])) {
            $default["$key"] = $param['default'];
        }
    }
    $params = array_merge($default, $params);


    if (isset($params['fileId'])) {
        $url = $_SERVER['HTTP_HOST'] . $tikiroot . 'tiki-download_file.php?fileId=' . $params['fileId'];
    } elseif (isset($params['url'])) {
        $url = $params['url'];
    }

    if (isset($url)) {
        $iframe = ('<iframe src="//docs.google.com/viewer?embedded=true&url=' . $url . '" width="' . $params['width'] . '" height="' . $params['height'] . '" style="border: none;"></iframe>');

        return '~np~' . $iframe . '~/np~';
    }

    return '~np~' . tra('No FileId or URL has been set') . '~/np~';
}
