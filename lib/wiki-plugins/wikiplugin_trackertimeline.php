<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_trackertimeline_info()
{
    return [
        'name' => tra('Tracker Timeline'),
        'documentation' => 'PluginTrackerTimeline',
        'description' => tra('Show a timeline view of a tracker'),
        'prefs' => [ 'wikiplugin_trackertimeline', 'feature_trackers' ],
        'iconname' => 'history',
        'introduced' => 3,
        'format' => 'html',
        'params' => [
            'tracker' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'title' => [
                'required' => true,
                'name' => tra('Title Field'),
                'description' => tra('Tracker Field ID containing the item title.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'summary' => [
                'required' => true,
                'name' => tra('Summary Field'),
                'description' => tra('Tracker Field ID containing the summary of the item. The summary will be displayed
					on the timeline when the item is focused.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'start' => [
                'required' => true,
                'name' => tra('Start Date'),
                'description' => tra('Tracker Field ID containing the element start date. The field must be a
					datetime/jscalendar field.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'end' => [
                'required' => true,
                'name' => tra('End Date'),
                'description' => tra('Tracker Field ID containing the element end date. The field must be a
					datetime/jscalendar field.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'group' => [
                'required' => true,
                'name' => tra('Element Group'),
                'description' => tra('Tracker Field ID containing the element\'s group. Elements of a same group are
					displayed on the same row.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'lower' => [
                'required' => true,
                'name' => tra('Lower Bound'),
                'description' => tr('Date from which element should be displayed. Date must be provided in
					%0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'datetime',
                'default' => '',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'upper' => [
                'required' => true,
                'name' => tra('Upper Bound'),
                'description' => tr('Date until which element should be displayed. Date must be provided in
					%0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'datetime',
                'default' => '',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'scale1' => [
                'required' => false,
                'name' => tra('Primary Scale Unit'),
                'description' => tra('Unit of time to use for the primary scale (default to hour - * SIMILE only)'),
                'since' => '3.0',
                'filter' => 'alpha',
                'default' => 'hour',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Hour'), 'value' => 'hour'],
                    ['text' => tra('Day'), 'value' => 'day'],
                    ['text' => tra('Week'), 'value' => 'week'],
                    ['text' => tra('Month'), 'value' => 'month'],
                    ['text' => tra('Year'), 'value' => 'year'],
                    ['text' => tra('Decade *'), 'value' => 'decade'],
                    ['text' => tra('Century *'), 'value' => 'century'],
                ]
            ],
            'scale2' => [
                'required' => false,
                'name' => tra('Secondary Scale Unit'),
                'description' => tra('Unit of time to use for the secondary scale (default to empty - * SIMILE only)'),
                'since' => '3.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Hour'), 'value' => 'hour'],
                    ['text' => tra('Day'), 'value' => 'day'],
                    ['text' => tra('Week'), 'value' => 'week'],
                    ['text' => tra('Month'), 'value' => 'month'],
                    ['text' => tra('Year'), 'value' => 'year'],
                    ['text' => tra('Decade *'), 'value' => 'decade'],
                    ['text' => tra('Century *'), 'value' => 'century'],
                ]
            ],
            'height' => [
                'required' => false,
                'name' => tra('Timeline height'),
                'description' => tr(
                    'Height of the timeline band as a CSS unit (default: %0 -  - * SIMILE only)',
                    '<code>250p</code>'
                ),
                'since' => '9.0',
                'filter' => 'text',
                'default' => '250px',
            ],
            'band2_height' => [
                'required' => false,
                'name' => tra('Lower band height'),
                'description' => tr(
                    'Height of the lower timeline band as a percentage (default: %0 -  - * SIMILE only)',
                    '<code>250p</code>'
                ),
                'since' => '9.0',
                'filter' => 'int',
                'default' => '30',
            ],
            'link_group' => [
                'required' => false,
                'name' => tra('Link Group Name'),
                'description' => tra('Convert the group name to a link'),
                'since' => '3.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'link_page' => [
                'required' => false,
                'name' => tra('Page Link Field'),
                'description' => tra('Tracker Field ID containing the page name for item details.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'simile_timeline' => [
                'required' => false,
                'name' => tra('SIMILE Timeline'),
                'description' => tra('Use the SIMILE Timeline Widget.'),
                'since' => '7.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'image_field' => [
                'required' => false,
                'name' => tra('Image Field'),
                'description' => tra('Tracker Field ID containing in image.'),
                'since' => '7.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
        ]
    ];
}

function wikiplugin_trackertimeline($data, $params)
{
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');

    static $instance = 0;
    $instance++;

    if (! isset($params['tracker'])) {
        return "^" . tr("Missing parameter: %0", 'tracker') . "^";
    }

    $default = ['scale1' => 'hour', 'simile_timeline' => 'n', 'height' => '250px', 'band2_height' => 30];
    $params = array_merge($default, $params);
    $formats = ['hour' => 'H:i', 'day' => 'jS', 'week' => 'jS', 'month' => 'm', 'year' => 'y'];

    $start = strtotime($params['lower']);
    $end = strtotime($params['upper']);
    $size = $end - $start;

    if ($size <= 0) {
        return "^" . tr("Start date after end date.") . "^";
    }

    $fieldIds = [
        $params['title'] => 'title',
        $params['summary'] => 'summary',
        $params['start'] => 'start',
        $params['end'] => 'end',
        $params['group'] => 'group',
    ];

    if (isset($params['link_page'])) {
        $fieldIds[ $params['link_page'] ] = 'link';
    }

    if (! empty($params['image_field'])) {
        $fieldIds[ $params['image_field'] ] = 'image';
    }

    $fields = [];
    foreach ($fieldIds as $id => $label) {
        $fields[$id] = $trklib->get_tracker_field($id);
    }

    $items = $trklib->list_items($params['tracker'], 0, -1, '', $fields);

    $data = [];
    foreach ($items['data'] as $item) {
        // Collect data
        $detail = [ 'item' => $item['itemId'] ];
        foreach ($item['field_values'] as $field) {
            $detail[ $fieldIds[$field['fieldId']] ] = $field['value'];
        }

        // Filter elements
        if ($params['simile_timeline'] !== 'y') {
            if ($detail['start'] >= $detail['end']) {
                continue;
            }
            if ($detail['end'] <= $start || $detail['start'] > $end) {
                continue;
            }
        } else {
            if (! empty($detail['end']) && $detail['start'] > $detail['end']) {
                continue;
            }
            if ((! empty($detail['end']) && $detail['end'] < $start) || $detail['start'] > $end) {
                continue;
            }
        }

        $detail['lstart'] = max($start, $detail['start']);
        $detail['lend'] = min($end, $detail['end']);
        $detail['lsize'] = round(($detail['lend'] - $detail['lstart']) / $size * 80);

        $detail['fstart'] = date($formats[$params['scale1']], $detail['start']);
        $detail['fend'] = date($formats[$params['scale1']], $detail['end']);
        $detail['psummary'] = TikiLib::lib('parser')->parse_data($detail['summary']);

        $detail['encoded'] = json_encode($detail);

        // Add to data list
        if (! array_key_exists($detail['group'], $data)) {
            $data[$detail['group']] = [];
        }
        $data[ $detail['group'] ][] = $detail;
    }

    if ($params['simile_timeline'] !== 'y') {
        $new = [];
        foreach ($data as $group => &$list) {
            wp_ttl_organize($group, $start, $size, $list, $new);
        }
        $data = array_merge($data, $new);
        ksort($data);

        $smarty->assign('wp_ttl_data', $data);
        $layouts = [];
        if (isset($params['scale2']) && $layout = wp_ttl_genlayout($start, $end, $size, $params['scale2'])) {
            $layouts[] = $layout;
        }
        $layouts[] = wp_ttl_genlayout($start, $end, $size, isset($params['scale1']) ? $params['scale1'] : 'hour');
        $smarty->assign('layouts', $layouts);
        $smarty->assign('link_group_names', isset($params['link_group']) && $params['link_group'] == 'y');

        return $smarty->fetch('wiki-plugins/wikiplugin_trackertimeline.tpl');
    }  	// SIMILE Timeline Widget setup
    $headerlib = TikiLib::lib('header');
    $smarty->loadPlugin('smarty_modifier_sefurl');

    // static js moved to lib
    $headerlib->add_jsfile('lib/simile_tiki/tiki-timeline.js');

    // prepare the data for SIMILE widget - to be included in the page for now (ajax feed to come)
    $ttl_data = [];
    $events = [];
    foreach ($data as $group => $list) {	// ignoring group for now
        foreach ($list as $item) {
            $event = [
                    'title' => $item['title'],
                    'start' => date('r', $item['start']),
                    'description' => $item['summary'],
                ];
            if (! empty($item['end'])) {
                $event['end'] = date('r', $item['end']);
                $event['isDuration'] = true;
            }
            if (! empty($item['link'])) {
                $event['link'] = $item['link'];
            }
            $image = $item['image'];
            if (! empty($image)) {
                if (strpos($image, ',') !== false) {
                    // just the first one
                    $image = substr($image, 0, strpos($image, ','));
                }
                if (is_numeric($image)) {
                    // a fileId
                    $image = smarty_modifier_sefurl($image, 'thumbnail');
                }
                $event['image'] = $image;
            }
            $events[] = $event;
        }
        $ttl_data = [
                'dateTimeFormat' => '',	// iso8601
//				'wikiURL' => '',
//				'wikiSection' => '',
                'events' => $events,
            ];
    }
    $js = 'var ttl_eventData_' . $instance . ' = ' . json_encode($ttl_data) . ";\n";

    $js .= '
setTimeout( function(){ ttlInit("ttl_timeline_' . $instance . '",ttl_eventData_' . $instance . ',"' . $params['scale1'] . '","' . $params['scale2'] . '","' . $params['band2_height'] . '"); }, 1000);
';

    $headerlib->add_jq_onready($js, 10);
    $out = '<div id="ttl_timeline_' . $instance . '" style="height: ' . $params['height'] . '; border: 1px solid #aaa"></div>';

    return $out;
}

function wp_ttl_organize($name, $base, $size, &$list, &$new)
{
    usort($list, 'wp_ttl_sort_cb');

    $first = $list;
    $list = [];
    $remaining = [];

    $pos = $base;
    foreach ($first as $item) {
        if ($item['lstart'] < $pos) {
            $remaining[] = $item;

            continue;
        }

        $item['lpad'] = round(($item['lstart'] - $pos) / $size * 80);
        $pos = $item['lend'];

        $list[] = $item;
    }

    if (count($remaining)) {
        wp_ttl_organize("$name ", $base, $size, $remaining, $new);
        $new["$name "] = $remaining;
    }
}

function wp_ttl_sort_cb($a, $b)
{
    if ($a['start'] == $b['start']) {
        return 0;
    }
    if ($a['start'] < $b['start']) {
        return -1;
    }
    if ($a['start'] > $b['start']) {
        return 1;
    }
}

function wp_ttl_genlayout($start, $end, $full, $type)
{
    switch ($type) {
        case 'empty':
        case '':
            return;
        case 'hour':
            $size = 3600;
            $pos = $start - ($start + $size) % $size;

            break;
        case 'day':
            $size = 86400;

            if (date('H:i:s', $start) == '00:00:00') {
                $pos = $start;
            } else {
                $pos = strtotime(date('Y-m-d 00:00:00', $start + $size));
            }

            break;
        case 'week':
            $size = 604800;

            if (date('H:i:sw', $start) == '00:00:000') {
                $pos = $start;
            } else {
                $pos = strtotime(date('Y-m-d 00:00:00', $start + $size));
            }

            $pos += 86400 * (6 - date('w', $start));

            break;
        case 'month':
            if (date('d H:i:s', $start) == '01 00:00:00') {
                $pos = $start;
            } else {
                $pos = strtotime(date('Y-m-01 00:00:00', strtotime('next month', $start)));
            }

            $size = date('t', $pos) * 86400;

            break;
        case 'year':
        default:
            if (date('m-d H:i:s', $start) == '01-01 00:00:00') {
                $pos = $start;
            } else {
                $pos = strtotime(date('Y-01-01 00:00:00', strtotime('next year', $start)));
            }

            $size = date('L', $pos) * 86400 + 86400 * 365;

            break;
    }

    $layout = [
        'size' => round($size / $full * 80),
        'blocks' => [
        ],
    ];

    $layout['pad'] = round(($pos - $start) / $full * 80);

    for ($i = $pos; $end > $i + $size; $i += $size) {
        switch ($type) {
            case 'hour':
                $layout['blocks'][] = date('H:i', $i);

                break;
            case 'day':
                $layout['blocks'][] = date('j', $i);

                break;
            case 'week':
                $layout['blocks'][] = date('j', $i);

                break;
            case 'month':
                $layout['blocks'][] = date('M', $i);

                break;
            case 'year':
                $layout['blocks'][] = date('Y', $i);

                break;
        }

        switch ($type) {
            case 'month':
                $size = date('t', $i) * 86400;

                break;
            case 'year':
                $size = date('L', $i) * 86400 + 86400 * 365;

                break;
        }
    }

    return $layout;
}
