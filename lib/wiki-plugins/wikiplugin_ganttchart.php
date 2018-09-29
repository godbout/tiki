<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_ganttchart_info()
{
	return [
		'name' => tr('Gantt Chart'),
		'description' => tr('Create and display a gantt graphic using tracker data'),
		'prefs' => ['wikiplugin_ganttchart'],
		'tags' => ['experimental'],
		'introduced' => 19,
		'format' => 'html',
		'iconname' => 'table',
		'params' => [
			'trackerId' => [
				'name' => tr('Tracker ID'),
				'description' => tr('Tracker to search from'),
				'required' => true,
				'default' => 0,
				'filter' => 'int',
				'profile_reference' => 'tracker',
				'since' => 19,
			],
			'status' => [
				'name' => tr('Status Field'),
				'description' => tr('Permanent name of the field to use for row status'),
				'required' => true,
				'filter' => 'word',
				'since' => 19,
			],
			'progress' => [
				'name' => tr('Progress Field'),
				'description' => tr('Permanent name of the field to use for row progress, values between 0-100'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'name' => [
				'name' => tr('Name Field'),
				'description' => tr('Permanent name of the field to use for row name'),
				'required' => true,
				'filter' => 'word',
				'since' => 19,
			],
			'description' => [
				'name' => tr('Description Field'),
				'description' => tr('Permanent name of the field to use for row description'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'code' => [
				'name' => tr('Code Field'),
				'description' => tr('Permanent name of the field to use for row code'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'begin' => [
				'name' => tr('Begin Date Field'),
				'description' => tr('Permanent name of the field to use for event beginning'),
				'required' => true,
			],
			'end' => [
				'name' => tr('End Date Field'),
				'description' => tr('Permanent name of the field to use for event ending'),
				'required' => true,
				'since' => 19,
			],
			'resourceId' => [
				'name' => tr('Resources Field'),
				'description' => tr('Permanent name of the field to use for resources ending'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'roleId' => [
				'name' => tr('Roles Field'),
				'description' => tr('Permanent name of the field to use for roles ending'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'effort' => [
				'name' => tr('Effort Field'),
				'description' => tr('Permanent name of the field to use for effort ending, values in milliseconds'),
				'required' => false,
				'filter' => 'word',
				'since' => 19,
			],
			'startIsMilestone' => [
				'name' => tr('Start Is Milestone'),
				'description' => tr('Flag field to start is milestone'),
				'required' => false,
				'filter' => 'none',
				'since' => 19,
			],
			'endIsMilestone' => [
				'name' => tr('End Is Milestone'),
				'description' => tr('Flag field to end is milestone'),
				'required' => false,
				'filter' => 'none',
				'since' => 19,
			],
			'canWrite' => [
				'name' => tr('Writable'),
				'description' => tr('Flag field to write or not in tasks'),
				'required' => false,
				'filter' => 'none',
				'since' => 19,
			],
			'canDelete' => [
				'name' => tr('Deletable'),
				'description' => tr('Flag field that permit to delete or not tasks'),
				'required' => false,
				'filter' => 'none',
				'since' => 19,
			],
			'canWriteOnParent' => [
				'name' => tr('Parent Writable'),
				'description' => tr('Flag field to write or not in parent tasks'),
				'required' => false,
				'filter' => 'none',
				'since' => 19,
			],
		],
	];
}

function wikiplugin_ganttchart($data, $params)
{
	//checking if vendor files are present
	if (! file_exists('vendor_bundled/vendor/robicch/jquery-gantt')) {
		return WikiParser_PluginOutput::internalError(tr('Missing required files, please make sure plugin files are installed at vendor_bundled/vendor/robicch/jquery-gantt. <br/><br /> To install, please run composer.'));
	}

	$access = TikiLib::lib('access');
	$access->setTicket();
	$trackerId = $params['trackerId'];
	$status = $params['status'];
	$progress = ! empty($params['progress']) ? $params['progress'] : '';
	$name = $params['name'];
	$description = $params['description'];
	$code = $params['code'];
	$begin = $params['begin'];
	$end = $params['end'];
	$resources = ! empty($params['resourceId']) ? $params['resourceId'] : '';
	$roles = ! empty($params['roleId']) ? $params['roleId'] : '';
	$effort = ! empty($params['effort']) ? $params['effort'] : '';
	$startIsMilestone = ! empty($params['startIsMilestone']) ? $params['startIsMilestone'] : '';
	$endIsMilestone = ! empty($params['endIsMilestone']) ? $params['endIsMilestone'] : '';
	$canWrite = ! empty($params['canWrite']) ? $params['canWrite'] : false;
	$canDelete = ! empty($params['canDelete']) ? $params['canDelete'] : false;
	$canWriteOnParent = ! empty($params['canWriteOnParent']) ? $params['canWriteOnParent'] : false;

	$definition = Tracker_Definition::get($trackerId);
	if (! $definition) {
		return WikiParser_PluginOutput::userError(tr('Tracker data source not found.'));
	}
	$trklib = TikiLib::lib('trk');
	$trackerDefinition = Tracker_Definition::get($trackerId);
	$listfields = $trackerDefinition->getFields();
	$listItems = $trklib->list_items($trackerId, 0, -1, 'lastModif_asc', $listfields);
	$ganttValues = [];
	$ganttResources = [];
	$ganttRoles = [];
	$allResources = [];
	$allRoles = [];
	$allLevel = [];

	$resourceId = 1;
	$roleId = 1;
	foreach ($listItems['data'] as $item) {
		$fieldItemValues = $item['field_values'];
		$itemId = 0;
		$fieldStatus = '';
		$fieldName = '';
		$fieldProgress = 0;
		$fieldDescription = '';
		$fieldCode = '';
		$fieldStartDate = '';
		$fieldEndDate = '';
		$selectedResourceValue = '';
		$selectedRoleValue = '';
		$fieldEffort = 0;
		$fieldStartIsMilestone = false;
		$fieldEndIsMilestone = false;
		$hasChild = false;

		foreach ($fieldItemValues as $fieldItem) {
			if (! isset($allLevel[$fieldItem['itemId']])) {
				$allLevel[$fieldItem['itemId']] = 0;
			}
			if (in_array($fieldItem['type'], ['r']) && ! empty($fieldItem['value'])) {
				$level = isset($allLevel[$fieldItem['value']]) ? $allLevel[$fieldItem['value']] : 0;
				$level++;
				$allLevel[$fieldItem['itemId']] = $level;
			}
			if ($status == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldStatus = $fieldItem['value'];
			}
			if ($progress == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldProgress = $fieldItem['value'];
			}
			if ($name == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldName = $fieldItem['value'];
				$itemId = $fieldItem['itemId'];
			}
			if ($description == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldDescription = $fieldItem['value'];
			}
			if ($code == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldCode = $fieldItem['value'];
			}
			if ($begin == $fieldItem['permName'] && is_numeric($fieldItem['value'])) {
				$fieldStartDate = ($fieldItem['value'] * 1000);
			}
			if ($end == $fieldItem['permName'] && is_numeric($fieldItem['value'])) {
				$fieldEndDate = ($fieldItem['value'] * 1000);
			}
			if ($resources == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				if (! in_array($fieldItem['value'], $ganttResources)) {
					$ganttResources[] = $fieldItem['value'];
					$allResources[] = [
						'id' => 'resource_' . $resourceId,
						'name' => $fieldItem['value']
					];
				}
				$selectedResourceValue = $fieldItem['value'];
				$resourceId++;
			}
			if ($roles == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				if (! in_array($fieldItem['value'], $ganttRoles)) {
					$ganttRoles[] = $fieldItem['value'];
					$allRoles[] = [
						'id' => 'role_' . $roleId,
						'name' => $fieldItem['value']
					];
				}
				$selectedRoleValue = $fieldItem['value'];
				$roleId++;
			}
			if ($effort == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldEffort = $fieldItem['value'];
			}
			if ($startIsMilestone == $fieldItem['permName'] && $fieldItem['value'] == 'y') {
				$fieldStartIsMilestone = true;
			}
			if ($endIsMilestone == $fieldItem['permName'] && $fieldItem['value'] == 'y') {
				$fieldEndIsMilestone = true;
			}
		}

		$numDays = countWorkingDays(($fieldStartDate / 1000), ($fieldEndDate / 1000));

		$resourceIdValue = '';
		foreach ($allResources as $resource) {
			if ($resource['name'] == $selectedResourceValue) {
				$resourceIdValue = $resource['id'];
			}
		}
		$roleIdValue = '';
		foreach ($allRoles as $role) {
			if ($role['name'] == $selectedRoleValue) {
				$roleIdValue = $role['id'];
			}
		}

		$ganttValues[] = [
			'id' => $itemId,
			'name' => $fieldName,
			'progress' => $fieldProgress,
			'progressByWorklog' => false,
			'relevance' => $percentageToFinish * 100,
			'type' => '',
			'typeId' => '',
			'description' => $fieldDescription,
			'code' => $fieldCode,
			'level' => $allLevel[$itemId],
			'status' => $fieldStatus,
			'depends' => '',
			'canWrite' => $canWrite,
			'start' => $fieldStartDate,
			'duration' => $numDays,
			'end' => $fieldEndDate,
			'startIsMilestone' => $fieldStartIsMilestone,
			'endIsMilestone' => $fieldEndIsMilestone,
			'collapsed' => false,
			'assigs' => [
				0 => [
					'resourceId' => $resourceIdValue,
					'id' => uniqid(),
					'roleId' => $roleIdValue,
					'effort' => intval($fieldEffort)
				]
			],
			'hasChild' => $allLevel[$itemId] > 0 ? true : false
		];
	}

	$info = ! empty($_POST) ? $_POST : [];
	save($info, $params, $allResources, $allRoles);

	$headerlib = TikiLib::lib('header');
	$headerlib->add_cssfile('vendor_bundled/vendor/robicch/jquery-gantt/platform.css');
	$headerlib->add_cssfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/dateField/jquery.dateField.css');
	$headerlib->add_cssfile('vendor_bundled/vendor/robicch/jquery-gantt/gantt.css');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/jquery.livequery.1.1.1.min.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/jquery.timers.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/utilities.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/forms.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/date.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/dialogs.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/layout.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/i18nJs.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/dateField/jquery.dateField.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/JST/jquery.JST.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/svg/jquery.svg.min.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/libs/jquery/svg/jquery.svgdom.1.8.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttUtilities.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttTask.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttDrawerSVG.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttZoom.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttGridEditor.js');
	$headerlib->add_jsfile('vendor_bundled/vendor/robicch/jquery-gantt/ganttMaster.js');
	$headerlib->add_jsfile('lib/jquery_tiki/wikiplugin-ganttchart.js', true);

	$smarty = TikiLib::lib('smarty');
	$smarty->assign('trackerId', $trackerId);

	$ganttProject = [
		'tasks' => $ganttValues,
		'deletedTaskIds' => [],
		'resources' => $allResources,
		'roles' => $allRoles,
		'canWrite' => $canWrite,
		'canDelete' => $canDelete,
		'canWriteOnParent' => $canWriteOnParent,
		'zoom' => "w3"
	];
	$smarty->assign('ganttProject', json_encode($ganttProject));

	return $smarty->fetch('wiki-plugins/wikiplugin_ganttchart.tpl');
}

/**
 * Count working days between two dates
 *
 * @param int $startDate
 * @param int $endDate
 * @return int
 */
function countWorkingDays($startDate, $endDate)
{
	$endDate = ! is_numeric($endDate) ? strtotime($endDate) : $endDate;
	$startDate = ! is_numeric($startDate) ? strtotime($startDate) : $startDate;

	$days = ($endDate - $startDate) / 86400 + 1;

	$no_full_weeks = floor($days / 7);
	$no_remaining_days = fmod($days, 7);

	$the_first_day_of_week = date("N", $startDate);
	$the_last_day_of_week = date("N", $endDate);

	if ($the_first_day_of_week <= $the_last_day_of_week) {
		if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) {
			$no_remaining_days--;
		}
		if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) {
			$no_remaining_days--;
		}
	} else {
		if ($the_first_day_of_week == 7) {
			$no_remaining_days--;
			if ($the_last_day_of_week == 6) {
				$no_remaining_days--;
			}
		} else {
			$no_remaining_days -= 2;
		}
	}

	$workingDays = $no_full_weeks * 5;
	if ($no_remaining_days > 0) {
		$workingDays += $no_remaining_days;
	}

	return intval($workingDays);
}

/**
 * Convert time into milliseconds
 *
 * @param string $time
 * @return int
 */
function getTimeToMilliseconds($time)
{
	$time = ! empty($time) ? explode(":", $time) : '';
	$milisec = 0;
	if (isset($time[0]) && isset($time[1])) {
		$hour = intval($time[0]) * 3600000;
		$minutes = intval($time[1]) * 60000;
		$milisec = $hour + $minutes;
	}

	return $milisec;
}

/**
 * Update tracker item information
 *
 * @param array $info
 * @param array $params
 * @param array $allResources
 * @param array $allRoles
 * @return null
 */
function save($info, $params, $allResources, $allRoles)
{
	$access = TikiLib::lib('access');
	if (! empty($info) && $access->checkCsrf()) {
		$trackerId = ! empty($info['trackerId']) ? $info['trackerId'] : 0;
		$trackerItemId = ! empty($info['trackerItemId']) ? $info['trackerItemId'] : 0;
		$info['resourceId'] = isset($info['resourceId']) ? $info['resourceId'] : '';
		$info['roleId'] = isset($info['roleId']) ? $info['roleId'] : '';
		$info['effort'] = isset($info['effort']) ? $info['effort'] : '';

		if ($trackerId != $params['trackerId']) {
			Feedback::error(tr('You are trying to update a tracker that is not used by this gantt chart'));
		} else {
			$fields = [];
			foreach ($info as $key => $field) {
				$fieldPermanentName = ! empty($params[$key]) ? $params[$key] : null;
				if (is_string($fieldPermanentName)) {
					$value = in_array($key, ['begin', 'end']) ? strtotime($field) : $field;
					if (in_array($key, ['resourceId', 'roleId'])) {
						foreach ($allResources as $resource) {
							if ($resource['id'] == $field) {
								$value = $resource['name'];
							}
						}
						foreach ($allRoles as $role) {
							if ($role['id'] == $field) {
								$value = $role['name'];
							}
						}
					}
					if ($key === 'effort') {
						$value = getTimeToMilliseconds($field);
					}
					$fields[$fieldPermanentName] = $value;
				}
			}

			$item = [
				'itemId' => $trackerItemId,
				'fields' => $fields
			];

			$definition = Tracker_Definition::get($trackerId);
			$trackerUtilities = new Services_Tracker_Utilities();
			$result = $trackerUtilities->updateItem($definition, $item);

			if ($result) {
				Feedback::success(tr('Gantt chart updated'));
				$access->redirect($_SERVER['HTTP_REFERER']);
			}
		}
	}
}
