<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_activitystream_info()
{
    return [
        'name' => tra('Activity Stream'),
        'documentation' => 'PluginActivityStream',
        'description' => tra('Create a social network activity stream.'),
        'prefs' => ['wikiplugin_activitystream', 'feature_search'],
        'default' => 'y',
        'introduced' => 12,
        'format' => 'html',
        'body' => tra('List configuration information'),
        'filter' => 'wikicontent',
        'profile_reference' => 'search_plugin_content',
        'iconname' => 'move',
        'tags' => [
            'advanced',
            'experimental' // Poor interface, poor documentation (hour-long outdated video in insufficient resolution). Chealer 2017-03-05
        ],
        'params' => [
            'auto' => [
                'name' => tr('Auto-Scroll'),
                'description' => tr('Automatically load next page of results when scrolling down.'),
                'default' => 0,
                'filter' => 'digits',
                'since' => '12.0',
                'options' => [
                    ['value' => 0, 'text' => tr('Off')],
                    ['value' => 1, 'text' => tr('On')],
                ],
            ],
        ],
    ];
}

function wikiplugin_activitystream($data, $params)
{
    $encoded = Tiki_Security::get()->encode([
        'body' => $data,
    ]);

    $servicelib = TikiLib::lib('service');

    return $servicelib->render('activitystream', 'render', [
        'autoscroll' => isset($params['auto']) && $params['auto'],
        'stream' => $encoded,
    ]);
}
