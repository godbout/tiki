<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'calendar';
require_once('tiki-setup.php');

$access->check_feature('feature_calendar');

$calendarlib = TikiLib::lib('calendar');
include_once('lib/newsletters/nllib.php');
include_once('lib/calendar/calrecurrence.php');
if ($prefs['feature_groupalert'] == 'y') {
    $groupalertlib = TikiLib::lib('groupalert');
}
$auto_query_args = ['calitemId', 'viewcalitemId'];

$daysnames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$daysnames_abr = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
$monthnames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$smarty->assign('daysnames', $daysnames);
$smarty->assign('daysnames_abr', $daysnames_abr);
$smarty->assign('monthnames', $monthnames);

$smarty->assign('edit', false);
$smarty->assign('recurrent', '');
$hour_minmax = '';
$recurrence = [
    'id' => '',
    'weekly' => '',
    'weekday' => '',
    'monthly' => '',
    'dayOfMonth' => '',
    'yearly' => '',
    'dateOfYear_day' => '',
    'dateOfYear_month' => '',
    'startPeriod' => '',
    'nbRecurrences' => '',
    'endPeriod' => ''
];
$smarty->assign('recurrence', $recurrence);

$caladd = [];
$rawcals = $calendarlib->list_calendars();
if ($rawcals['cant'] == 0 && $tiki_p_admin_calendar == 'y') {
    $smarty->assign('msg', tra('You need to <a href="tiki-admin_calendars.php?cookietab=2">create a calendar</a>'));
    $smarty->display("error.tpl");
    die;
}

$rawcals['data'] = Perms::filter([ 'type' => 'calendar' ], 'object', $rawcals['data'], [ 'object' => 'calendarId' ], 'view_calendar');

foreach ($rawcals["data"] as $cal_data) {
    $cal_id = $cal_data['calendarId'];
    $calperms = Perms::get([ 'type' => 'calendar', 'object' => $cal_id ]);
    if ($cal_data["personal"] == "y") {
        if ($user) {
            $cal_data["tiki_p_view_calendar"] = 'y';
            $cal_data["tiki_p_view_events"] = 'y';
            $cal_data["tiki_p_add_events"] = 'y';
            $cal_data["tiki_p_change_events"] = 'y';
        } else {
            $cal_data["tiki_p_view_calendar"] = 'n';
            $cal_data["tiki_p_view_events"] = 'y';
            $cal_data["tiki_p_add_events"] = 'n';
            $cal_data["tiki_p_change_events"] = 'n';
        }
    } else {
        $cal_data["tiki_p_view_calendar"] = $calperms->view_calendar ? "y" : "n";
        $cal_data["tiki_p_view_events"] = $calperms->view_events ? "y" : "n";
        $cal_data["tiki_p_add_events"] = $calperms->add_events ? "y" : "n";
        $cal_data["tiki_p_change_events"] = $calperms->change_events ? "y" : "n";
    }
    $caladd["$cal_id"] = $cal_data;
    if ($cal_data['tiki_p_add_events'] == 'y' && empty($calID)) {
        $calID = $cal_id;
    }
}
$smarty->assign('listcals', $caladd);
if (isset($_REQUEST['new'])) {
    $smarty->assign('saveas', true);
}
if (! isset($_REQUEST["calendarId"])) {
    if (isset($_REQUEST['calitemId'])) {
        $calID = $calendarlib->get_calendarid($_REQUEST['calitemId']);
    } elseif (isset($_REQUEST['viewcalitemId'])) {
        $calID = $calendarlib->get_calendarid($_REQUEST['viewcalitemId']);
    }
} elseif (isset($_REQUEST['calendarId'])) {
    $calID = $_REQUEST['calendarId'];
} elseif (isset($_REQUEST['save']) && isset($_REQUEST['save']['calendarId'])) {
    $calID = $_REQUEST['save']['calendarId'];
}

