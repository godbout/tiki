<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Psr\Log\LoggerInterface;

class Scheduler_Item
{

	public $id;
	public $name;
	public $description;
	public $task;
	public $params;
	public $run_time;
	public $status;
	public $re_run;
	private $logger;

	const STATUS_ACTIVE = 'active';
	const STATUS_INACTIVE = 'inactive';

	public static $availableTasks = [
		'ConsoleCommandTask' => 'ConsoleCommand',
		'ShellCommandTask' => 'ShellCommand',
		'HTTPGetCommandTask' => 'HTTPGetCommand',
		'TikiCheckerCommandTask' => 'TikiCheckerCommand',
	];

	public function __construct($id, $name, $description, $task, $params, $run_time, $status, $re_run, LoggerInterface $logger)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->task = $task;
		$this->params = $params;
		$this->run_time = $run_time;
		$this->status = $status;
		$this->re_run = $re_run;
		$this->logger = $logger;
	}

	public static function getAvailableTasks()
	{
		return self::$availableTasks;
	}

	/**
	 * Save Scheduler
	 */
	public function save()
	{
		$schedLib = TikiLib::lib('scheduler');
		$id = $schedLib->set_scheduler($this->name, $this->description, $this->task, $this->params, $this->run_time, $this->status, $this->re_run, $this->id);

		if ($id) {
			$this->id = $id;
		}
	}

	/**
	 * check if the scheduler is running
	 *
	 * @return int return scheduler id if it is matched and return -1 otherwise
	 */
	public function isRunning()
	{
		$lastRun = $this->getLastRun();
		return ! empty($lastRun) ? $lastRun['status'] == 'running' : false;
	}

	/**
	 * Check if scheduler is stalled (running for long time)
	 *
	 * @param bool	$notify	Send tiki admins an email notification if scheduler is marked as stalled, if null uses tiki stored preferences
	 *
	 * @return int|bool	The scheduler run id if is stalled, false otherwise
	 */
	public function isStalled($notify = null)
	{
		global $tikilib;

		$threshold = $tikilib->get_preference('scheduler_stalled_timeout', 15);

		if ($threshold === 0) {
			return false;
		}

		$lastRun = $this->getLastRun();

		if (empty($lastRun) || $lastRun['status'] != 'running') {
			return false;
		}

		if ($lastRun['stalled']) {
			return true;
		}

		$startTime = $lastRun['start_time'];
		$now = time();

		if ($now < ($startTime + $threshold * 60)) {
			return false;
		}

		$this->setStalled($lastRun['id'], $notify);

		return $lastRun['id'];
	}

	/**
	 * Sets last run as stalled
	 *
	 * @param int	$runId	The run id to mark as stalled
	 * @param bool 	$notify	Send tiki admins an email notification, if null uses tiki stored preferences
	 */
	protected function setStalled($runId, $notify = null)
	{
		global $tikilib;

		$schedLib = TikiLib::lib('scheduler');
		$schedLib->setSchedulerRunStalled($this->id, $runId);

		if (is_null($notify)) {
			$notify = $tikilib->get_preference('scheduler_notify_on_stalled', 'y') === 'y';
		}

		if ($notify) {
			Tiki\Notifications\Email::sendSchedulerNotification('scheduler_stalled_notification_subject.tpl', 'scheduler_stalled_notification.tpl', $this);
		}
	}

	/**
	 * Reset a stalled run in order to allow run scheduler again
	 *
	 * @param string $message	The message to include in the run output
	 *
	 * @return bool	True if stalled run was reset, false if otherwise.
	 */
	public function resetRun($message)
	{
		if (! $runId = $this->isStalled()) {
			return false;
		}

		$schedLib = TikiLib::lib('scheduler');
		$schedLib->end_scheduler_run($this->id, $runId, 'failed', $message);

		return true;
	}

	/**
	 * @return array
	 */
	public function execute()
	{
		global $prefs;

		$schedlib = TikiLib::lib('scheduler');
		$status = $schedlib->get_run_status($this->id);

		$this->logger->info('Scheduler last run status: ' . $status);

		if ($status == 'running') {
			if ($this->isStalled()) {
				return [
					'status' => 'failed',
					'message' => tr('Scheduler task is stalled.')
				];
			}

			return [
				'status' => 'failed',
				'message' => tr('Scheduler task is already running.')
			];
		}

		$this->logger->info('Task: ' . $this->task);

		$class = 'Scheduler_Task_' . $this->task;
		if (! class_exists($class)) {
			return [
				'status' => 'failed',
				'message' => $class . ' not found.',
			];
		}

		list('run_id' => $runId, 'start_time' => $startTime) = $schedlib->start_scheduler_run($this->id);
		$this->logger->debug("Start time: " . $startTime);

		$params = json_decode($this->params, true);
		$this->logger->debug("Task params: " . $this->params);

		if ($params === null && ! empty($this->params)) {
			return [
				'status' => 'failed',
				'message' => tr('Unable to decode task params.')
			];
		}

		$task = new $class($this->logger);
		$result = $task->execute($params);

		$executionStatus = $result ? 'done' : 'failed';
		$outputMessage = $task->getOutput();

		$endTime = $schedlib->end_scheduler_run($this->id, $runId, $executionStatus, $outputMessage);
		$this->logger->debug("End time: " . $endTime);

		return [
			'status' => $executionStatus,
			'message' => $outputMessage,
		];
	}

	/**
	 * Return scheduler last run
	 *
	 * @return array|null An array with last run details or null if not found
	 */
	public function getLastRun()
	{
		$schedlib = TikiLib::lib('scheduler');
		$runs = $schedlib->get_scheduler_runs($this->id, 1);

		if (empty($runs)) {
			return null;
		}

		return $runs[0];
	}

	/**
	 * Transforms an array (from schedulers lib) to a Scheduler_Item object
	 *
	 * @param array 			$scheduler	The scheduler details
	 * @param LoggerInterface	$logger		Logger
	 *
	 * @return Scheduler_Item
	 */
	public static function fromArray(array $scheduler, $logger)
	{
		return new self(
			$scheduler['id'],
			$scheduler['name'],
			$scheduler['description'],
			$scheduler['task'],
			$scheduler['params'],
			$scheduler['run_time'],
			$scheduler['status'],
			$scheduler['re_run'],
			$logger
		);
	}
}
