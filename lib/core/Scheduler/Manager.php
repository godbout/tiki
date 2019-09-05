<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Psr\Log\LoggerInterface;

class Scheduler_Manager
{

	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	public function run()
	{
		global $tikilib;

		// Get all active schedulers
		$schedLib = TikiLib::lib('scheduler');
		$activeSchedulers = $schedLib->get_scheduler(null, 'active');

		$this->logger->info(sprintf("Found %d active scheduler(s).", sizeof($activeSchedulers)));

		$runTasks = [];
		$reRunTasks = [];

		// Check for stalled tasks
		foreach ($activeSchedulers as $scheduler) {
			$schedulerTask = Scheduler_Item::fromArray($scheduler, $this->logger);
			if ($schedulerTask->isStalled()) {
				$this->logger->info(tr("Scheduler %0 (id: %1) is stalled", $schedulerTask->name, $schedulerTask->id));

				//Attempt to heal
				$notify = $tikilib->get_preference('scheduler_notify_on_healing', 'y');
				$schedulerTask->heal('Scheduler was healed by cron', $notify);
			}
		}

		foreach ($activeSchedulers as $scheduler) {
			try {
				$lastRun = $schedLib->get_scheduler_runs($scheduler["id"], 1);
				if (count($lastRun) == 1) {
					$lastRunDate = $lastRun[0]["end_time"];
				} else {
					$lastRunDate = (isset($scheduler["creation_date"]) ? $scheduler["creation_date"] : time());
				}

				$lastRunDate = (int)($lastRunDate - ($lastRunDate % 60));
				$lastShould = Scheduler_Utils::get_previous_run_date($scheduler['run_time']);

				if (isset($lastRunDate) && $lastShould >= $lastRunDate) {
					$runTasks[] = $scheduler;
					$this->logger->info(sprintf("Run scheduler %s", $scheduler['name']));
					continue;
				}
			} catch (\Scheduler\Exception\CrontimeFormatException $e) {
				$this->logger->error(sprintf(tra("Skip scheduler %s - %s"), $scheduler['name'], $e->getMessage()));
				continue;
			}

			// Check which tasks should run if they failed previously (last execution)
			if ($scheduler['re_run']) {
				$reRunTasks[] = $scheduler;
				continue;
			}

			$this->logger->info(sprintf("Skip scheduler %s - Not scheduled to run at this time", $scheduler['name']));
		}

		foreach ($reRunTasks as $task) {
			$status = $schedLib->get_run_status($task['id']);
			if ($status == 'failed') {
				$this->logger->info(sprintf("Re-run scheduler %s - Last run has failed", $scheduler['name']));
				$runTasks[] = $task;
			}
		}

		if (empty($runTasks)) {
			$this->logger->notice("No active schedulers were found to run at this time.");
		} else {
			//$output->writeln(sprintf("Total of %d schedulers to run.", sizeof($runTasks)), OutputInterface::VERBOSITY_VERY_VERBOSE);
		}

		foreach ($runTasks as $runTask) {
			$schedulerTask = Scheduler_Item::fromArray($runTask, $this->logger);

			$this->logger->notice(sprintf(tra('***** Running scheduler %s *****'), $schedulerTask->name));
			$result = $schedulerTask->execute();

			if ($result['status'] == 'failed') {
				$this->logger->error(sprintf(tra("***** Scheduler %s - FAILED *****\n%s"), $schedulerTask->name, $result['message']));
			} else {
				$this->logger->notice(sprintf(tra("***** Scheduler %s - OK *****"), $schedulerTask->name));
			}
		}
	}

	/**
	 * Heal a specific or all stalled schedulers
	 *
	 * @param $schedulerId
	 *   A specific scheduler id to heal
	 */
	public function heal($schedulerId = null)
	{
		$schedLib = TikiLib::lib('scheduler');
		$schedulers = $schedLib->get_scheduler($schedulerId, 'active');

		if (empty($schedulers) && $schedulerId) {
			$this->logger->error(tr("Scheduler with id %0 does not exist or is not active", $schedulerId));
			return;
		}

		if ($schedulerId != null) {
			$schedulers = [$schedulers];
		}

		foreach ($schedulers as $scheduler) {
			$item = Scheduler_Item::fromArray($scheduler, $this->logger);

			if ($item->isStalled()) {
				$this->logger->notice(tr("Scheduler `%0` (id: %1) is stalled", $item->name, $item->id));

				if ($item->heal('Scheduler healed through command', false, true)) {
					$this->logger->notice(tr("Scheduler `%0` (id: %1) was healed", $item->name, $item->id));
				} else {
					$this->logger->notice(tr("Scheduler `%0` (id: %1) was not healed", $item->name, $item->id));
				}
			} else {
				$this->logger->notice(tr("Scheduler %0 (id: %1) is not stalled, no need to heal", $item->name, $item->id));
			}
		}
	}
}
