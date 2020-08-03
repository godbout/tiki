<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_addtogooglecal_info()
{
    return [
        'name' => tra('Add to Google Calendar'),
        'documentation' => 'PluginAddToGoogleCal',
        'description' => tra('Add a calendar item to a Google calendar.'),
        'prefs' => ['wikiplugin_addtogooglecal'],
        'introduced' => 6,
        'iconname' => 'calendar',
        'format' => 'html',
        'params' => [
            'calitemid' => [
                'required' => true,
                'name' => tra('Calendar item ID'),
                'description' => tra('The item ID of the calendar to add to Google calendar.'),
                'accepted' => tra('A calendar item ID number'),
                'filter' => 'digits',
                'default' => '',
                'since' => '6.0',
                'profile_reference' => 'calendar',
            ],
            'iconstyle' => [
                'required' => false,
                'name' => tra('Icon Style'),
                'description' => tra('Choose the icon style'),
                'accepted' => tra('Either 1, 2 or 3'),
                'filter' => 'digits',
                'default' => 1,
                'since' => '6.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('One'), 'value' => 1],
                    ['text' => tra('Two'), 'value' => 2],
                    ['text' => tra('Three'), 'value' => 3],
                ],
            ],
        ],
    ];
}

function wikiplugin_addtogooglecal($data, $params)
{
    $access = TikiLib::lib('access');
    $access->check_feature('feature_calendar');
    $calendarlib = TikiLib::lib('calendar');

    $cal_item_id = $params['calitemid'];
    $cal_id = $calendarlib->get_calendarid($cal_item_id);
    $calperms = Perms::get([ 'type' => 'calendar', 'object' => $cal_id ]);
    if (! $calperms->view_events) {
        return '';
    }
    $calitem = $calendarlib->get_item($cal_item_id);
    if (empty($calitem['start'])) {
        return '';
    }
    $gcal_action = 'TEMPLATE';
    $gcal_text = urlencode(str_replace(["\n", "\r"], ['', ''], strip_tags($calitem['parsedName'])));
    $gcal_details = urlencode(str_replace(["\n", "\r"], ['', ''], $calitem['parsed']));
    $gcal_location = urlencode(str_replace(["\n", "\r"], ['', ''], strip_tags($calitem['locationName'])));
    $curtikidate = new TikiDate();
    // Google requires date to be formatted in UTC
    $old_tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $date_from = date('Ymd', $calitem['start']) . 'T' . date('His', $calitem['start']) . 'Z';
    $date_to = date('Ymd', $calitem['end']) . 'T' . date('His', $calitem['end']) . 'Z';
    date_default_timezone_set($old_tz);
    $gcal_dates = $date_from . '/' . $date_to;
    if (isset($params['iconstyle']) && $params['iconstyle'] == 1) {
        $gcal_icon = 'https://www.google.com/calendar/images/ext/gc_button6.gif';
    } elseif (isset($params['iconstyle']) && $params['iconstyle'] == 2) {
        $gcal_icon = 'https://www.google.com/calendar/images/ext/gc_button2.gif';
    } elseif (isset($params['iconstyle']) && $params['iconstyle'] == 3) {
        $gcal_icon = 'https://www.google.com/calendar/images/ext/gc_button1.gif';
    } else {
        $gcal_icon = 'https://www.google.com/calendar/images/ext/gc_button6.gif';
    }

    return '<a target="_blank" href="https://www.google.com/calendar/event?action=' . $gcal_action . '&text=' . $gcal_text . '&dates=' . $gcal_dates . '&location=' . $gcal_location . '&details=' . $gcal_details . '"><img src="' . $gcal_icon . '"></a>';
}
