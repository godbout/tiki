<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
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
			'order' => [
				'name' => tr('Order Field'),
				'description' => tr('Permanent name of the field to use for row number order'),
				'required' => true,
				'filter' => 'word',
				'since' => 19,
			],
			'level' => [
				'name' => tr('Level Field'),
				'description' => tr('Permanent name of the field to use for row level'),
				'required' => true,
				'filter' => 'word',
				'since' => 19,
			],
			'status' => [
				'name' => tr('Status Field'),
				'description' => tr('Permanent name of the field to use for row status'),
				'required' => true,
				'filter' => 'word',
				'since' => 19,
			],
			'depends' => [
				'name' => tr('Dependency Field'),
				'description' => tr('Permanent name of the field to use for row dependency'),
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
		return WikiParser_PluginOutput::internalError(
			tr(
				'Missing required files, please make sure plugin files are installed at vendor_bundled/vendor/robicch/jquery-gantt. <br/><br /> To install, please run composer.'
			)
		);
	}

	$access = TikiLib::lib('access');
	$access->setTicket();
	$trackerId = $params['trackerId'];
	$order = ! empty($params['order']) ? $params['order'] : '';
	$levelParam = ! empty($params['level']) ? $params['level'] : '';
	$status = $params['status'];
	$depends = ! empty($params['depends']) ? $params['depends'] : '';
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
	$canWrite = ! empty($params['canWrite']) ? filter_var($params['canWrite'], FILTER_VALIDATE_BOOLEAN) : false;
	$canDelete = ! empty($params['canDelete']) ? filter_var($params['canDelete'], FILTER_VALIDATE_BOOLEAN) : false;
	$canWriteOnParent = ! empty($params['canWriteOnParent']) ? filter_var($params['canWriteOnParent'], FILTER_VALIDATE_BOOLEAN) : false;

	$definition = Tracker_Definition::get($trackerId);
	if (! $definition) {
		return WikiParser_PluginOutput::userError(tr('Tracker data source not found.'));
	}

	if (empty($order)) {
		return WikiParser_PluginOutput::userError(tr('Order tracker field parameter is mandatory.'));
	}

	if (empty($levelParam)) {
		return WikiParser_PluginOutput::userError(tr('Level tracker field parameter is mandatory.'));
	}

	if (empty($depends)) {
		return WikiParser_PluginOutput::userError(tr('Depends tracker field parameter is mandatory.'));
	}

	$trklib = TikiLib::lib('trk');
	$trackerDefinition = Tracker_Definition::get($trackerId);
	$listfields = $trackerDefinition->getFields();
	$orderField = $trklib->get_tracker_field($order);
	$orderItems = ! empty($orderField['fieldId']) ? 'f_' . $orderField['fieldId'] . '_asc' : 'created_asc';
	$listItems = $trklib->list_items($trackerId, 0, -1, $orderItems, $listfields);
	$ganttValues = [];
	$ganttRoles = [];
	$allResources = [];
	$allRoles = [];
	$allLevel = [];
	$listHasChildren = [];

	//Get all users
	$userlib = TikiLib::lib('user');
	$users = $userlib->list_all_users();

	//fetch user to show in select
	foreach ($users as $key => $value) {
		$allResources[] = [
			'id' => $value,
			'name' => $value,
		];
	}

	$itemIds = [];
	$roleId = 1;
	foreach ($listItems['data'] as $item) {
		$fieldItemValues = $item['field_values'];
		$itemId = 0;
		$fieldOrder = 0;
		$fieldStatus = '';
		$fieldDepends = '';
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
			if ($levelParam == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$level = isset($allLevel[$fieldItem['value']]) ? $allLevel[$fieldItem['value']] : 0;
				$level++;
				$allLevel[$fieldItem['itemId']] = $level;
				$listHasChildren[$fieldItem['value']] = true;
			}
			if ($order == $fieldItem['permName'] && is_numeric($fieldItem['value'])) {
				$fieldOrder = (int)$fieldItem['value'];
			}
			if ($status == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldStatus = $fieldItem['value'];
			}
			if ($depends == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldDepends = $fieldItem['value'];
			}
			if ($progress == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldProgress = $fieldItem['value'];
			}
			if ($name == $fieldItem['permName'] && ! empty($fieldItem['value'])) {
				$fieldName = $fieldItem['value'];
				$itemId = $fieldItem['itemId'];
				$itemIds[] = $itemId;
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
				$selectedResourceValue = $fieldItem['value'];
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
			'order' => $fieldOrder,
			'progress' => $fieldProgress,
			'progressByWorklog' => false,
			'relevance' => 0,
			'type' => '',
			'typeId' => '',
			'description' => $fieldDescription,
			'code' => $fieldCode,
			'level' => $allLevel[$itemId],
			'status' => $fieldStatus,
			'depends' => $fieldDepends,
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
					'effort' => (int)$fieldEffort
				]
			],
			'hasChild' => $hasChild
		];
	}

	$ganttValues = checkChildrens($ganttValues, $listHasChildren);
	$ganttValues = transformDependenciesIdsToIndex($ganttValues, $itemIds);

	$info = ! empty($_POST) ? $_POST : [];

	updateTasks($info, $params, $allResources, $allRoles);
	save($info, $params, $allResources, $allRoles, true);

	$headerlib = TikiLib::lib('header');
	$headerlib->add_cssfile('themes/base_files/feature_css/wikiplugin-ganttchart.css');
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
		'zoom' => "1M"
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

	return (int)$workingDays;
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
		$hour = (int)$time[0] * 3600000;
		$minutes = (int)$time[1] * 60000;
		$milisec = $hour + $minutes;
	}

	return $milisec;
}

