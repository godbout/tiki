<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function saveScheduler()
{
	$schedLib = TikiLib::lib('scheduler');

	$addTask = true;
	$errors = [];

	$name = $_POST['scheduler_name'];
	$description = $_POST['scheduler_description'];
	$task = $_POST['scheduler_task'];
	$runTime = trim($_POST['scheduler_time']);
	$status = $_POST['scheduler_status'];
	$reRun = $_POST['scheduler_rerun'] == 'on' ? 1 : 0;
	$runOnlyOnce = $_POST['scheduler_run_only_once'] == 'on' ? 1 : 0;

	if (empty($name)) {
		$errors[] = tra('Name is required');
		$addTask = false;
	}

	if (empty($task)) {
		$errors[] = tra('Task is required');
		$addTask = false;
	} else {
		$className = 'Scheduler_Task_' . $task;
		if (! class_exists($className)) {
			Feedback::error(tra('An error occurred; please contact the administrator.'));
			$access = TikiLib::lib('access');
			$access->redirect('tiki-admin_schedulers.php');
			die;
		}

		$logger = new Tiki_Log('Schedulers', \Psr\Log\LogLevel::ERROR);
		$class = new $className($logger);

		$taskName = strtolower($class->getTaskName());
		$taskParams = $class->getParams();

		foreach ($taskParams as $key => $param) {
			if (empty($param['required'])) {
				continue;
			}

			$httpParamName = $taskName . '_' . $key;
			if (empty($_POST[$httpParamName])) {
				$errors[] = sprintf(tra('%s is required'), $param['name']);
				$addTask = false;
			}
		}

		$params = $class->parseParams();
	}

	if (empty($runTime)) {
		$errors[] = tra('Run Time is required');
		$addTask = false;
	}

	if (! Scheduler_Utils::validate_cron_time_format($runTime)) {
		$errors[] = tra('Run Time format is invalid');
		$addTask = false;
	}

	if (empty('status')) {
		$errors[] = tra('Status cannot be empty');
		$addTask = false;
	}

	$schedulerinfo = [];

	if ($addTask) {
		$scheduler = ! empty($_POST['scheduler']) ? $_POST['scheduler'] : null;

		$schedLib->set_scheduler($name, $description, $task, $params, $runTime, $status, $reRun, $runOnlyOnce, $scheduler);
		if ($scheduler) {
			$feedback = sprintf(tra('Scheduler %s was updated.'), $name);
		} else {
			$feedback = sprintf(tra('Scheduler %s was created.'), $name);
		}

		Feedback::success($feedback);
		$access = TikiLib::lib('access');
		$access->redirect('tiki-admin_schedulers.php');
		die;
	} else {
		if (! empty($errors)) {
			Feedback::error(['mes' => $errors]);
		}
	}

	$schedulerinfo['name'] = $name;
	$schedulerinfo['description'] = $description;
	$schedulerinfo['task'] = $task;
	$schedulerinfo['run_time'] = $runTime;
	$schedulerinfo['status'] = $status;
	$schedulerinfo['re_run'] = $reRun;
	$schedulerinfo['params'] = json_decode($params, true);

	return $schedulerinfo;
}

require_once('tiki-setup.php');

$access = TikiLib::lib('access');
$access->check_feature('feature_scheduler');
$access->check_permission(['tiki_p_admin_schedulers']);

$auto_query_args = [];
$cookietab = 1;

$auto_query_args = [
	'offset',
	'numrows',
	'scheduler',
	'logs',
];

$schedLib = TikiLib::lib('scheduler');

if ((isset($_POST['new_scheduler']) || (isset($_POST['editscheduler']) && isset($_POST['scheduler']))) && $access->checkCsrf()) {
	// If scheduler saved, it redirects to the schedulers page, cleaning the add/edit scheduler form.
	$schedulerinfo = saveScheduler();
	$cookietab = 2;
} elseif (isset($_REQUEST['scheduler']) and $_REQUEST['scheduler']) {
	$schedulerinfo = $schedLib->get_scheduler($_REQUEST['scheduler']);

	if (empty($schedulerinfo)) {
		$schedulerinfo['name'] = '';
		$schedulerinfo['description'] = '';
		$schedulerinfo['task'] = '';
		$schedulerinfo['params'] = [];
		$schedulerinfo['run_time'] = '';
		$schedulerinfo['status'] = '';
		$schedulerinfo['re_run'] = '';

		$_REQUEST['scheduler'] = 0;
	} else {
		$schedulerinfo['params'] = json_decode($schedulerinfo['params'], true);

		if (empty($_REQUEST['offset'])) {
			$offset = 0;
		} else {
			$offset = $_REQUEST['offset'];
		}
		$smarty->assign_by_ref('offset', $offset);

		if (empty($_REQUEST['numrows'])) {
			$numRows = $maxRecords;
		} else {
			$numRows = $_REQUEST['numrows'];
		}
		$smarty->assign_by_ref('numrows', $numRows);

		$tikilib = TikiLib::lib('tiki');
		$numOfLogs = $tikilib->get_preference('scheduler_keep_logs');
		$schedulerRuns = $schedLib->get_scheduler_runs($_REQUEST['scheduler'], $numRows, $offset);
		$runsCount = $schedLib->countRuns($_REQUEST['scheduler']);

		if ($runsCount > $numOfLogs && $numOfLogs > 0) {
			$runsCount = $numOfLogs;
		}

		$smarty->assign_by_ref('cant', $runsCount);
		$smarty->assign_by_ref('numOfLogs', $numOfLogs);

		// Check if last run is still running and can be stopped.
		if (! empty($schedulerRuns[0]) &&
			$schedulerRuns[0]['status'] == 'running' &&
			$schedulerRuns[0]['start_time'] + 600 < time()) {
			// If the task is running for more than 10min, maybe it's stucked.
			$schedulerRuns[0]['can_stop'] = true;
		}
	}

	if (isset($_REQUEST['logs'])) {
		$cookietab = '3';
	} else {
		$cookietab = '2';
	}
} else {
	$schedulerinfo['name'] = '';
	$schedulerinfo['description'] = '';
	$schedulerinfo['task'] = '';
	$schedulerinfo['run_time'] = '';
	$schedulerinfo['status'] = '';
	$schedulerinfo['re_run'] = '';

	$_REQUEST['scheduler'] = 0;
}

$tasks = $schedLib->get_scheduler();

$logger = new Tiki_Log('Webcron', \Psr\Log\LogLevel::ERROR);
foreach ($tasks as $key => $task) {
	$schedulerItem = Scheduler_Item::fromArray($task, $logger);
	if (! $schedulerItem instanceof Scheduler_Item) {
		continue;
	}

	$tasks[$key]['stalled'] = $schedulerItem->isStalled();
}

$smarty->assign_by_ref('schedulers', $tasks);

if (isset($_REQUEST['add'])) {
	$cookietab = '2';
}

$headerlib->add_jsfile('lib/jquery_tiki/tiki-schedulers.js');
$smarty->assign('schedulerinfo', $schedulerinfo);
$smarty->assign('schedulerruns', isset($schedulerRuns) ? $schedulerRuns : []);
$smarty->assign('schedulerId', $_REQUEST['scheduler']);
$smarty->assign('schedulerTasks', Scheduler_Item::getAvailableTasks());
$smarty->assign('selectedTask', '');
$smarty->assign('schedulerStatus', [
	Scheduler_Item::STATUS_ACTIVE => tra('Active'),
	Scheduler_Item::STATUS_INACTIVE => tra('Inactive'),
]);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_schedulers.tpl');
$smarty->display('tiki.tpl');