if ($prefs['feature_groupalert'] == 'y' && ! empty($calID)) {
    $groupforalert = $groupalertlib->GetGroup('calendar', $calID);
    $showeachuser = '';
    if ($groupforalert != '') {
        $showeachuser = $groupalertlib->GetShowEachUser('calendar', $calID, $groupforalert);
        $listusertoalert = $userlib->get_users(0, -1, 'login_asc', '', '', false, $groupforalert, '');
        $smarty->assign_by_ref('listusertoalert', $listusertoalert['data']);
    }
    $smarty->assign_by_ref('groupforalert', $groupforalert);
    $smarty->assign_by_ref('showeachuser', $showeachuser);
}

$tikilib->get_perm_object($calID, 'calendar');
$access->check_permission('tiki_p_view_calendar');

$calitemId = ! empty($_REQUEST['save']['calitemId']) ? $_REQUEST['save']['calitemId'] : (! empty($_REQUEST['calitemId']) ? $_REQUEST['calitemId'] : (! empty($_REQUEST['viewcalitemId']) ? $_REQUEST['viewcalitemId'] : 0));
if (! empty($calitemId) && ! empty($user)) {
    $calitem = $calendarlib->get_item($calitemId);
    if ($calitem['user'] == $user) {
        $smarty->assign('tiki_p_change_events', 'y');
        $tiki_p_change_events = 'y';
        if (! empty($_REQUEST['save']['calendarId'])) {
            $caladd[$_REQUEST['save']['calendarId']]['tiki_p_change_events'] = $caladd[$_REQUEST['save']['calendarId']]['tiki_p_add_events'];
        }
        $caladd[$calitem['calendarId']]['tiki_p_change_events'] = 'y';
    }
}

if (isset($_REQUEST['save']) && ! isset($_REQUEST['preview']) && ! isset($_REQUEST['act'])) {
    $_REQUEST['changeCal'] = true;
}

$displayTimezone = TikiLib::lib('tiki')->get_display_timezone();
if (isset($_REQUEST['act']) || isset($_REQUEST['preview']) || isset($_REQUEST['changeCal'])) {
    $save = $_POST['save'];
    $save['allday'] = empty($_POST['allday']) ? 0 : 1;

    if (! isset($save['date_start']) && ! isset($save['date_end'])) {
        $save['date_start'] = strtotime($_POST['start_date_Year'] . '-' . $_POST['start_date_Month'] . '-' . $_POST['start_date_Day'] .
            ' ' . $_POST['start_Hour'] . ':' . $_POST['start_Minute'] . ':00');
        $save['date_end'] = strtotime($_POST['end_date_Year'] . '-' . $_POST['end_date_Month'] . '-' . $_POST['end_date_Day'] .
            ' ' . $_POST['end_Hour'] . ':' . $_POST['end_Minute'] . ':00');
        echo date('Y-m-d H:i', $save['end']);
    }

    if (! empty($save['description'])) {
        $save['description'] = $tikilib->convertAbsoluteLinksToRelative($save['description']);
    }

    // Take care of timestamps dates coming from jscalendar
    if (isset($save['date_start']) || isset($save['date_end'])) {
        if (isset($_REQUEST['tzoffset'])) {
            $browser_offset = 0 - (int)$_REQUEST['tzoffset'] * 60;
            $server_offset = TikiDate::tzServerOffset($displayTimezone);
            $save['date_start'] = $save['date_start'] - $server_offset + $browser_offset;
            $save['date_end'] = $save['date_end'] - $server_offset + $browser_offset;
            if (! empty($_POST['startPeriod'])) {
                // get timezone date at 12:00am - reason: when this is later displayed, it could be the wrong date if stored at UTC
                // real solution here is to save the start date as a date object, not a timestamp to avoid timezone conversion issues...
                $_POST['startPeriod'] = TikiDate::getStartDay($_POST['startPeriod'] - $server_offset + $browser_offset, $displayTimezone);
            }
            if (! empty($_POST['endPeriod'])) {
                // get timezone date at 12:00am
                $_POST['endPeriod'] = TikiDate::getStartDay($_POST['endPeriod'] - $server_offset + $browser_offset, $displayTimezone);
            }
        }
    }

    $save['start'] = $save['date_start'];

    if ($save['end_or_duration'] == 'duration') {
        $save['duration'] = max(0, $_REQUEST['duration_Hour'] * 60 * 60 + $_REQUEST['duration_Minute'] * 60);
        $save['end'] = $save['start'] + $save['duration'];
    } else {
        $save['end'] = $save['date_end'];
        $save['duration'] = max(0, $save['end'] - $save['start']);
    }

    if (! empty($save['participant_roles'])) {
        $participants = [];
        foreach ($save['participant_roles'] as $username => $role) {
            $participants[] = [
                'username' => $username,
                'role' => $role,
                'partstat' => $save['participant_partstat'][$username] ?? null
            ];
        }
        $save['participants'] = $participants;
    } else {
        $save['participants'] = [];
    }
}