/**
 * Update gantt children values
 *
 * @param array $ganttValues
 * @param array $listHasChildren
 * @return array
 */
function checkChildrens($ganttValues, $listHasChildren)
{
	if (! empty($ganttValues) && ! empty($listHasChildren)) {
		foreach ($ganttValues as $key => $ganttItem) {
			if (isset($listHasChildren[$ganttItem['id']])) {
				$ganttValues[$key]['hasChild'] = true;
			}
		}
	}

	return $ganttValues;
}

/**
 * Update tracker item information
 *
 * @param array $info
 * @param array $params
 * @param array $allResources
 * @param array $allRoles
 * @param boolean $notifications
 * @return null
 */
function save($info, $params, $allResources, $allRoles, $notifications = false)
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
					$value = $field;
					if (in_array($key, ['begin', 'end'])) {
						if (strlen($field) > 10) {
							$value = substr($field, 0, -3);
						} else {
							$value = strtotime($field);
						}
					}
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

			if ($result && $notifications) {
				Feedback::success(tr('Gantt chart updated'));
				$access->redirect($_SERVER['HTTP_REFERER']);
			}
		}
	}
}

/**
 * Transform all tasks dependencies ids in to indexes. This is needed to ganttChart.
 *
 * @param array $ganttValues
 * @param array $itemIds
 * @return array
 */
function transformDependenciesIdsToIndex($ganttValues, $itemIds)
{
	foreach ($ganttValues as $key => $value) {
		if (! isset($value['depends']) || strlen($value['depends']) == 0) {
			continue;
		}
		$depends = explode(',', $value['depends']);
		if (is_array($depends)) {
			$indexIds = [];
			foreach ($depends as $itemId) {
				$itemData = explode(':', $itemId);
				$itemId = $itemData[0];
				$itemDays = isset($itemData[1]) ? $itemData[1] : false;
				$index = array_search($itemId, $itemIds);
				if ($index) {
					$index = $index + 1;
					$index = ! empty($itemDays) ? $index . ':' . $itemDays : $index;
					$indexIds[] = $index;
				}
			}
			$depends = ! empty($indexIds) ? implode(',', $indexIds) : '';
		}
		$ganttValues[$key]['depends'] = $depends;
	}

	return $ganttValues;
}

/**
 * Transform all tasks dependencies indexes to ids.
 *
 * @param string $depends
 * @param array $tasks
 * @return string
 */
function transformDependenciesIndexToIds($depends, $tasks)
{
	$dependsIds = '';
	if (! empty($depends) && ! empty($tasks)) {
		$indexes = explode(',', $depends);
		foreach ($indexes as $value) {
			$itemData = explode(':', $value);
			$index = $itemData[0];
			$index = isset($tasks[$index - 1]['id']) ? $tasks[$index - 1]['id'] : false;
			$itemDays = isset($itemData[1]) ? $itemData[1] : false;
			if ($index && $itemDays) {
				$index = $index . ':' . $itemDays;
			}
			$dependsIds .= $index;
		}
	}

	return $dependsIds;
}

/**
 * Update/delete gantt tasks in tracker items
 *
 * @param array $info
 * @return null
 * @throws Services_Exception
 */
function updateTasks($info, $params, $allResources, $allRoles)
{
	$access = TikiLib::lib('access');
	if (! empty($info)
		&& $access->checkCsrf()
		&& ! empty($info['trackerId'])
		&& (! empty($info['deletedIds']) || ! empty($info['tasks']))
	) {
		$tikilib = TikiLib::lib('tiki');
		$trklib = TikiLib::lib('trk');

		$transaction = $tikilib->begin();
		foreach ($info['deletedIds'] as $deletedId) {
			$itemInfo = $trklib->get_item_info($deletedId);
			$actionObject = Tracker_Item::fromInfo($itemInfo);
			if ($actionObject->canRemove()) {
				$trklib->remove_tracker_item($deletedId);
			}
		}
		$transaction->commit();

		$order = 1;
		foreach ($info['tasks'] as $key => $task) {
			// mapping gantt task to tracker items
			$task['trackerId'] = $info['trackerId'];
			$task['trackerItemId'] = $task['id'];
			$task['begin'] = $task['start'];
			$task['order'] = $order;
			$task['resourceId'] = ! empty($task['assigs'][0]['resourceId']) ? $task['assigs'][0]['resourceId'] : '';
			$task['roleId'] = ! empty($task['assigs'][0]['roleId']) ? $task['assigs'][0]['roleId'] : '';
			$task['effort'] = ! empty($task['assigs'][0]['effort']) ? $task['assigs'][0]['effort'] : '';
			$level = ! empty($task['level']) ? $task['level'] : 0;
			$task['level'] = getTaskLevel($level, $info['tasks']);

			if (! empty($task['depends'])) {
				$task['depends'] = transformDependenciesIndexToIds($task['depends'], $info['tasks']);
			}

			unset($task['id']);
			unset($task['start']);
			unset($task['assigs']);

			save($task, $params, $allResources, $allRoles);
			$order++;
		}

		Feedback::success(tr('Gantt chart updated'));
		exit;
	}
}

/**
 * Get tasks level based in gantt identation
 *
 * @param array $taskLevel
 * @param array $allTasks
 * @return int
 */
function getTaskLevel($taskLevel, $allTasks)
{
	$level = 0;
	if (! empty($taskLevel) && $taskLevel > 0 && ! empty($allTasks)) {
		foreach ($allTasks as $key => $task) {
			$matchKey = (int)$taskLevel - 1;
			if ($matchKey == $key && isset($task['id'])) {
				$level = $task['id'];
				break;
			}
		}
	}

	return $level;
}
