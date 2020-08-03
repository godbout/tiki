<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_trackerfilter_info()
{
    require_once 'lib/wiki-plugins/wikiplugin_trackerlist.php';
    $list = wikiplugin_trackerlist_info();
    $params = array_merge(
        [
            'filters' => [
                'required' => true,
                'name' => tra('Filters'),
                'description' => tr(
                    'The list of fields that can be used as filters along with their formats.
					The field number and format are separated by a %0/%1 and multiple fields are separated by %0:%1.',
                    '<code>',
                    '</code>'
                )
                    . tr('Format choices are:') . '<br /><code>d</code> - ' . tr('dropdown')
                    . '<br /><code>r</code> - ' . tr('radio buttons')
                    . '<br /><code>m</code> - ' . tr('multiple choice dropdown')
                    . '<br /><code>c</code> - ' . tr('checkbox')
                    . '<br /><code>t</code> - ' . tr('text with wild characters')
                    . '<br /><code>T</code> - ' . tr('exact text match')
                    . '<br /><code>i</code> - ' . tr('initials')
                    . '<br /><code>sqlsearch</code> - ' . tr('advanced search')
                    . '<br /><code>range</code> - ' . tr('range search (from/to)')
                    . '<br /><code>></code>, <code>><</code>, <code>>>=</code>, <code>><=</code> - ' . tr('greater
						than, less than, greater than or equal, less than or equal.') . '<br />'
                    . tr('Example:') . ' <code>2/d:4/r:5:(6:7)/sqlsearch</code>',
                'since' => '1',
                'doctype' => 'filter',
                'default' => '',
                'profile_reference' => 'tracker_field_string',
            ],
            'action' => [
                'required' => false,
                'name' => tra('Action'),
                'description' => tr('Label on the submit button. Default: %0Filter%1. Use a space character to omit the
					button (for use in datachannels etc)', '<code>', '</code>'),
                'since' => '2.0',
                'doctype' => 'show',
                'default' => 'Filter'
            ],
            'displayList' => [
                'required' => false,
                'name' => tra('Display List'),
                'description' => tra('Show the full list (before filtering) initially (filtered list shown by default)'),
                'since' => '2.0',
                'doctype' => 'show',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'line' => [
                'required' => false,
                'name' => tra('Line'),
                'description' => tra('Displays all the filters on the same line (not shown on same line by default)'),
                'since' => '2.0',
                'doctype' => 'show',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('Yes with field label in dropdown'), 'value' => 'in'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'noflipflop' => [
                'required' => false,
                'name' => tra('No Toggle'),
                'description' => tr('The toggle button to show/hide filters will not be shown if set to Yes (%0y%1).
					Default is not to show the toggle (default changed from "n" to "y" in Tiki 20.0).', '<code>', '</code>'),
                'since' => '6.0',
                'doctype' => 'show',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'export_action' => [
                'required' => false,
                'name' => tra('Export CSV.'),
                'description' => tra('Label for an export button. Leave blank to show the usual "Filter" button instead.'),
                'since' => '6.0',
                'doctype' => 'export',
                'default' => '',
                'advanced' => true,
            ],
            'export_status' => [
                'required' => false,
                'name' => tra('Export Status Field'),
                'description' => tra('Export the status field if the Export CSV option is used'),
                'since' => '11.1',
                'advanced' => true,
                'filter' => 'alpha',
                'doctype' => 'export',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'export_created' => [
                'required' => false,
                'name' => tra('Export Created Date Field'),
                'description' => tra('Export the created date field if the Export CSV option is used'),
                'since' => '11.1',
                'advanced' => true,
                'filter' => 'alpha',
                'doctype' => 'export',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'export_modif' => [
                'required' => false,
                'name' => tra('Export Modified Date Field'),
                'description' => tra('Export the modified date field if the Export CSV option is used'),
                'since' => '11.1',
                'advanced' => true,
                'filter' => 'alpha',
                'doctype' => 'export',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'export_charset' => [
                'required' => false,
                'name' => tra('Export Character Set'),
                'description' => tra('Character set to be used if the Export CSV option is used'),
                'since' => '11.1',
                'doctype' => 'export',
                'default' => 'UTF-8',
                'advanced' => true,
            ],
            'mapButtons' => [
                'required' => false,
                'name' => tra('Map View Buttons'),
                'description' => tra('Display Mapview and Listview buttons'),
                'since' => '6.0' . tr(' - was %0 until 12.0', '<code>googlemapButtons</code>'),
                'filter' => 'alpha',
                'doctype' => 'show',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
        $list['params']
    );

    return [
        'name' => tra('Tracker Filter'),
        'documentation' => 'PluginTrackerFilter',
        'description' => tra('Create a form to filter tracker fields'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_trackerfilter' ],
        'body' => tra('notice'),
        'iconname' => 'filter',
        'introduced' => 1,
        'params' => $params,
        'format' => 'html',
        'extraparams' => true,
    ];
}

function wikiplugin_trackerfilter($data, $params)
{
    global $prefs;
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');
    static $iTrackerFilter = 0;
    if ($prefs['feature_trackers'] != 'y') {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    $iTrackerFilter++;
    $default = ['noflipflop' => 'y', 'action' => 'Filter', 'line' => 'n', 'displayList' => 'n', 'export_action' => '',
                     'export_itemid' => 'y', 'export_status' => 'n', 'export_created' => 'n', 'export_modif' => 'n', 'export_charset' => 'UTF-8', 'status' => 'opc'];

    if (isset($_REQUEST['reset_filter'])) {
        wikiplugin_trackerFilter_reset_filters($iTrackerFilter);
    } elseif (! isset($_REQUEST['filter']) && isset($_REQUEST['session_filters']) && $_REQUEST['session_filters'] == 'y') {
        $params = array_merge($params, wikiplugin_trackerFilter_get_session_filters($iTrackerFilter));
    }
    if (isset($_REQUEST["mapview"]) && $_REQUEST["mapview"] == 'y' && ! isset($_REQUEST["searchmap"]) && ! isset($_REQUEST["searchlist"]) || isset($_REQUEST["searchmap"]) && ! isset($_REQUEST["searchlist"])) {
        $params["showmap"] = 'y';
        $smarty->assign('mapview', true);
    }
    if (isset($_REQUEST["mapview"]) && $_REQUEST["mapview"] == 'n' && ! isset($_REQUEST["searchmap"]) && ! isset($_REQUEST["searchlist"]) || isset($_REQUEST["searchlist"]) && ! isset($_REQUEST["searchmap"])) {
        $params["showmap"] = 'n';
        $smarty->assign('mapview', false);
    }
    $params = array_merge($default, $params);
    if ($params['noflipflop'] !== 'n') {
        $params['noflipflop'] = 'y';
    }
    extract($params, EXTR_SKIP);
    $dataRes = '';

    if (isset($_REQUEST['msgTrackerFilter'])) {
        $smarty->assign('msgTrackerFilter', $_REQUEST['msgTrackerFilter']);
    }

    $headerlib = TikiLib::lib('header');

    /**
     * adding spinner when clicking on the filter button
     *added by Axel.mwenze on  Monday, august 05, 2019
     */
    $headerlib->add_jq_onready(
        '$("#form-filter").submit(function(r) { 
				$(".trackerfilter_loader").show();
				return true;
		})'
    );

    $headerlib->add_jq_onready(
        '/* Maintain state of other trackerfilter plugin forms */
					$(".trackerfilter form").submit( function () {
						var current_tracker = this;
						$(current_tracker).append("<input type=\"hidden\" name=\"tracker_filters[]\" value=\"" + $(current_tracker).serialize() + "\" />")
						$(".trackerfilter form").each( function() {
							if (current_tracker !== this && $("input[name=count_item]", this).val() > 0) {
								$(current_tracker).append("<input type=\"hidden\" name=\"tracker_filters[]\" value=\"" + $(this).serialize() + "\" />")
							}
						});
						return true;
					});'
    );
    if ($prefs['jquery_ui_chosen'] === 'y') {
        $headerlib->add_css('@media (min-width: 768px) { .tiki #col1 .trackerfilter form .table-responsive { overflow-x: visible; overflow-y: visible; }} /* jquery_ui_chosen specific: edit this in wikiplugin_trackerfilter.php */');
    } // TODO: move the CSS to less and add class html attribute in wikiplugin_trackerfilter.tpl instead

    if (! empty($_REQUEST['tracker_filters']) && count($_REQUEST['tracker_filters']) > 0) {
        foreach ($_REQUEST['tracker_filters'] as $tf_vals) {
            parse_str(urldecode($tf_vals), $vals);
            foreach ($vals as $k => $v) {
                // if it's me and i had some items
                if ($k == 'iTrackerFilter' && $v == $iTrackerFilter && isset($vals['count_item']) && $vals['count_item'] > 0) {
                    // unset request params for all the plugins (my one will be array_merged below)
                    foreach ($_REQUEST['tracker_filters'] as $tf_vals2) {
                        parse_str(urldecode($tf_vals2), $vals2);
                        foreach ($vals2 as $k2 => $v2) {
                            unset($GLOBALS['_REQUEST'][$k2]);
                        }
                    }
                    $_REQUEST = array_merge($_REQUEST, $vals);
                }
            }
        }
    }
    if (! empty($_REQUEST['filter']) || ! empty($_REQUEST['reset_filter'])) {  // If we set a new filter, reset pagination for this plugin
        unset($GLOBALS['_REQUEST']["tr_offset$iTrackerFilter"]);
    }

    if (! isset($filters)) {
        if (empty($export_action)) {
            return tra('missing parameters') . ' filters';
        }
        $listfields = [];
        $filters = [];
        $formats = [];
    } else {
        $listfields = wikiplugin_trackerFilter_split_filters($filters);
        foreach ($listfields as $i => $f) {
            if (strchr($f, '/')) {
                list($fieldId, $format) = explode('/', $f);
                $listfields[$i] = $fieldId;
                $formats[$fieldId] = $format;
            } else {
                $formats[$f] = '';
            }
        }
    }
    if (empty($trackerId) && ! empty($_REQUEST['trackerId'])) {
        $trackerId = $_REQUEST['trackerId'];
    }

    $tracker_definition = Tracker_Definition::get($trackerId);

    if (empty($_REQUEST['filter']) && empty($export_action)) { // look if not coming from an initial and not exporting
        foreach ($_REQUEST as $key => $val) {
            if (substr($key, 0, 2) == 'f_') {
                $_REQUEST['filter'] = 'y';

                break;
            }
        }
    }
    if (! isset($sortchoice)) {
        $sortchoice = '';
    } else {
        unset($params['sortchoice']);
        if (isset($_REQUEST["tr_sort_mode$iTrackerFilter"])) {
            $params['sort_mode'] = $_REQUEST["tr_sort_mode$iTrackerFilter"];
        }
        foreach ($sortchoice as $i => $sc) {
            $sc = explode('|', $sc);
            $sortchoice[$i] = ['value' => $sc[0], 'label' => empty($sc[1]) ? $sc[0] : $sc[1]];
        }
    }
    if (empty($trackerId) || ! ($tracker = $trklib->get_tracker($trackerId))) {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    $filters = wikiplugin_trackerFilter_get_filters($trackerId, $listfields, $formats, $status);
    if (empty($export_action)) {
        if (! is_array($filters)) {
            return $filters;
        }
    }
    if (($displayList == 'y' || isset($_REQUEST['filter']) || isset($_REQUEST["tr_offset$iTrackerFilter"]) || isset($_REQUEST['tr_sort_mode'])) &&
                (! isset($_REQUEST['iTrackerFilter']) || $_REQUEST['iTrackerFilter'] == $iTrackerFilter)) {
        $ffs = [];
        $values = [];
        $exactValues = [];
        wikiplugin_trackerfilter_build_trackerlist_filter($_REQUEST, $formats, $ffs, $values, $exactValues, $tracker_definition);
        // echo '<pre>BUILD_FILTER'; print_r($ffs); print_r($exactValues); echo '</pre>';

        $params['fields'] = isset($fields) ? $fields : [];
        if (empty($params['trackerId'])) {
            $params['trackerId'] = $trackerId;
        }
        if (! empty($ffs)) {
            if (empty($params['filterfield'])) {
                $params['filterfield'] = $ffs;
                $params['exactvalue'] = $exactValues;
                $params['filtervalue'] = $values;
            } else {
                if (! isset($params['exactvalue']) && ! isset($params['filtervalue'])) {
                    Feedback::error(tr('TrackerFilter: Wrong parameter specified - filterfield exists but exactvalue or filtervalue not set.'));
                }
                $c = count($params['filterfield']);
                $params['filterfield'] = array_merge($params['filterfield'], $ffs);
                for ($i = 0; $i < $c; ++$i) {
                    $params['exactvalue'][$i] = empty($params['exactvalue'][$i]) ? '' : $params['exactvalue'][$i];
                    $params['filtervalue'][$i] = empty($params['filtervalue'][$i]) ? '' : $params['filtervalue'][$i];
                }
                $params['exactvalue'] = array_merge($params['exactvalue'], $exactValues);
                $params['filtervalue'] = array_merge($params['filtervalue'], $values);
            }
        }
        if (empty($params['max'])) {
            $params['max'] = $prefs['maxRecords'];
        }
        if (! empty($_REQUEST['f_status'])) {
            $params['status'] = $_REQUEST['f_status'];
        }
        wikiplugin_trackerFilter_save_session_filters($params, $iTrackerFilter);
        $smarty->assign('urlquery', wikiplugin_trackerFilter_build_urlquery($params));
        include_once('lib/wiki-plugins/wikiplugin_trackerlist.php');
        $dataRes .= wikiplugin_trackerlist($data, $params);
    } else {
        $data = '';
    }

    $smarty->assign_by_ref('sortchoice', $sortchoice);
    $smarty->assign_by_ref('filters', $filters);
    //echo '<pre>';print_r($filters); echo '</pre>';
    $smarty->assign_by_ref('trackerId', $trackerId);
    $smarty->assign('line', ($line == 'y' || $line == 'in') ? 'y' : 'n');
    $smarty->assign('indrop', $line == 'in' ? 'y' : 'n');
    $smarty->assign('iTrackerFilter', $iTrackerFilter);
    if (! empty($export_action)) {
        $smarty->assign('export_action', $export_action);
        $smarty->assign('export_fields', implode(':', $fields));
        $smarty->assign('export_itemid', $export_itemid == 'y' ? 'on' : '');
        $smarty->assign('export_status', $export_status == 'y' ? 'on' : '');
        $smarty->assign('export_created', $export_created == 'y' ? 'on' : '');
        $smarty->assign('export_modif', $export_modif == 'y' ? 'on' : '');
        $smarty->assign('export_charset', $export_charset);
        if (! empty($_REQUEST['itemId']) && (empty($ignoreRequestItemId) || $ignoreRequestItemId != 'y')) {
            $smarty->assign('export_itemId', $_REQUEST['itemId']);
        }


        if (empty($params['filters'])) {
            if (! empty($filterfield)) { 	// convert param filters to export params
                $f_fields = [];
                for ($i = 0, $cfilterfield = count($filterfield); $i < $cfilterfield; $i++) {
                    if (! empty($exactvalue[$i])) {
                        $f_fields['f_' . $filterfield[$i]] = $exactvalue[$i];
                    } elseif (! empty($filtervalue[$i])) {
                        $f_fields['f_' . $filterfield[$i]] = $filtervalue[$i];
                        $f_fields['x_' . $filterfield[$i]] = 't';	// x_ is for not exact?
                    }
                }
                $smarty->assign_by_ref('f_fields', $f_fields);
            }
            $filters = [];	// clear out filters set up earlier which default to all fields if not exporting
        } else {
            $f_fields = [];
            foreach ($formats as $fid => $fformat) {
                $f_fields['x_' . $fid] = $fformat;  // x_ is for not exact
            }
            $smarty->assign_by_ref('f_fields', $f_fields);
        }
    }
    if ($displayList == 'n' || ! empty($_REQUEST['filter']) || $noflipflop !== 'n' || $prefs['javascript_enabled'] != 'y' || (isset($_SESSION['tiki_cookie_jar']["show_trackerFilter$iTrackerFilter"]) && $_SESSION['tiki_cookie_jar']["show_trackerFilter$iTrackerFilter"] == 'y')) {
        $open = 'y';
        $_SESSION['tiki_cookie_jar']["show_trackerFilter$iTrackerFilter"] = 'y';
    } else {
        $open = 'n';
    }
    $smarty->assign_by_ref('open', $open);
    $smarty->assign_by_ref('action', $action);
    $smarty->assign_by_ref('noflipflop', $noflipflop);
    $smarty->assign_by_ref('dataRes', $dataRes);

    if (isset($mapButtons)) {
        $smarty->assign('mapButtons', $mapButtons);
    }

    $dataF = $smarty->fetch('wiki-plugins/wikiplugin_trackerfilter.tpl');

    static $first = true;

    if ($first) {
        $first = false;
        $headerlib->add_jq_onready(
            '$("a.prevnext", "#trackerFilter' . $iTrackerFilter . ' + .trackerfilter-result").click( function( e ) {
				e.preventDefault();
				$("#trackerFilter' . $iTrackerFilter . ' form")
				.attr("action", $(this).attr("href"))
				.submit();
			} );'
        );
    }

    return $data . $dataF;
}

function wikiplugin_trackerfilter_build_trackerlist_filter($input, $formats, &$ffs, &$values, &$exactValues, Tracker_Definition $tracker_definition)
{
    $trklib = TikiLib::lib('trk');

    foreach ($input as $key => $val) {
        if (substr($key, 0, 2) == 'f_' && ! empty($val) && (! is_array($val) || ! empty($val[0]))) {
            if (! is_array($val)) {
                $val = urldecode($val);
            }
            if ($val === '-Blank (no data)-') {
                $val = '';
            }
            $fieldId = substr($key, 2);
            $field = $tracker_definition->getField((int)$fieldId);

            if ($fieldId == 'status') {
                continue;
            }
            if (preg_match('/([0-9]+)(Month|Day|Year|Hour|Minute|Second)/', $fieldId, $matches)) { // a date
                if (! in_array($matches[1], $ffs)) {
                    $fieldId = $matches[1];
                    $ffs[] = $matches[1];
                    // TO do optimize get options of the field
                    $date = $trklib->build_date($_REQUEST, $trklib->get_tracker_field($fieldId), 'f_' . $fieldId);
                    if (empty($formats[$fieldId])) { // = date
                        $exactValues[] = $date;
                    } else { // > or < data
                        $exactValues[] = [$formats[$fieldId] => $date];
                    }
                }
            } elseif ($field['type'] == 'F') {
                // if field type is freetag force the use of $values instead of $exactValues
                $ffs[] = $fieldId;

                if (is_array($val)) {
                    $val = implode('%', $val);
                }

                $values[] = "%$val%";
            } else {
                if (preg_match("/\d+_(from|to)(Month|Day|Year|Hour|Minute|Second)?/", $fieldId, $m)) { // range filter
                    $fieldId = (int)$fieldId;
                    $formats[$fieldId] = ($m[1] == 'from' ? '>=' : '<=');

                    if (! empty($m[2])) {
                        if ($m[2] != 'Year') {
                            continue;
                        }
                        $val = $trklib->build_date($_REQUEST, $trklib->get_tracker_field($fieldId), 'f_' . $fieldId . '_' . $m[1]);
                    } else {
                        $handler = $trklib->get_field_handler($field);
                        $input['ins_' . $fieldId] = $val;
                        $data = $handler->getFieldData($input);
                        $val = $data['value'];
                    }
                }
                if (! is_numeric($fieldId)) { // composite filter
                    $ffs[] = ['sqlsearch' => explode(':', str_replace(['(', ')'], '', $fieldId))];
                } else {
                    $ffs[] = $fieldId;
                }
                if (isset($formats[$fieldId]) && ($formats[$fieldId] == 't' || $formats[$fieldId] == 'm' || $formats[$fieldId] == 'i')) {
                    $exactValues[] = '';
                    $values[] = ($formats[$fieldId] == 'i') ? "$val%" : $val;
                } else {
                    if (! empty($formats[$fieldId]) && preg_match('/[\>\<]+/', $formats[$fieldId])) {
                        $exactValues[] = [$formats[$fieldId] => $val];
                    } else {
                        $exactValues[] = $val;
                    }
                    $values[] = '';
                }
            }
        }
    }
}

function wikiplugin_trackerFilter_reset_filters($iTrackerFilter = 0)
{
    unset($_SESSION[wikiplugin_trackerFilter_get_session_filters_key($iTrackerFilter)]);
    unset($_REQUEST['tracker_filters']);

    foreach ($_REQUEST as $key => $val) {
        if (substr($key, 0, 2) == 'f_') {
            unset($_REQUEST[$key]);
        }
    }
}

function wikiplugin_trackerFilter_get_session_filters_key($iTrackerFilter = 0)
{
    $trackerId = isset($_REQUEST['trackerId']) ? $_REQUEST['trackerId'] : 0;
    if (! empty($_REQUEST['page'])) {
        return 'f_' . $_REQUEST['page'] . '_' . $iTrackerFilter;
    }

    return '';
}

function wikiplugin_trackerFilter_save_session_filters($filters, $iTrackerFilter = 0)
{
    $_SESSION[wikiplugin_trackerFilter_get_session_filters_key($iTrackerFilter)] = $filters;
}

function wikiplugin_trackerFilter_get_session_filters($iTrackerFilter = 0)
{
    $key = wikiplugin_trackerFilter_get_session_filters_key($iTrackerFilter);

    if (! isset($_SESSION[$key])) {
        return [];
    }

    if (isset($_SESSION[$key]['filterfield'])) {
        foreach ($_SESSION[$key]['filterfield'] as $idx => $field) {
            $_REQUEST['f_' . $field] = $_SESSION[$key]['filtervalue'][$idx];
        }
    }

    return $_SESSION[$key];
}

function wikiplugin_trackerFilter_split_filters($filters)
{
    if (empty($filters)) {
        return [];
    }
    $in = false;
    for ($i = 0, $max = strlen($filters); $i < $max; ++$i) {
        if ($filters[$i] == '(') {
            $in = true;
        } elseif ($filters[$i] == ')') {
            $in = false;
        } elseif ($in && $filters[$i] == ':') {
            $filters[$i] = ',';
        }
    }
    $list = explode(':', $filters);
    foreach ($list as $i => $filter) {
        $list[$i] = str_replace(',', ':', $filter);
    }

    return $list;
}

function wikiplugin_trackerFilter_get_filters($trackerId = 0, array $listfields = [], &$formats, $status = 'opc')
{
    global $tiki_p_admin_trackers;
    $trklib = TikiLib::lib('trk');
    $filters = [];
    if (empty($trackerId) && ! empty($listfields[0])) {
        $field = $trklib->get_tracker_field($listfields[0]);
        $trackerId = $field['trackerId'];
    }

    if ($listfields) {
        $newListFields = [];
        foreach ($listfields as $id => $field) {
            /** @var TrackerLib $trklib */
            $info = $trklib->get_field_info($field);
            if (! is_numeric($field) || ($info && $info['trackerId'] == $trackerId)) {
                $newListFields[] = $field;
            }
        }
        if (count($listfields) != count($newListFields)) {
            $listfields = $newListFields;
            if ($tiki_p_admin_trackers == "y") {
                Feedback::error(tr('TrackerFields: Unknown tracker field used in the parameter "filters"'));
            }
        }
    }

    $fields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '', true, empty($listfields) ? '' : ['fieldId' => $listfields]);
    if (empty($listfields)) {
        foreach ($fields['data'] as $field) {
            $listfields[] = $field['fieldId'];
        }
    }

    $iField = 0;
    foreach ($listfields as $fieldId) {
        if ($fieldId == 'status' || $fieldId == 'Status') {
            $filter = ['name' => $fieldId, 'fieldId' => 'status', 'format' => 'd', 'opts' => [['id' => 'o', 'name' => 'open', 'selected' => (! empty($_REQUEST['f_status']) && $_REQUEST['f_status'] == 'o') ? 'y' : 'n'], ['id' => 'p', 'name' => 'pending', 'selected' => (! empty($_REQUEST['f_status']) && $_REQUEST['f_status'] == 'p') ? 'y' : 'n'], ['id' => 'c', 'name' => 'closed', 'selected' => (! empty($_REQUEST['f_status']) && $_REQUEST['f_status'] == 'c') ? 'y' : 'n']]];
            $filters[] = $filter;

            continue;
        }
        if (! is_numeric($fieldId)) { // composite field
            $filter = ['name' => 'Text', 'fieldId' => $fieldId, 'format' => 'sqlsearch'];
            if (! empty($_REQUEST['f_' . $fieldId])) {
                $filter['selected'] = $_REQUEST['f_' . $fieldId];
            }
            $filters[] = $filter;

            continue;
        }
        foreach ($fields['data'] as $iField => $field) {
            if ($field['fieldId'] == $fieldId) {
                break;
            }
        }
        if (($field['isHidden'] == 'y' || $field['isHidden'] == 'c') && $tiki_p_admin_trackers != 'y') {
            continue;
        }
        if ($field['type'] == 'i' || $field['type'] == 'h' || $field['type'] == 'G' || $field['type'] == 'x') {
            continue;
        }
        $fieldId = $field['fieldId'];
        $res = [];
        if (empty($formats)) {
            $formats = [];
        }
        if (empty($formats[$fieldId])) { // default format depends on field type
            switch ($field['type']) {
                case 'e':// category
                    $res = wikiplugin_trackerfilter_get_categories($field);
                    $formats[$fieldId] = (count($res) >= 6) ? 'd' : 'r';

                    break;
                case 'd': // drop down list
                case 'y': // country
                case 'g': // group selector
                case 'M': // Multiple Values
                    $formats[$fieldId] = 'd';

                    break;
                case 'REL':
                    $formats[$fieldId] = 'REL';

                    break;
                case 'R': // radio
                    $formats[$fieldId] = 'r';

                    break;
                case '*': //rating
                    $formats[$fieldId] = '*';

                    break;
                case 'f':
                case 'j':
                    $formats[$fieldId] = $field['type'];

                    break;
                default:
                    $formats[$fieldId] = 't';

                    break;
            }
        }
        if ($field['type'] == 'e' && ($formats[$fieldId] == 't' || $formats[$fieldId] == 'T' || $formats[$fieldId] == 'i')) { // do not accept a format text for a categ for the moment
            if (empty($res)) {
                $res = wikiplugin_trackerfilter_get_categories($field);
            }
            $formats[$fieldId] = (count($res) >= 6) ? 'd' : 'r';
        }
        $opts = [];
        if ($formats[$fieldId] == 't' || $formats[$fieldId] == 'T' || $formats[$fieldId] == 'i') {
            $selected = empty($_REQUEST['f_' . $fieldId]) ? '' : $_REQUEST['f_' . $fieldId];
        } elseif ($formats[$fieldId] == 'range') {
            // map f_ID_from/to request vars to ins_ ones for tracker fields to parse them
            $from_input = $_REQUEST;
            $to_input = $_REQUEST;
            foreach (['', 'Month', 'Day', 'Year', 'Hour', 'Minute'] as $suffix) {
                if (isset($from_input['f_' . $fieldId . '_from' . $suffix])) {
                    $from_input['ins_' . $fieldId . $suffix] = $from_input['f_' . $fieldId . '_from' . $suffix];
                }
                if (isset($to_input['f_' . $fieldId . '_to' . $suffix])) {
                    $to_input['ins_' . $fieldId . $suffix] = $to_input['f_' . $fieldId . '_to' . $suffix];
                }
            }
            $handler = $trklib->get_field_handler($field);
            $data = $handler->getFieldData($from_input);
            $field['ins_id'] = 'f_' . $field['fieldId'] . '_from';
            $field['value'] = $data['value'];
            $opts['from'] = $field;
            $data = $handler->getFieldData($to_input);
            $field['ins_id'] = 'f_' . $field['fieldId'] . '_to';
            $field['value'] = $data['value'];
            $opts['to'] = $field;
        } else {
            $selected = false;
            switch ($field['type']) {
                case 'e': // category
                    if (empty($res)) {
                        $res = wikiplugin_trackerfilter_get_categories($field);
                    }
                    foreach ($res as $opt) {
                        $opt['id'] = $opt['categId'];
                        if (! empty($_REQUEST['f_' . $fieldId]) && ((is_array($_REQUEST['f_' . $fieldId]) && in_array($opt['id'], $_REQUEST['f_' . $fieldId])) || (! is_array($_REQUEST['f_' . $fieldId]) && $opt['id'] == $_REQUEST['f_' . $fieldId]))) {
                            $opt['selected'] = 'y';
                            $selected = true;
                        } else {
                            $opt['selected'] = 'n';
                        }
                        $opts[] = $opt;
                    }
                    $opts[] = wikiplugin_trackerFilter_add_empty_option($fieldId);

                    break;
                case 'd': // drop down list
                case 'R': // radio buttons
                case '*': // stars
                case 'M': // Multiple Values
                    $cumul = '';
                    foreach ($field['options_array'] as $val) {
                        // check if exists custom label, the format is 'value=label'
                        $delimiterPos = stripos($val, '=');
                        if ($delimiterPos !== false) {
                            $optval = substr($val, 0, $delimiterPos);
                            $sval = substr($val, $delimiterPos + 1);
                        } else {
                            $optval = $val;
                            $sval = $val;
                        }
                        $sval = strip_tags(TikiLib::lib('parser')->parse_data($sval, ['parsetoc' => false]));
                        $opt['id'] = $optval;
                        if ($field['type'] == '*') {
                            $cumul = $opt['name'] = "$cumul*";
                        } else {
                            $opt['name'] = $sval;
                        }
                        if (! empty($_REQUEST['f_' . $fieldId]) && ((! is_array($_REQUEST['f_' . $fieldId]) && $_REQUEST['f_' . $fieldId] == $optval) || (is_array($_REQUEST['f_' . $fieldId]) && in_array($optval, $_REQUEST['f_' . $fieldId])))) {
                            $opt['selected'] = 'y';
                            $selected = true;
                        } else {
                            $opt['selected'] = 'n';
                        }
                        $opts[] = $opt;
                    }
                    $opts[] = wikiplugin_trackerFilter_add_empty_option($fieldId);

                    break;
                case 'c': // checkbox
                    $opt['id'] = 'y';
                    $opt['name'] = 'Yes';
                    if (! empty($_REQUEST['f_' . $fieldId]) && $_REQUEST['f_' . $fieldId] == 'y') {
                        $opt['selected'] = 'y';
                        $selected = true;
                    } else {
                        $opt['selected'] = 'n';
                    }
                    $opts[] = $opt;
                    $opt['id'] = 'n';
                    $opt['name'] = 'No';
                    if (! empty($_REQUEST['f_' . $fieldId]) && $_REQUEST['f_' . $fieldId] == 'n') {
                        $opt['selected'] = 'y';
                        $selected = true;
                    } else {
                        $opt['selected'] = 'n';
                    }
                    $opts[] = $opt;
                    $formats[$fieldId] = 'r';

                    break;
                case 'n': // numeric
                case 'D': // drop down + other
                case 't': // text
                case 'i': // text with initial
                case 'a': // textarea
                case 'm': // email
                case 'y': // country
                case 'k': //page selector
                case 'u': // user
                case 'g': // group
                case 'q': // auto increment
                    if (isset($status)) {
                        $res = $trklib->list_tracker_field_values($trackerId, $fieldId, $status);
                    } else {
                        $res = $trklib->list_tracker_field_values($trackerId, $fieldId);
                    }
                    if ($field['type'] == 'u') {
                        $res = array_unique(
                            call_user_func_array(
                                'array_merge',
                                array_map(
                                    function ($users) use ($trklib) {
                                        return $trklib->parse_user_field($users);
                                    },
                                    $res
                                )
                            )
                        );
                        sort($res, SORT_NATURAL);
                    }
                    foreach ($res as $val) {
                        $sval = strip_tags(TikiLib::lib('parser')->parse_data($val, ['parsetoc' => false]));
                        $opt['id'] = $val;
                        $opt['name'] = $sval;
                        if ($field['type'] == 'y') { // country
                            $opt['name'] = str_replace('_', ' ', $opt['name']);
                        }
                        if (! empty($_REQUEST['f_' . $fieldId]) && ((! is_array($_REQUEST['f_' . $fieldId]) && urldecode($_REQUEST['f_' . $fieldId]) == $val) || (is_array($_REQUEST['f_' . $fieldId]) && in_array($val, $_REQUEST['f_' . $fieldId])))) {
                            $opt['selected'] = 'y';
                            $selected = true;
                        } else {
                            $opt['selected'] = 'n';
                        }
                        $opts[] = $opt;
                    }
                    $opts[] = wikiplugin_trackerFilter_add_empty_option($fieldId);

                    break;
                case 'REL':
                    if (! empty($field['options_map']['filter'])) {
                        parse_str($field['options_map']['filter'], $filter);
                    } else {
                        $filter = [];
                    }
                    $format = '{title}';

                    $opts = [];
                    $opts['field_filter'] = $filter;
                    $opts['field_selection'] = isset($_REQUEST['f_' . $fieldId]) ? $_REQUEST['f_' . $fieldId] : '';
                    $opts['field_format'] = $format;
                    $opts['other_options'][] = wikiplugin_trackerFilter_add_empty_option($fieldId);

                    break;
                case 'w': //dynamic item lists
                case 'r': // item link
                    $opts = [];
                    $handler = $trklib->get_field_handler($field);
                    if ($handler) {
                        $list1 = $handler->getItemList();
                        foreach ($list1 as $id => $option) {
                            $opt['id'] = $id;
                            $opt['name'] = html_entity_decode($option); // this will be escaped by smarty but already escaped from ItemLink
                            if (! empty($_REQUEST['f_' . $fieldId]) &&
                                (
                                    (! is_array($_REQUEST['f_' . $fieldId]) &&
                                        urldecode($_REQUEST['f_' . $fieldId]) == $id) ||
                                    (is_array($_REQUEST['f_' . $fieldId]) &&
                                        in_array($id, $_REQUEST['f_' . $fieldId]))
                                )) {
                                $opt['selected'] = 'y';
                                $selected = true;
                            } else {
                                $opt['selected'] = 'n';
                            }
                            $opts[] = $opt;
                        }
                    }
                    $opts[] = wikiplugin_trackerFilter_add_empty_option($fieldId);

                    break;

                case 'f':
                case 'j':
                    $field['ins_id'] = 'f_' . $field['fieldId'];

                    break;
                case 'F': // freetags
                    $freetaglib = TikiLib::lib('freetag');
                    $opts = [];
                    $tags = [];
                    $items = $trklib->list_items($field['trackerId'], 0, -1, '', [$field]);

                    foreach ($items['data'] as $item) {
                        $tags = array_merge($tags, $item['field_values'][0]['freetags']);
                    }

                    $tags = array_unique($tags);
                    sort($tags);

                    foreach ($tags as $tag) {
                        $selected = false;

                        if (isset($_REQUEST['f_' . $fieldId])) {
                            $selection = $_REQUEST['f_' . $fieldId];

                            if ((is_array($selection) && in_array($tag, $selection)) || $selection == $tag) {
                                $selected = true;
                            }
                        }

                        $opts[] = [
                        'id' => $tag,
                        'name' => $tag,
                        'selected' => $selected,
                        ];
                    }

                    break;
                default:
                    return tra('tracker field type not processed yet') . ' ' . $field['type'];
            }
        }
        $filters[] = ['name' => $field['name'], 'fieldId' => $fieldId, 'format' => $formats[$fieldId], 'opts' => $opts, 'selected' => $selected, 'field' => $field];
    }

    return $filters;
}

/** get get categories for field
 *
 * @param array $field
 * @throws Exception
 * @return array of category arrays
 */
function wikiplugin_trackerfilter_get_categories($field)
{
    $handler = TikiLib::lib('trk')->get_field_handler($field);

    if ($handler) {
        $res = $handler->getFieldData();
        // handle full path setting here
        if ($field['options_map']['descendants'] == 2) {
            foreach ($res['list'] as & $cat) {
                $cat['name'] = $cat['categpath'];
            }
        }

        return $res['list'];
    }

    return [];
}

function wikiplugin_trackerFilter_build_urlquery($params)
{
    if (empty($params['filterfield'])) {
        return [];
    }
    $urlquery = [];
    foreach ($params['filterfield'] as $key => $filter) {
        $filterfield[] = $filter;
        if (! empty($params['exactvalue'][$key]) && empty($params['filtervalue'][$key])) {
            $filtervalue[] = '';
            $exactvalue[] = $params['exactvalue'][$key];
        } else {
            $filtervalue[] = $params['filtervalue'][$key];
            $exactvalue[] = '';
        }
    }
    if (! empty($filterfield)) {
        $urlquery['filterfield'] = implode(':', $filterfield);
        $urlquery['filtervalue'] = implode(':', $filtervalue);
        $urlquery['exactvalue'] = implode(':', array_map(
            function ($ev) {
                return is_array($ev) ?
                    key($ev) . reset($ev)
                    : $ev;
            },
            $exactvalue
        ));
    }
    if (! empty($params['sort_mode'])) {
        $urlquery['sort_mode'] = $params['sort_mode'];
    }

    return $urlquery;
}

function wikiplugin_trackerFilter_add_empty_option($fieldId)
{
    $empty = '-Blank (no data)-';

    return [
        'id' => $empty,
        'name' => $empty,
        'selected' => (! empty($_REQUEST['f_' . $fieldId]) && ((! is_array($_REQUEST['f_' . $fieldId]) && $_REQUEST['f_' . $fieldId] === $empty) || (is_array($_REQUEST['f_' . $fieldId]) && in_array($empty, $_REQUEST['f_' . $fieldId])))) ? 'y' : 'n'
    ];
}
