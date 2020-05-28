<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$inputConfiguration = [[
		'staticKeyFilters'	=> [
			'itemId'		=> 'int',
			'fieldId'		=> 'int',
			'version'		=> 'int',
			'offset'		=> 'int',
			'diff_style'	=> 'word',
			'Filter'		=> 'word',
		]],
			['catchAllUnset' => null],
];
$section = 'trackers';
require_once('tiki-setup.php');

$access->check_feature('feature_trackers');

$trklib = TikiLib::lib('trk');

$auto_query_args = ['offset', 'itemId', 'fieldId', 'filter'];

if (! empty($_REQUEST['itemId'])) {
	$item_info = $trklib->get_tracker_item($_REQUEST['itemId']);
	$item = Tracker_Item::fromInfo($item_info);
	if (! $item->canView()) {
		$smarty->assign('errortype', 401);
		$smarty->assign('msg', tra('You do not have permission to view this page.'));
		$smarty->display('error.tpl');
		die;
	}
	$fieldId = empty($_REQUEST['fieldId']) ? 0 : $_REQUEST['fieldId'];
	$smarty->assign_by_ref('fieldId', $fieldId);
	$filter = [];
	if (! empty($_REQUEST['version'])) {
		$filter['version'] = $_REQUEST['version'];
	}
	$smarty->assign_by_ref('filter', $filter);
	$offset = empty($_REQUEST['offset']) ? 0 : $_REQUEST['offset'];
	$smarty->assign('offset', $offset);

	if (! empty($item_info)) {
		$history = $trklib->get_item_history($item_info, $fieldId, $filter, $offset, $prefs['maxRecords']);
		$smarty->assign_by_ref('history', $history['data']);
		$smarty->assign_by_ref('cant', $history['cant']);

		foreach ($history['data'] as $i => $hist) {
			if (empty($field_option[$hist['fieldId']])) {
				if ($hist['fieldId'] > 0) {
					$field_option[$hist['fieldId']] = $trklib->get_tracker_field($hist['fieldId']);
				}
				else {
					$field_option[$hist['fieldId']] = [	// fake field to do the diff on
						'type' => 't',
						'name' => tr('Status'),
						'trackerId' => $item_info['trackerId'],
					];
				}
			}
		}

		$diff_style = empty($_REQUEST['diff_style']) ? $prefs['tracker_history_diff_style'] : $_REQUEST['diff_style'];
		$smarty->assign('diff_style', $diff_style);

		$smarty->assign_by_ref('item_info', $item_info);
		$smarty->assign_by_ref('field_option', $field_option);
	}
	else {
		$smarty->assign('errortype', 401);
		$smarty->assign('msg', tra('This tracker item either has been deleted or is not found.'));
		$smarty->display('error.tpl');
		die;
	}
}

$tiki_actionlog_conf = TikiDb::get()->table('tiki_actionlog_conf');
$logging = $tiki_actionlog_conf->fetchCount(
	[
		'objectType' => 'trackeritem',
		'action' => $tiki_actionlog_conf->in(['Created','Updated']),
		'status' => $tiki_actionlog_conf->in(['y','v']),
	]
);
// disallow robots to index page
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('logging', $logging);

$smarty->assign('mid', 'tiki-tracker_view_history.tpl');
$smarty->display('tiki.tpl');
