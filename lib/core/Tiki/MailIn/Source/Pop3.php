<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Source;

use Tiki\MailIn\Exception\TransportException;
use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Storage\Part;
use Laminas\Mail\Storage\Pop3 as ZendPop3;
use Laminas\Mail\Exception\ExceptionInterface as ZendMailException;

class Pop3 implements SourceInterface
{
	protected $host;
	protected $port;
	protected $username;
	protected $password;

	function __construct($host, $port, $username, $password)
	{
		$this->host = $host;
		$this->port = (int) $port;
		$this->username = $username;
		$this->password = $password;
	}

	function test()
	{
		try {
			$pop = $this->connect();
			$pop->close();

			return true;
		} catch (TransportException $e) {
			return false;
		}
	}

	/**
	 * @return \Generator
	 * @throws TransportException
	 */
	function getMessages()
	{
		$pop = $this->connect();
		$toDelete = [];

		foreach ($pop as $i => $source) {
			/* @var $source \Laminas\Mail\Storage\Message */
			$message = new Message($i, function () use ($i, & $toDelete) {
				$toDelete[] = $i;
			});
			$from = $source->from ?: $source->{'return-path'};
			if (! empty($source->{'message-id'})) {
				$message->setMessageId(str_replace(['<', '>'], '', $source->{'message-id'}));
			}
			$message->setRawFrom($from);
			$message->setSubject($source->subject);
			$message->setRecipient($source->to);
			$message->setHtmlBody($this->getBody($source, 'text/html'));
			$message->setBody($this->getBody($source, 'text/plain'));
			$content = '';
			foreach ($source->getHeaders() as $header) {
				$content .= $header->toString()."\r\n";
			}
			$content .= "\r\n".$source->getContent();
			$message->setContent($content);

			$this->handleAttachments($message, $source);

			yield $message;
		}

		// Due to an issue in Zend_Mail_Storage, deletion must be done in reverse order
		$toDelete = array_reverse($toDelete);

		foreach ($toDelete as $i) {
			$pop->removeMessage($i);
		}

		$pop->close();
	}

	/**
	 * @return \Laminas\Mail\Storage\Pop3
	 * @throws TransportException
	 */
	protected function connect()
	{
		try {
			$pop = new ZendPop3([
				'host' => $this->host,
				'port' => $this->port,
				'user' => $this->username,
				'password' => $this->password,
				'ssl' => $this->port == 995,
			]);

			return $pop;
		} catch (ZendMailException $e) {
			throw new TransportException(tr("Login failed for POP3 account on %0:%1 for user %2", $this->host, $this->password, $this->username));
		}
	}

	/**
	 * @param Part $part
	 * @param string $type
	 * @param string $return
	 *
	 * @return string
	 */
	private function getBody($part, $type, $return = '')
	{
		/** @var ContentType $contentType */
		$contentType = $part->getHeaders()->get('Content-Type');
		if (! $part->isMultipart() && (! $contentType || $contentType->getType() === $type)) {
			$return .= $this->decode($part);
		}

		if ($part->isMultipart()) {
			for ($i = 1; $i <= $part->countParts(); ++$i) {
				$p = $part->getPart($i);
				$ret = $this->getBody($p, $type, $return);

				$pType = $p->getHeaders()->get('Content-Type');
				if ($contentType->getType() === 'multipart/mixed' && $type === 'text/html' && $pType && $pType->getType() === $type) {
					// mainly to remove the html, head and body tags
					$return .= strip_tags($ret, '<b><br><dd><div><dl><dt><em><h1><h2><h3><h4><h5><h6><hr><i><img><li><ol><p><s><span><strong><table><tr><td><u><ul>');
					// TODO work out how to insert inline file's id when using multipart/alternative
				} else {
					if ($ret) {
						return $ret;
					}
				}
			}
		}
		return $return;
	}

	/**
	 * @param $message Message
	 * @param $part Part
	 */
	private function handleAttachments($message, $part)
	{
		if ($part->isMultipart()) {
			// check each part
			for ($i = 1; $i <= $part->countParts(); ++$i) {
				$p = $part->getPart($i);
				if ($p->isMultipart()) {
					$this->handleAttachments($message, $p);
				}
				$headers = $p->getHeaders()->toArray();

				// filter out any non-binary parts
				if (! isset($headers['Content-Transfer-Encoding']) || $headers['Content-Transfer-Encoding'] !== 'base64') {
					continue;
				}

				if (isset($headers['Content-Id'])) {
					$contentId = $headers['Content-Id'];
					$contentId = trim($contentId, '<>');
				} elseif (isset($headers['x-attachment-id'])) {
					$contentId = $headers['x-attachment-id'];
				} else {
					$contentId = uniqid();
				}
				$fileName = '';
				$fileType = '';
				$fileData = $this->decode($p);
				$fileSize = mb_strlen($fileData, '8bit');

				if (isset($headers['Content-Type'])) {
					$type = $headers['Content-Type'];
					$pos = strpos($type, ';');
					if ($pos === false) {
						$fileType = $type;
					} else {
						$fileType = substr($type, 0, $pos);
					}

					if (preg_match('/name="([^"]+)"/', $type, $parts)) {
						$fileName = $parts[1];
					}
				}

				if (! $fileName && isset($headers['Content-Disposition'])) {
					$dispo = $headers['Content-Disposition'];
					if (preg_match('/name="([^"]+)"/', $dispo, $parts)) {
						$fileName = $parts[1];
					}
				}

				$message->addAttachment($contentId, $fileName, $fileType, $fileSize, $fileData);
			}
		}
	}

	/**
	 * @param $part Part
	 * @return string
	 */
	private function decode($part)
	{
		$content = $part->getContent();
		if ($part->getHeaders()->get('Content-Transfer-Encoding')) {
			switch ($part->getHeader('Content-Transfer-Encoding')->getFieldValue()) {
				case 'base64':
					$content = base64_decode($content);
					break;
				case 'quoted-printable':
					$content = quoted_printable_decode($content);
					break;
			}
		}

		if ($part->getHeaders()->get('Content-Type')) {
			if (preg_match('/charset="?iso-8859-1"?/i', $part->getHeader('Content-Type')->getFieldValue())) {
				$content = utf8_encode($content); //convert to utf8
			}
		}

		return $content;
	}
}
