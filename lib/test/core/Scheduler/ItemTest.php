<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\Scheduler;

use Scheduler_Item;
use Tiki_Log;
use TikiLib;

/**
 * Class ItemTest
 */
class ItemTest extends \PHPUnit_Framework_TestCase
{

	protected static $items = [];

	public static function tearDownAfterClass()
	{
		$schedlib = TikiLib::lib('scheduler');

		foreach (self::$items as $itemId) {
			$schedlib->remove_scheduler($itemId);
		}
	}

	/**
	 * @covers Scheduler_Item::isStalled()
	 */
	public function testIsStalled()
	{
		global $prefs;

		$logger = new Tiki_Log('UnitTests', \Psr\Log\LogLevel::ERROR);
		$scheduler = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"index:rebuild"}',
			'*/10 * * * *',
			'active',
			0,
			$logger
		);

		$scheduler->save();

		self::$items[] = $scheduler->id;

		$schedlib = TikiLib::lib('scheduler');

		// Test just start running scheduler
		$schedlib->start_scheduler_run($scheduler->id);
		$this->assertFalse($scheduler->isStalled(false));

		// Test over threshold running scheduler
		$threshold = $prefs['scheduler_stalled_timeout'] = 15;
		$startTime = strtotime(sprintf('-%d min', $threshold));

		$schedlib->start_scheduler_run($scheduler->id, $startTime);

		$this->assertNotFalse($scheduler->isStalled(false));

		$lastRun = $scheduler->getLastRun();
		$this->assertEquals('running', $lastRun['status']);
		$this->assertEmpty($lastRun['end_time']);
		$this->assertTrue((bool) $lastRun['stalled']);

		// Test running scheduler with disabled 'stalled'
		$prefs['scheduler_stalled_timeout'] = 0;
		$startTime = strtotime(sprintf('-%d min', $threshold));

		$schedlib->start_scheduler_run($scheduler->id, $startTime);
		$this->assertFalse($scheduler->isStalled(false));

		$lastRun = $scheduler->getLastRun();
		$this->assertEquals('running', $lastRun['status']);
		$this->assertEmpty($lastRun['end_time']);
		$this->assertFalse((bool) $lastRun['stalled']);
	}
}
