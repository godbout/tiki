<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Scheduler_Task_TikiCheckerCommandTask extends Scheduler_Task_CommandTask
{
	/**
	 * Execute Scheduled task
	 *
	 * @param null $params
	 * @return bool
	 */
	public function execute($params = null)
	{
		try {
			$this->logger->debug(tra('Checking tiki version'));

			$tikilib = TikiLib::lib('tiki');
			global $TWV;

			$tikiVersion = $TWV->version;
			$versionUtils = new Tiki_Version_Utils();
			$needupdate = $versionUtils->checkUpdatesForVersion($tikiVersion);
			$lastRebuild = $tikilib->get_preference('notified_tiki_version');

			if (! empty($needupdate) && $lastRebuild != $tikiVersion) {
				$userlib = TikiLib::lib('user');
				$smarty = TikiLib::lib('smarty');

				$listgroups = $userlib->get_groups(0, -1, 'groupName_asc', '', '', 'n');

				$this->logger->debug(tra('Sending emails'));

				$recipients = [];
				if (! empty($listgroups)) {
					foreach ($listgroups['data'] as $group) {
						if ($userlib->group_has_permission($group['groupName'], 'tiki_p_admin')) {
							$listusers = $userlib->get_users(0, -1, 'login_asc', '', '', false, $group['groupName']);

							if (! empty($listusers)) {
								foreach ($listusers['data'] as $user) {
									if (! in_array($user['email'], $recipients)) {
										array_push($recipients, $user['email']);
										$subject = ! empty($tikilib->get_preference('browsertitle')) ? tra("Tiki Updates: ") . $tikilib->get_preference('browsertitle') : tra("Tiki Updates");

										// Send mail
										$mail = new TikiMail();
										$smarty->assign('upgrade_messages', $needupdate);
										$smarty->assign('subject', $subject);
										$defaultLanguage = $userlib->get_language($user['user']);
										$mail->setUser($user['email']);
										$mail_data = $smarty->fetchLang($defaultLanguage, "mail/admin_tiki_checker_subject.tpl");
										$mail->setSubject($mail_data);
										$mail_data = $smarty->fetchLang($defaultLanguage, "mail/admin_tiki_checker.tpl");
										$mail->setHtml($mail_data);

										if (! $mail->send([$user['email']])) {
											$msg = tra('Unable to send mail');
											$mailerrors = print_r($mail->errors, true);
											$msg .= '<br>' . $mailerrors;
											$this->logger->debug($msg);
										} else {
											$this->logger->debug(tra('Tiki version email sent to') . ' ' . $user['email']);
										}
									}
								}
							}
						}
					}
				}

				$tikilib->set_preference('notified_tiki_version', $tikiVersion);
			} else {
				$this->logger->debug(tra('Tiki Version Updated'));
			}

			return true;
		} catch (\Exception $e) {
			$this->errorMessage = $e->getMessage();
			return false;
		}
	}

	public function getParams()
	{
	}
}
