<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_getaccesstoken_info()
{
    return [
        'name' => tra('Get Security Token'),
        'documentation' => tra('PluginGetAccessToken'),
        'description' => tra('Display a link on a secure page using an access token'),
        'prefs' => [ 'auth_token_access', 'wikiplugin_getaccesstoken' ],
        'inline' => true,
        'validate' => 'all',
        'iconname' => 'lock',
        'filter' => 'wikicontent',
        'introduced' => 7,
        'params' => [
            'entry' => [
                'required' => true,
                'name' => tra('Entry Patg'),
                'description' => tra('The path or part of the path that the token is for'),
                'since' => '7.0',
                'filter' => 'text',
                'default' => ''
            ],
            'keys' => [
                'required' => false,
                'keys' => tra('Query Keys'),
                'description' => tra('Query string parameter keys that the token is for, separated by a colon (:)'),
                'since' => '7.0',
                'filter' => 'text',
                'default' => '',
                'separator' => ':'
            ],
            'values' => [
                'required' => false,
                'name' => tra('Query Values'),
                'description' => tra('Query string parameter values that the token is for, separated by a colon (:)'),
                'since' => '7.0',
                'filter' => 'text',
                'default' => '',
                'separator' => ':'
            ],
        ],
    ];
}

function wikiplugin_getaccesstoken($data, $params)
{
    global $tikilib;
    if (! isset($params['entry'])) {
        return '';
    }
    $entry = $params['entry'];
    
    if (! isset($params['keys'])) {
        $keys = [];
    } else {
        $keys = $params['keys'];
    }
    if (! isset($params['keys'])) {
        $values = [];
    } else {
        $values = $params['values'];
    }
    $bindvars = [];
    $bindvars[] = "%$entry%";
    $querystringvars = [];
    for ($i = 0, $count_keys = count($keys); $i < $count_keys; $i++) {
        $querystringvars[$keys[$i]] = $values[$i];
    }
    if (! empty($querystringvars)) {
        $encoded = json_encode($querystringvars);
        $mid = " and `parameters` = ?";
        $bindvars[] = $encoded;
    }
    $query = "select `token` from `tiki_auth_tokens` where `entry` like ? $mid";
    if ($ret = $tikilib->getOne($query, $bindvars)) {
        return $ret;
    }

    return '';
}
