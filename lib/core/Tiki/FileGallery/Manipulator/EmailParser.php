<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Manipulator;

use TikiLib;

class EmailParser extends Manipulator
{
	public function run($args = [])
	{
		global $prefs, $user;

		$file = $this->file;
		if ($file->filetype != 'message/rfc822') {
			return false;
		}

		$message_content = $file->getContents();
		try {
			$message = \Laminas\Mail\Message::fromString($message_content);
		} catch (\Exception\RuntimeException $e) {
			Feedback::error(tr('Failed parsing file %0 as an email.', $file->fileId) . '<br />' . $e->getMessage());
			return false;
		}
		$headers = $message->getHeaders();

		$result = [
			'subject' => $message->getSubject(),
			'body' => $message->getBodyText(),
			'from' => '',
			'sender' => '',
			'recipient' => '',
			'date' => '',
			'content_type' => '',
			'plaintext' => '',
			'html' => '',
		];

		$from = $headers->get('From');
		if ($from) {
			$result['from'] = $from->getFieldValue();
		}

		$sender = $headers->get('Sender');
		if ($sender) {
			$result['sender'] = $sender->getFieldValue();
		}

		$recipient = $headers->get('To');
		if ($recipient) {
			$result['recipient'] = $recipient->getFieldValue();
		}

		$content_type = $headers->get('Content-Type');
		$boundary = '';
		if ($content_type) {
			$result['content_type'] = $content_type->getType();
			$boundary = $content_type->getParameter('boundary');
		} else {
			$result['content_type'] = '';
		}

		$date = $headers->get('Date');
		if ($date) {
			$result['date'] = strtotime($date->getFieldValue());
		} else {
			$result['date'] = '';
		}

		if ($headers->has('mime-version') && $boundary) {
			try {
				$mime = \Laminas\Mime\Message::createFromMessage($message_content, $boundary);
				foreach ($mime->getParts() as $part) {
					$content_type = '';
					$headers = $part->getHeadersArray();
					foreach ($headers as $header) {
						if (strtolower($header[0]) == 'content-type') {
							$content_type = $header[1];
						}
					}
					if (stristr($content_type, 'text/plain')) {
						$result['plaintext'] = $part->getRawContent();
					}
					if (stristr($content_type, 'text/html')) {
						$result['html'] = $part->getRawContent();
					}
				}
			} catch (\Exception\RuntimeException $e) {
				Feedback::error(tr('Failed extracting text parts from file %0 as an email.', $file->fileId) . '<br />' . $e->getMessage());
			}
		}

		return $result;
	}
}
