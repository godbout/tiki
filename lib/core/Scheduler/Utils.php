<?php

class Scheduler_Utils
{

	/**
	 * Checks if a cron should run at a time.
	 *
	 * @param string|\DateTime $time Relative calculation date
	 * @param $cron string A cron time expression (ex.: 0 0 * * *)
	 * @return bool true if should run, false otherwise.
	 * @throws \Scheduler\Exception\CrontimeFormatException
	 */
	public static function is_time_cron($time, $cron)
	{
		if (! self::validate_cron_time_format($cron)) {
			throw new Scheduler\Exception\CrontimeFormatException(tra('Invalid cron time format'));
		}

		$cronEx = Cron\CronExpression::factory($cron);
		return $cronEx->isDue($time);
	}

	/**
	 * Validate a cron time string
	 *
	 * @param $cron string A cron time expression (ex.: 0 0 * * *)
	 * @return bool true if valid, false otherwise
	 */
	public static function validate_cron_time_format($cron)
	{
		return Cron\CronExpression::isValidExpression($cron);
	}

	/**
	 * Parse users/emails to send notifications
	 *
	 * @param string $prefName The name of the preference that contains the list of users/emails to parse
	 *
	 * @return array An array with valid users/emails to notify
	 * @throws Exception
	 */
	public static function getSchedulerNotificationUsers($prefName)
	{

		global $tikilib;

		$notificationUsers = $tikilib->get_preference($prefName);

		$usersLib = TikiLib::lib('user');
		$logsLib = TikiLib::lib('logs');

		$users = [];
		$invalid = [];

		if (empty($notificationUsers)) {
			return $usersLib->get_group_users('Admins', 0, -1, '*');
		}

		$parts = explode(',', $notificationUsers);

		foreach ($parts as $target) {
			$target = trim($target);

			if ($usersLib->user_exists($target)) {
				$user = $usersLib->get_user_info($target);
				$users[] = $user;
				continue;
			}

			if ($usersLib->user_exists_by_email($target)) {
				$userLogin = $usersLib->get_user_by_email($target);
				$user = $usersLib->get_user_info($userLogin);
				$users[] = $user;
				continue;
			}

			if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
				$users[] = [
					'email' => $target
				];
				continue;
			};

			$invalid[] = $target;
		}

		if (! empty($invalid)) {
			$error_message = tr("Found invalid user(s)/email(s) to send notification on preference %0. Invalid users/emails: %1", $prefName, implode(', ', $invalid));
			$logsLib->add_log('Scheduler error', $error_message);
		}

		return $users;
	}


	/**
	 * Get previous run date.
	 *
	 * @param $cron string A cron time expression (ex.: 0 0 * * *)
	 * @return number timestamp in seconds.
	 * @throws \Scheduler\Exception\CrontimeFormatException
	 */
	public static function get_previous_run_date($cron)
	{
		if (! self::validate_cron_time_format($cron)) {
			throw new Scheduler\Exception\CrontimeFormatException(tra('Invalid cron time format'));
		}
		$cron = Cron\CronExpression::factory($cron);
		return $cron->getPreviousRunDate()->getTimestamp();
	}
}