$impossibleDates = false;
if (isset($save['start']) && isset($save['end'])) {
    if (($save['end'] - $save['start']) < 0) {
        $impossibleDates = true;
    }
}

if (isset($_POST['act'])) {
    // Check antibot code if anonymous and allowed
    if (empty($user) && $prefs['feature_antibot'] == 'y' && (! $captchalib->validate())) {
        $smarty->assign('msg', $captchalib->getErrors());
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }
    if (empty($save['user'])) {
        $save['user'] = $user;
    }
    $newcalid = $save['calendarId'];
    if ((empty($save['calitemId']) and $caladd["$newcalid"]['tiki_p_add_events'] == 'y') ||
            (! empty($save['calitemId']) and $caladd["$newcalid"]['tiki_p_change_events'] == 'y')) {
        if (empty($save['name'])) {
            $save['name'] = tra("event without name");
        }
        if (empty($save['priority'])) {
            $save['priority'] = 1;
        }
        if (! isset($save['status'])) {
            if (empty($calendar['defaulteventstatus'])) {
                $save['status'] = 1; // Confirmed
            } else {
                $save['status'] = $calendar['defaulteventstatus'];
            }
        }
        if (empty($save['trackerItemId'])) {
            $redirectUrl = 'tiki-calendar.php?todate=' . $save['start'];
        } else {
            $smarty->loadPlugin('smarty_modifier_sefurl');
            $redirectUrl = smarty_modifier_sefurl($save['trackerItemId'], 'trackeritem');
        }

        if (array_key_exists('recurrent', $_POST) && ($_POST['recurrent'] == 1) && $_POST['affect'] != 'event') {
            if ($save['end'] < $save['start']) {
                $impossibleDates = true;
            } elseif ($save['start'] + (24 * 60 * 60) < $save['end']) {	// more than a day?
                $impossibleDates = true;
            } else {
                $impossibleDates = false;
            }
            if (! $impossibleDates) {
                $calRecurrence = new CalRecurrence(! empty($_POST['recurrenceId']) ? $_POST['recurrenceId'] : -1);
                $calRecurrence->setCalendarId($save['calendarId']);
                $calRecurrence->setStart(strftime('%H%M', $save['start']));
                $calRecurrence->setEnd(strftime('%H%M', $save['end']));
                $calRecurrence->setAllday($save['allday']);
                $calRecurrence->setLocationId($save['locationId']);
                $calRecurrence->setCategoryId($save['categoryId']);
                $calRecurrence->setNlId(0); //TODO : What id nlId ?
                $calRecurrence->setPriority($save['priority']);
                $calRecurrence->setStatus($save['status']);
                $calRecurrence->setUrl($save['url']);
                $calRecurrence->setLang(strLen($save['lang']) > 0 ? $save['lang'] : 'en');
                $calRecurrence->setName($save['name']);
                $calRecurrence->setDescription($save['description']);
                switch ($_POST['recurrenceType']) {
                    case "weekly":
                        $calRecurrence->setWeekly(true);
                        $calRecurrence->setWeekday($_POST['weekday']);
                        $calRecurrence->setMonthly(false);
                        $calRecurrence->setYearly(false);

                        break;
                    case "monthly":
                        $calRecurrence->setWeekly(false);
                        $calRecurrence->setMonthly(true);
                        $calRecurrence->setDayOfMonth($_POST['dayOfMonth']);
                        $calRecurrence->setYearly(false);

                        break;
                    case "yearly":
                        $calRecurrence->setWeekly(false);
                        $calRecurrence->setMonthly(false);
                        $calRecurrence->setYearly(true);
                        $calRecurrence->setDateOfYear(str_pad($_POST['dateOfYear_month'], 2, '0', STR_PAD_LEFT) . str_pad($_POST['dateOfYear_day'], 2, '0', STR_PAD_LEFT));

                        break;
                }
                if ($calRecurrence->getId() > 0 && $save['calitemId'] == $calRecurrence->getFirstItemId()) {
                    // modify start period when the first event is updated
                    $calRecurrence->setStartPeriod(TikiDate::getStartDay($save['start'], $displayTimezone));
                } else {
                    $calRecurrence->setStartPeriod($_POST['startPeriod']);
                }
                if ($_POST['endType'] == "dt") {
                    $calRecurrence->setEndPeriod($_POST['endPeriod']);
                } else {
                    $calRecurrence->setNbRecurrences(empty($_POST['nbRecurrences']) ? null : $_POST['nbRecurrences']);
                }
                $calRecurrence->setUser($save['user']);
                $calRecurrence->save(! empty($_POST['affect']) && $_POST['affect'] === 'all');
                // Save the ip at the log for the addition of new calendar items
                if ($prefs['feature_actionlog'] == 'y' && empty($save['calitemId']) && $caladd["$newcalid"]['tiki_p_add_events']) {
                    $logslib->add_action('Created', 'recurrent event starting on ' . $_POST['startPeriod'] . ' in calendar ' . $save['calendarId'], 'calendar event');
                }
                if ($prefs['feature_actionlog'] == 'y' && ! empty($save['calitemId']) and $caladd["$newcalid"]['tiki_p_change_events']) {
                    $logslib->add_action('Updated', 'recurrent event starting on ' . $_POST['startPeriod'] . ' in calendar ' . $save['calendarId'], 'calendar event');
                }
                $access->redirect($redirectUrl);
                die;
            }
        } else {
            if (! $impossibleDates) {
                if (array_key_exists('recurrenceId', $_POST)) {
                    $save['recurrenceId'] = $_POST['recurrenceId'];
                    $save['changed'] = 1;
                }
                //save event as new
                if (isset($_POST['saveas'])) {
                    $save['calitemId'] = 0;
                }
                $calitemId = $calendarlib->set_item($user, $save['calitemId'], $save);
                // Save the ip at the log for the addition of new calendar items
                if ($prefs['feature_actionlog'] == 'y' && empty($save['calitemId']) && $caladd["$newcalid"]['tiki_p_add_events']) {
                    $logslib->add_action('Created', 'event ' . $calitemId . ' in calendar ' . $save['calendarId'], 'calendar event');
                }
                if ($prefs['feature_actionlog'] == 'y' && ! empty($save['calitemId']) and $caladd["$newcalid"]['tiki_p_change_events']) {
                    $logslib->add_action('Updated', 'event ' . $calitemId . ' in calendar ' . $save['calendarId'], 'calendar event');
                }
                if ($prefs['feature_groupalert'] == 'y') {
                    $groupalertlib->Notify($_REQUEST['listtoalert'], "tiki-calendar_edit_item.php?viewcalitemId=" . $calitemId);
                }
                $access->redirect($redirectUrl);
                die;
            }
        }
    }
}

