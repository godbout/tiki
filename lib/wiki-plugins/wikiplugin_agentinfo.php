<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_agentinfo_info()
{
    return [
        'name' => tra('User Agent Info'),
        'documentation' => 'PluginAgentinfo',
        'description' => tra('Show the user\'s browser and server information.'),
        'prefs' => ['wikiplugin_agentinfo'],
        'introduced' => 1,
        'iconname' => 'computer',
        'params' => [
            'info' => [
                'required' => false,
                'name' => tra('Info'),
                'description' => tra('Display\'s the visitor\'s IP address (IP or default), browser information (BROWSER), or server software (SVRSW).'),
                'default' => 'IP',
                'filter' => 'alpha',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('IP address'), 'value' => 'IP'],
                    ['text' => tra('Server software'), 'value' => 'SVRSW'],
                    ['text' => tra('Browser'), 'value' => 'BROWSER'],
                ],

            ],
        ],
    ];
}

function wikiplugin_agentinfo($data, $params)
{
    global $tikilib;

    extract($params, EXTR_SKIP);

    $asetup = '';

    if (! isset($info)) {
        $info = 'IP';
    }

    if ($info == 'IP') {
        $asetup = $tikilib->get_ip_address();
    }

    if ($info == 'SVRSW' && isset($_SERVER['SERVER_SOFTWARE'])) {
        $asetup = $_SERVER["SERVER_SOFTWARE"];
    }

    if ($info == 'BROWSER' && isset($_SERVER['HTTP_USER_AGENT'])) {
        $asetup = $_SERVER["HTTP_USER_AGENT"];
    }

    return $asetup;
}
