<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\core\Scheduler\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class SchedulerRunOutput extends BufferedOutput
{
	private $itemId;

	/**
	 * @var false|resource
	 */
	private $stream;

	/**
	 * SchedulerRunOutput constructor.
	 *
	 * @param                               $itemId
	 * @param int                           $verbosity
	 * @param bool                          $decorated
	 * @param OutputFormatterInterface|null $formatter
	 */
	public function __construct($itemId, $verbosity = self::VERBOSITY_NORMAL, $decorated = false, OutputFormatterInterface $formatter = null)
	{
		parent::__construct($verbosity, $decorated, $formatter);
		$this->itemId = $itemId;
		$this->stream = $this->createStream();
	}

	public static function getFileName($itemId)
	{
		return 'scheduler' . md5(sprintf('schedule_item_%s.log', $itemId));
	}

	public static function getTempLog($itemId)
	{
		return @file_get_contents(self::getFilePath($itemId));
	}

	public function clear()
	{
		@unlink(self::getFilePath($this->itemId));
	}

	protected function doWrite($message, $newline)
	{
		parent::doWrite($message, $newline);

		if ($newline) {
			$message .= PHP_EOL;
		}

		@fwrite($this->stream, $message);

		fflush($this->stream);
	}

	private static function getFilePath($itemId)
	{
		return 'temp/cache/' . self::getFileName($itemId);
	}

	private function createStream()
	{
		return fopen($this->getFilePath($this->itemId), 'a', false);
	}

	/**
	 * Gets the stream attached to this StreamOutput instance.
	 *
	 * @return resource A stream resource
	 */
	public function getStream()
	{
		return $this->stream;
	}
}
