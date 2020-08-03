<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 * @return array
 */
function module_last_modif_events_info()
{
    return [
        'name' => tra('Last-Modified Events'),
        'description' => tra('Display the specified number of calendar events, starting from the most recently modified.'),
        'prefs' => ["feature_calendar"],
        'params' => [
            'calendarId' => [
                'name' => tra('Calendar Identifier'),
                'description' => tra('If set to a calendar identifier, restricts the events to those in the identified calendar.') . " " . tra('Example value: 13.') . " " . tra('Not set by default.'),
                'profile_reference' => 'calendar',
            ],
            'maxlen' => [
                'name' => tra('Maximum Length'),
                'description' => tra('Maximum number of characters in event names allowed before truncating.'),
                'filter' => 'int',
            ],
            'nodate' => [
                'name' => tra('Hide Date'),
                'description' => tra('If set to "y", it will hide date.'),
            ]
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_modif_events($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $calendarlib = TikiLib::lib('calendar');

    $events = $calendarlib->last_modif_events($mod_reference["rows"], isset($module_params["calendarId"]) ? $module_params["calendarId"] : 0);

    $smarty->assign('modLastEvents', $events);
    $smarty->assign('maxlen', isset($module_params["maxlen"]) ? $module_params["maxlen"] : 0);
    $smarty->assign('nodate', isset($module_params["nodate"]) ? $module_params["nodate"] : 'n');
}
