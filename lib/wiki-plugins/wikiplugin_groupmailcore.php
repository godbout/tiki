<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_groupmailcore_info()
{
    return [
        'name' => tra('GroupMail Core'),
        'documentation' => 'PluginGroupMailCore',
        'description' => tra('Display GroupMail functions on a page'),
        'prefs' => ['wikiplugin_groupmailcore', 'feature_trackers'],
        //'extraparams' => true,
        'iconname' => 'group',
        'tags' => [ 'experimental' ],
        'introduced' => 4,
        'params' => [
            'fromEmail' => [
                'required' => true,
                'name' => tra('From Email'),
                'description' => tra('Email address to report.'),
                'since' => '4.0',
                'default' => '',
            ],
            'trackerId' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('ID of GroupMail Logs tracker (set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'fromFId' => [
                'required' => true,
                'name' => tra('From Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'operatorFId' => [
                'required' => true,
                'name' => tra('Operator Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'subjectFId' => [
                'required' => true,
                'name' => tra('Subject Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'messageFId' => [
                'required' => true,
                'name' => tra('Message Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'contentFId' => [
                'required' => true,
                'name' => tra('Content Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'accountFId' => [
                'required' => true,
                'name' => tra('Account Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'datetimeFId' => [
                'required' => true,
                'name' => tra('Datetime Field ID'),
                'description' => tra('ID of GroupMail Logs tracker field (usually set up in alias by profile).'),
                'since' => '4.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
        ],
    ];
}

function wikiplugin_groupmailcore($data, $params)
{
    global $tikilib;
    require_once('lib/wiki-plugins/wikiplugin_trackerlist.php');

    $trackerparams = [];
    $trackerparams['trackerId'] = $params['trackerId'];
    $trackerparams['fields'] = $params['fromFId'] . ':' . $params['operatorFId'] . ':' . $params['subjectFId'] . ':' . $params['datetimeFId'];
    $trackerparams['popup'] = $params['fromFId'] . ':' . $params['contentFId'];
    $trackerparams['filterfield'] = $params['fromFId'] . ':' . $params['accountFId'];
    $trackerparams['filtervalue'] = $params['fromEmail'] . ':' . $params['accountName'];
    $trackerparams['stickypopup'] = 'n';
    $trackerparams['showlinks'] = 'y';
    $trackerparams['shownbitems'] = 'n';
    $trackerparams['showinitials'] = 'n';
    $trackerparams['showstatus'] = 'n';
    $trackerparams['showcreated'] = 'n';
    $trackerparams['showlastmodif'] = 'n';

    $data = wikiplugin_trackerlist('', $trackerparams);

    return $data;
}
