<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObjectsNotifyMaintainersCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('objects:notify-maintainers')
			->setDescription('Send out email notification to maintainers for objects whose freshness is greater than the limit')
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $prefs;

		if ($prefs['object_maintainers_enable'] !== 'y') {
			$output->writeln('<error>Error: preference "Object maintainers and freshness" not enabled (object_maintainers_enable).</error>');
			return;
		}

		$objectlib = \TikiLib::lib('object');
		$userlib = \TikiLib::lib('user');
		require_once('lib/mail/maillib.php');

		$allowed_types = ['wiki page'];

		//TODO: $logslib = TikiLib::lib('logs');
		tiki_mail_setup();
		$output->writeln('Notify object maintainers starting...');

		// get all objects that have maintainers (actually: objects and corresponding maintainers)
		$objects = $objectlib -> get_maintainers();
		foreach ($objects as $object) {
			$object_type = $object['source_type'];
			if (in_array($object_type, $allowed_types)) {
				$object_id = $object['source_itemId'];
				$maintainer = $object['target_itemId']; // i.e., user

				$freshness = $objectlib->get_freshness($object_id, $object_type);

				$object_update_frequency = \TikiLib::lib('attribute')->get_attribute($object_type, $object_id, 'tiki.object.update_frequency');

				// send email
				if ($freshness > $object_update_frequency) {
					#$output->writeln('Sending message to maintainers of ' . $object_id . '...');

					// get this object's maintainer's email
					$maintainer_info = $userlib->get_user_info($maintainer);

					require_once 'lib/mail/maillib.php';

					try {
						$mail = tiki_get_admin_mail();

						// TODO: SOME ERROR MESSAGES IF SENDER_EMAIL AND SENDER_NAME NOT DEFINED
						$mail->setReplyTo($prefs['sender_email'], $prefs['sender_name']);
						$mail->setFrom($prefs['sender_email'], $prefs['sender_name']);
						$mail->setSender($prefs['sender_email'], $prefs['sender_name']);

						if (empty($maintainer_info['email'])) {
							$output->writeln('Object "' . $object_id . '": Email not sent to maintainer ' . $maintainer .': user email is empty');
							continue;
						}

						$mail->addTo($maintainer_info['email'], $maintainer);

						$content = 'Hello ' . $maintainer . ',<BR><BR>';
						$content .= 'You are the maintainer of <strong>' . $object_id . '</strong> ' . $object_type . '.<BR><BR>';
						$content .= 'It has been ' . $freshness . ' days since its last update, however it needs to be updated every ' . $object_update_frequency . ' days.<BR><BR>';
						$content .= 'Please visit <strong> ' . $object_id . '</strong>, review and update it.<BR><BR>';
						$content .= $prefs['email_footer'];

						$mail->setSubject("Freshness notification");

						$bodyPart = new \Zend\Mime\Message();
						$bodyMessage = new \Zend\Mime\Part($content);
						$bodyMessage->type = \Zend\Mime\Mime::TYPE_HTML;
						if ($prefs['default_mail_charset']) {
							$bodyMessage->setCharset($prefs['default_mail_charset']);
						}

						$messageParts = [
							$bodyMessage
						];

						$bodyPart->setParts($messageParts);
						$mail->setBody($bodyPart);

						tiki_send_email($mail);

						// TODO:
/*						if ($prefs['log_mail'] == 'y') {
							$logslib = TikiLib::lib('logs');
							$logslib->add_log('mail', tr('EmailAction - send to %0, subject - %1', $email, $subject));
						}*/

						$output->writeln('Object "' . $object_id . '": Email sent to maintainer ' . $maintainer);

					} catch (Exception $e) {
						// TODO:
/*						if ($prefs['log_mail'] == 'y') {
							$logslib = TikiLib::lib('logs');
							$logslib->add_log('mail error', tr("EmailAction - can't send message"));
						}*/

						throw new Search_Action_Exception(tr('Error sending email to ' . $maintainer_info['email'] . ': %0', $e->getMessage()));
						// AND/OR $output->writeln('Email not sent to ' . $maintainer);

					}
				}
			}
		}
	}
}
