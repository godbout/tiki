<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_fullwidthtitle_info()
{
    return [
        'name' => tra('Set a Full-Width Page Title'),
        'description' => tra('Display the page title the full width of the site content container. '),
        'documentation' => tra('PluginFullWidthTitle'),
        'default' => 'y',
        'format' => 'html',
        'filter' => 'wikicontent',
        'introduced' => 15,
        'iconname' => 'title',
        'tags' => ['advanced'],
        'params' => [
            'title' => [
                'name' => tr('Page title'),
                'description' => tr('If you need to include tpl files.'),
                'since' => '15.0',
                'required' => true,
                'filter' => 'text'
            ],
            'iconsrc' => [
                'name' => tr('Icon Source'),
                'description' => tr('Source path of the icon.'),
                'since' => '15.0',
                'required' => false,
                'filter' => 'text'
            ],
        ],
    ];
}

function wikiplugin_fullwidthtitle($data, $params)
{
    $smarty = TikiLib::lib('smarty');

    $smarty->assign('title', $params['title']);
    if (! empty($params['iconsrc'])) {
        $smarty->assign('iconsrc', $params['iconsrc']);
    }

    return $smarty->fetch('templates/wiki-plugins/wikiplugin_fullwidthtitle.tpl');
}
