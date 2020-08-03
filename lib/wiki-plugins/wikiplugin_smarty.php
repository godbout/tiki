<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_smarty_info()
{
    return [
        'name' => tra('Smarty function'),
        'documentation' => 'PluginSmarty',
        'description' => tra('Insert a Smarty function or variable'),
        'prefs' => ['wikiplugin_smarty'],
        'validate' => 'all',
        'extraparams' => true,
        'format' => 'html',
        'tags' => [ 'experimental' ],
        'iconname' => 'code',
        'introduced' => 5,
        'params' => [
            'name' => [
                'required' => true,
                'name' => tra('Smarty function'),
                'description' => tr(
                    'The name of the Smarty function that the plugin will activate. Available functions can be found at %0 and %1',
                    '<code>lib/smarty_tiki/function.(<strong>name</strong>).php</code>',
                    '<code>vendor_bundled/vendor/smarty/smarty/libs/plugins/function.(<strong>name</strong>).php</code>'
                ),
                'since' => '7.0',
                'filter' => 'word',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_smarty($data, $params)
{
    $smarty = TikiLib::lib('smarty');
    if (empty($params['name'])) {
        return tra('Incorrect parameter');
    }
    if ($params['name'] == 'eval') {
        $content = $smarty->fetch('string:' . $params['var']);
    } else {
        $path = 'lib/smarty_tiki/function.' . $params['name'] . '.php';
        if (! file_exists($path)) {
            $path = 'vendor_bundled/vendor/smarty/smarty/libs/plugins/function.' . $params['name'] . '.php';
            if (! file_exists($path)) {
                return tra('Incorrect parameter');
            }
        }
        include_once($path);
        $func = 'smarty_function_' . $params['name'];
        $content = $func($params, $smarty);
    }

    return $content;
}