if (! empty($_REQUEST['viewcalitemId']) && isset($_REQUEST['del_me']) && $tiki_p_calendar_add_my_particip == 'y') {
    $calendarlib->update_participants($_REQUEST['viewcalitemId'], null, [$user]);
}

if (! empty($_REQUEST['viewcalitemId']) && isset($_REQUEST['add_me']) && $tiki_p_calendar_add_my_particip == 'y') {
    $calendarlib->update_participants($_REQUEST['viewcalitemId'], [['name' => $user]], null);
}

if (! empty($_REQUEST['viewcalitemId']) && ! empty($_REQUEST['guests']) && isset($_REQUEST['add_guest']) && $tiki_p_calendar_add_guest_particip == 'y') {
    $guests = preg_split('/ *, */', $_REQUEST['guests']);
    foreach ($guests as $i => $guest) {
        $guests[$i] = ['name' => $guest];
    }
    $calendarlib->update_participants($_REQUEST['viewcalitemId'], $guests);
}

if (isset($_REQUEST["delete"]) and ($_REQUEST["delete"]) and isset($_REQUEST["calitemId"]) and $tiki_p_change_events == 'y') {
    // There is no check for valid antibot code if anonymous allowed to delete events since this comes from a JS button at the tpl and bots are not know to use JS
    $access->check_authenticity();
    $calitem = $calendarlib->get_item($_REQUEST['calitemId']);
    $calendarlib->drop_item($user, $_REQUEST["calitemId"]);
    if ($prefs['feature_actionlog'] == 'y') {
        $logslib->add_action('Removed', 'event ' . $_REQUEST['calitemId'], 'calendar event');
    }
    $_REQUEST["calitemId"] = 0;
    header('Location: tiki-calendar.php?todate=' . $calitem['start']);
    exit;
} elseif (isset($_REQUEST["delete"]) and ($_REQUEST["delete"]) and isset($_REQUEST["recurrenceId"]) and $tiki_p_change_events == 'y') {
    // There is no check for valid antibot code if anonymous allowed to delete events since this comes from a JS button at the tpl and bots are not know to use JS
    $access->check_authenticity();
    $calRec = new CalRecurrence($_REQUEST['recurrenceId']);
    $calRec->delete();
    if ($prefs['feature_actionlog'] == 'y') {
        $logslib->add_action('Removed', 'recurrent event (recurrenceId = ' . $_REQUEST["recurrenceId"] . ')', 'calendar event');
    }
    $_REQUEST["recurrenceTypeId"] = 0;
    $_REQUEST["calitemId"] = 0;
    header('Location: tiki-calendar.php');
    die;
} elseif (isset($_REQUEST['drop']) and $tiki_p_change_events == 'y') {
    check_ticket('calendar');
    if (is_array($_REQUEST['drop'])) {
        foreach ($_REQUEST['drop'] as $dropme) {
            $calendarlib->drop_item($user, $dropme);
        }
    } else {
        $calendarlib->drop_item($user, $_REQUEST['drop']);
    }
    if ($prefs['feature_actionlog'] == 'y') {
        $logslib->add_action('Removed (dropped)', 'event/s ' . $_REQUEST['calitemId'], 'calendar event');
    }
    header('Location: tiki-calendar.php');
    die;
} elseif (isset($_REQUEST['duplicate']) and $tiki_p_add_events == 'y') {
    // Check antibot code if anonymous and allowed
    if (empty($user) && $prefs['feature_antibot'] == 'y' && (! $captchalib->validate())) {
        $smarty->assign('msg', $captchalib->getErrors());
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }
    $calitem = $calendarlib->get_item($_REQUEST['calitemId']);
    $calitem['calendarId'] = $calID;
    $calitem['calitemId'] = 0;
    $calendarlib->set_item($user, 0, $calitem);
    $id = 0;
    if (isset($_REQUEST['calId'])) {
        $calendar = $calendarlib->get_calendar($_REQUEST['calId']);
    } else {
        $calendar = $calendarlib->get_calendar($calitem['calendarId']);
    }
    $smarty->assign('edit', true);
    $hour_minmax = abs(ceil(($calendar['startday'] - 1) / (60 * 60))) . '-' . ceil(($calendar['endday']) / (60 * 60));
} elseif (isset($_REQUEST['preview']) || $impossibleDates) {
    $save['parsed'] = TikiLib::lib('parser')->parse_data($save['description'], ['is_html' => $prefs['calendar_description_is_html'] === 'y']);
    $save['parsedName'] = TikiLib::lib('parser')->parse_data($save['name']);
    $id = isset($save['calitemId']) ? $save['calitemId'] : '';
    $save['recurrenceId'] = isset($_POST['recurrenceId']) ? $_POST['recurrenceId'] : '';
    $calitem = $save;
    $calitem["selected_participants"] = array_map(function ($role) {
        return $role['username'];
    }, $calitem['participants']);

    $recurrence = [
        'id' => $calitem['recurrenceId'],
        'weekly' => isset($_POST['recurrenceType']) && $_POST['recurrenceType'] == 'weekly',
        'weekday' => isset($_POST['weekday']) ? $_POST['weekday'] : '',
        'monthly' => isset($_POST['recurrenceType']) && $_POST['recurrenceType'] == 'monthly',
        'dayOfMonth' => isset($_POST['dayOfMonth']) ? $_POST['dayOfMonth'] : '',
        'yearly' => isset($_POST['recurrenceType']) && $_POST['recurrenceType'] == 'yearly',
        'dateOfYear_day' => isset($_POST['dateOfYear_day']) ? $_POST['dateOfYear_day'] : '',
        'dateOfYear_month' => isset($_POST['dateOfYear_month']) ? $_POST['dateOfYear_month'] : '',
        'startPeriod' => isset($_POST['startPeriod']) ? $_POST['startPeriod'] : '',
        'nbRecurrences' => isset($_POST['nbRecurrences']) ? $_POST['nbRecurrences'] : '',
        'endPeriod' => isset($_POST['endPeriod']) ? $_POST['endPeriod'] : ''
    ];
    if (isset($_POST['recurrent']) && $_POST['recurrent'] == 1) {
        $smarty->assign('recurrent', $_POST['recurrent']);
    }
    $smarty->assign_by_ref('recurrence', $recurrence);

    $calendar = $calendarlib->get_calendar($calitem['calendarId']);
    $smarty->assign('edit', true);
} elseif (isset($_REQUEST['changeCal'])) {
    $calitem = $save;
    $calendar = $calendarlib->get_calendar($calitem['calendarId']);
    if (empty($save['calitemId'])) {
        $calitem['allday'] = $calendar['allday'] == 'y' ? 1 : 0;
    }
    $smarty->assign('edit', true);
    $id = isset($save['calitemId']) ? $save['calitemId'] : 0;
    $hour_minmax = ceil(($calendar['startday']) / (60 * 60)) . '-' . ceil(($calendar['endday']) / (60 * 60));
    $smarty->assign('changeCal', isset($_REQUEST['changeCal']));
} elseif (isset($_REQUEST['viewcalitemId']) and $tiki_p_view_events == 'y') {
    $calitem = $calendarlib->get_item($_REQUEST['viewcalitemId']);
    $id = $_REQUEST['viewcalitemId'];
    $calendar = $calendarlib->get_calendar($calitem['calendarId']);
    $hour_minmax = ceil(($calendar['startday']) / (60 * 60)) . '-' . ceil(($calendar['endday']) / (60 * 60));
} elseif (isset($_REQUEST['calitemId']) and ($tiki_p_change_events == 'y' or $tiki_p_view_events == 'y')) {
    $calitem = $calendarlib->get_item($_REQUEST['calitemId']);

    if ($prefs['feature_jscalendar'] === 'y' && $prefs['users_prefs_display_timezone'] === 'Site') {
        // using site timezone always so alter the stored utc date by the server offset for the datetimepicker
        $tzServerOffset = TikiDate::tzServerOffset($displayTimezone);
        $calitem['start'] += $tzServerOffset;
        $calitem['end'] += $tzServerOffset;
    }
    $id = $_REQUEST['calitemId'];
    $calendar = $calendarlib->get_calendar($calitem['calendarId']);
    $smarty->assign('edit', true);
    $hour_minmax = ceil(($calendar['startday']) / (60 * 60)) . '-' . ceil(($calendar['endday']) / (60 * 60));
//Add event buttons - either button on top of page or one of the buttons on a specific day
} elseif (isset($calID) and $tiki_p_add_events == 'y') {
    $calendar = $calendarlib->get_calendar($calID);
    if (isset($_REQUEST['todate'])) {
        $now = $_REQUEST['todate'];
        if (isset($_REQUEST['tzoffset'])) {
            $browser_offset = 0 - (int)$_REQUEST['tzoffset'] * 60;
            $server_offset = TikiDate::tzServerOffset($displayTimezone);
            $now = $now - $server_offset + $browser_offset;
        }
    } else {
        $now = $tikilib->now;
    }
    if (! empty($_REQUEST['tzoffset'])) {
        $browser_offset = 0 - (int)$_REQUEST['tzoffset'] * 60;
        $now = $now + $browser_offset;
    }
    //if current time of day is within the calendar day (between startday and endday), then use now as start, otherwise use beginning of calendar day
    $day_start = $tikilib->make_time(
        abs(ceil($calendar['startday'] / (60 * 60))),
        0,
        0,
        TikiLib::date_format('%m', $now),
        TikiLib::date_format('%d', $now),
        TikiLib::date_format('%Y', $now)
    );
    $day_end = $tikilib->make_time(
        abs(ceil($calendar['endday'] / (60 * 60))),
        0,
        0,
        TikiLib::date_format('%m', $now),
        TikiLib::date_format('%d', $now),
        TikiLib::date_format('%Y', $now)
    );
    if ($day_start < $now && ($now + (60 * 60)) < $day_end) {
        $start = $now;
    } elseif ($day_start < $now && isset($_REQUEST['todate'])) {
        // if $now ($_REQUEST['todate']) is before the day start then make the start hour of the event "now"
        // as it will have been a whole day that was clicked on
        $start = $tikilib->make_time(
            TikiLib::date_format('%H', $tikilib->now),
            0,
            0,
            TikiLib::date_format('%m', $now),
            TikiLib::date_format('%d', $now),
            TikiLib::date_format('%Y', $now)
        );
    } else {
        $start = $day_start;
    }
    if ($prefs['users_prefs_display_timezone'] === 'Site') {
        $server_offset = TikiDate::tzServerOffset($displayTimezone);
        $start = $start + $server_offset;
    }
    $end = $start + (60 * 60);	// default to 1 hour long

    //if $now_end is midnight, make it one second before
    if (TikiLib::date_format('%H%M%s', $end) == '000000') {
        $end -= 1;
    }

    $calitem = [
        'calitemId' => 0,
        'user' => $user,
        'name' => '',
        'url' => '',
        'description' => '',
        'status' => $calendar['defaulteventstatus'],
        'priority' => 0,
        'locationId' => 0,
        'categoryId' => 0,
        'nlId' => 0,
        'start' => $start,
        'end' => $end,
        'duration' => (60 * 60),
        'recurrenceId' => 0,
        'allday' => $calendar['allday'] == 'y' ? 1 : 0,
        'organizers' => [$user],
        'participants' => [[
            'username' => $user,
            'role' => '',
            'partstat' => ''
        ]],
        'selected_participants' => [$user]
        ];
    $hour_minmax = abs(ceil(($calendar['startday'] - 1) / (60 * 60))) . '-' . ceil(($calendar['endday']) / (60 * 60));
    $id = 0;
    $smarty->assign('edit', true);
} else {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have permission to view this page"));
    $smarty->display("error.tpl");
    die;
}
if (! empty($id) && $calendar['personal'] == 'y' && $calitem['user'] != $user) {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have permission to view this page"));
    $smarty->display("error.tpl");
    die;
}

