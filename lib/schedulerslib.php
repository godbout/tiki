<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

class SchedulersLib extends TikiLib
{

	/**
	 * Let a list of schedulers
	 *
	 * @param int 		$schedulerId	The Scheduler Id
	 * @param string 	$status			The Scheduler current status
	 *
	 * @return array	An array of schedulers or the scheduler details if $schedulerId is not null
	 */
	public function get_scheduler($schedulerId = null, $status = null)
	{

		$schedulersTable = $this->table('tiki_scheduler');

		$conditions = [];

		if ($status) {
			$conditions['status'] = $status;
		}

		if ($schedulerId) {
			$conditions['id'] = $schedulerId;
			return $schedulersTable->fetchRow([], $conditions);
		}

		return $schedulersTable->fetchAll([], $conditions);
	}

	/**
	 * Save scheduler details
	 *
	 * @param string 		$name			The scheduler name
	 * @param string|null 	$description 	The scheduler description text
	 * @param string 		$task			The scheduler task (check Scheduler_Item::$availableTasks)
	 * @param string|null 	$params 		The task parameters
	 * @param string 		$run_time		The cron run time
	 * @param string 		$status 		The scheduler status (active, inactive)
	 * @param int 			$re_run 		0 or 1 to run case failed
	 * @param int|null 		$scheduler_id 	The scheduler id (optional)
	 *
	 * @return int	The scheduler id
	 */
	public function set_scheduler($name, $description, $task, $params, $run_time, $status, $re_run, $scheduler_id = null)
	{

		$values = [
			'name' => $name,
			'description' => $description,
			'task' => $task,
			'params' => $params,
			'run_time' => $run_time,
			'status' => $status,
			're_run' => $re_run,
		];

		$schedulersTable = $this->table('tiki_scheduler');

		if (! $scheduler_id) {
			return $schedulersTable->insert($values);
		} else {
			$schedulersTable->update($values, ['id' => $scheduler_id]);
			return $scheduler_id;
		}
	}

	/**
	 * Get the info of the last scheduler runs
	 *
	 * @param int $scheduler_id The scheduler id
	 * @param int $limit 		The number of runs to return
	 *
	 * @return array An array with the scheduler runs found
	 */
	public function get_scheduler_runs($scheduler_id, $limit = 10)
	{
		if (! is_numeric($limit)) {
			$limit = -1;
		}

		$schedulersRunTable = $this->table('tiki_scheduler_run');

		return $schedulersRunTable->fetchAll([], ['scheduler_id' => $scheduler_id], $limit, -1, ['id' => 'DESC']);
	}

	/**
	 * Get scheduler last run status
	 *
	 * @param int $scheduler_id	The Scheduler Id
	 *
	 * @return bool|mixed
	 */
	public function get_run_status($scheduler_id)
	{
		$schedulersRunTable = $this->table('tiki_scheduler_run');
		return $schedulersRunTable->fetchOne('status', ['scheduler_id' => $scheduler_id], ['id' => 'DESC']);
	}

	/**
	 * Mark scheduler run as active (running)
	 *
	 * @param string 	$scheduler_id	The scheduler id
	 * @param int|null 	$start_time 	Run start time in timestamp format
	 *
	 * @return array	An array with the run id and start time
	 */
	public function start_scheduler_run($scheduler_id, $start_time = null)
	{

		if (empty($start_time)) {
			$start_time = time();
		}

		$schedulersRunTable = $this->table('tiki_scheduler_run');
		$runId = $schedulersRunTable->insert([
			'scheduler_id' => $scheduler_id,
			'start_time' => $start_time,
			'status' => 'running'
		]);

		return [
			'run_id' => $runId,
			'start_time' => $start_time
		];
	}

	/**
	 * Mark scheduler run as finished
	 *
	 * @param int		$scheduler_id		The scheduler id
	 * @param int		$run_id				The scheduler run id
	 * @param string	$executionStatus	The execution status (done, failed)
	 * @param string 	$errorMessage		The output message
	 * @param int|null	$end_time			The run end time in timestamp format
	 *
	 * @return int	The end time in timestamp format
	 */
	public function end_scheduler_run($scheduler_id, $run_id, $executionStatus, $errorMessage, $end_time = null)
	{

		if (empty($end_time)) {
			$end_time = time();
		}

		$schedulersRunTable = $this->table('tiki_scheduler_run');
		$schedulersRunTable->update([
			'status' => $executionStatus,
			'output' => $errorMessage,
			'end_time' => $end_time
		], [
			'scheduler_id' => $scheduler_id,
			'status' => 'running',
			'id' => $run_id,
		]);

		return $end_time;
	}

	/**
	 * Set a scheduler run as stalled
	 *
	 * @param int	$schedulerId 	The Scheduler Id
	 * @param int	$runId		The run id that is stalled
	 */
	public function setSchedulerRunStalled($schedulerId, $runId)
	{
		$schedulersRunTable = $this->table('tiki_scheduler_run');
		$schedulersRunTable->update([
			'stalled' => 1,
		], [
			'scheduler_id' => $schedulerId,
			'id' => $runId,
			'status' => 'running',
		]);
	}

	/**
	 * Remove the scheduler and its runs/logs
	 *
	 * @param int	$scheduler_id	The Scheduler Id
	 */
	public function remove_scheduler($scheduler_id)
	{

		$schedulersRunTable = $this->table('tiki_scheduler_run');
		$schedulersRunTable->delete(['scheduler_id' => $scheduler_id]);

		$schedulersTable = $this->table('tiki_scheduler');
		$schedulersTable->delete(['id' => $scheduler_id]);

		$logslib = TikiLib::lib('logs');
		$logslib->add_action('Removed', $scheduler_id, 'scheduler');
	}
}
