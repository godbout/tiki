<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_bigbluebutton_list()
{
    return [
        'bigbluebutton_feature' => [
            'name' => tra('BigBlueButton web conferencing'),
            'description' => tra('Integration with the BigBlueButton collaboration server for web conference and screen sharing.'),
            'type' => 'flag',
            'keywords' => 'big blue button web conferencing audio video chat screensharing whiteboard',
            'help' => 'BigBlueButton',
            'tags' => ['basic'],
            'default' => 'n',
            'extensions' => [
                'dom',
            ],
        ],
        'bigbluebutton_server_location' => [
            'name' => tra('BigBlueButton server location'),
            'description' => tra('Full URL to the BigBlueButton installation.'),
            'type' => 'text',
            'filter' => 'url',
            'hint' => tra('http://host.example.com/'),
            'keywords' => 'big blue button web conferencing audio video chat screensharing whiteboard',
            'size' => 40,
            'tags' => ['basic'],
            'default' => '',
        ],
        'bigbluebutton_server_salt' => [
            'name' => tra('BigBlueButton server salt'),
            'description' => tra('A salt key used to generate checksums for the BigBlueButton server to assure that requests are authentic.'),
            'keywords' => 'big blue button web conferencing audio video chat screensharing whiteboard',
            'type' => 'text',
            'size' => 40,
            'filter' => 'text',
            'tags' => ['basic'],
            'default' => '',
        ],
        'bigbluebutton_recording_max_duration' => [
            'name' => tr('BigBlueButton recording maximum duration'),
            'description' => tr('A maximum duration for the meetings must be submitted to BigBlueButton to prevent the recordings from being excessively long if a user leaves the conference window open.'),
            'units' => tra('minutes'),
            'keywords' => 'big blue button',
            'type' => 'text',
            'filter' => 'digits',
            'size' => 6,
            'default' => 5 * 60,
            'tags' => ['basic'],
        ],
        'bigbluebutton_dynamic_configuration' => [
            'name' => tr('BigBlueButton dynamic configuration'),
            'description' => tr('Uses the advanced options of BigBlueButton to configure the XML per room.'),
            'keywords' => 'big blue button',
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
        ],
    ];
}