if (! empty($calendar['eventstatus'])) {
    $calitem['status'] = $calendar['eventstatus'];
}

if ($calendar['customlocations'] == 'y') {
    $listlocs = $calendarlib->list_locations($calID);
} else {
    $listlocs = [];
}
$smarty->assign('listlocs', $listlocs);
$smarty->assign('changeCal', isset($_REQUEST['changeCal']));

$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

if ($calendar['customcategories'] == 'y') {
    $listcats = $calendarlib->list_categories($calID);
} else {
    $listcats = [];
}
$smarty->assign('listcats', $listcats);

if ($calendar["customsubscription"] == 'y') {
    $subscrips = $nllib->list_avail_newsletters();
} else {
    $subscrips = [];
}
$smarty->assign('subscrips', $subscrips);

if ($calendar["customlanguages"] == 'y') {
    $langLib = TikiLib::lib('language');
    $languages = $langLib->list_languages();
} else {
    $languages = [];
}
$smarty->assign('listlanguages', $languages);

$smarty->assign('listpriorities', ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
$smarty->assign('listprioritycolors', ['fff', 'fdd', 'fcc', 'fbb', 'faa', 'f99', 'e88', 'd77', 'c66', 'b66', 'a66']);
$smarty->assign('listroles', ['0' => '', '1' => tra('required'), '2' => tra('optional'), '3' => tra('non-participant')]);


if ($prefs['feature_theme_control'] == 'y') {
    $cat_type = "calendar";
    $cat_objid = $calID;
    include('tiki-tc.php');
}

$headerlib->add_cssfile('themes/base_files/feature_css/calendar.css', 20);

$smarty->assign('referer', empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'tiki-calendar_edit_item.php') !== false ? 'tiki-calendar.php' : $_SERVER['HTTP_REFERER']);
$smarty->assign('myurl', 'tiki-calendar_edit_item.php');
$smarty->assign('id', $id);
$smarty->assign('hour_minmax', $hour_minmax);
if (isset($calitem['recurrenceId']) && $calitem['recurrenceId'] > 0) {
    $cr = new CalRecurrence($calitem['recurrenceId']);
    $smarty->assign('recurrence', $cr->toArray());
    $recurranceNumChangedEvents = TikiDb::get()->table('tiki_calendar_items')->fetchCount([
        'recurrenceId' => $calitem['recurrenceId'],
        'changed' => 1,
    ]);
    $smarty->assign('recurranceNumChangedEvents', (int) $recurranceNumChangedEvents);
}
$headerlib->add_jsfile('lib/jquery_tiki/calendar_edit_item.js');

$smarty->assign('calitem', $calitem);
$smarty->assign('calendar', $calendar);
$smarty->assign('calendarId', $calID);
$smarty->assign('preview', isset($_REQUEST['preview']));
if ($calitem['allday']) {
    $smarty->assign('hidden_if_all_day', ' style="display:none;"');
} else {
    $smarty->assign('hidden_if_all_day', '');
}

if (array_key_exists('CalendarViewGroups', $_SESSION) && count($_SESSION['CalendarViewGroups']) == 1) {
    $smarty->assign('calendarView', $_SESSION['CalendarViewGroups'][0]);
}

$wikilib = TikiLib::lib('wiki');
$plugins = $wikilib->list_plugins(true, 'editwiki');
$smarty->assign_by_ref('plugins', $plugins);
$smarty->assign('impossibleDates', $impossibleDates);
if (! empty($_REQUEST['fullcalendar'])) {
    $smarty->display('calendar.tpl');
} else {
    $smarty->assign('mid', 'tiki-calendar_edit_item.tpl');
    $smarty->display('tiki.tpl');
}
