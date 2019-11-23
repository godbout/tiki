<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
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
class ManagerTest extends \PHPUnit_Framework_TestCase
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
	 * Test if two active schedulers scheduled to run at same same, run.
	 */
	public function testSchedulersRunAtSameRunTime()
	{

		$logger = new Tiki_Log('UnitTests', \Psr\Log\LogLevel::ERROR);
		$scheduler1 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'* * * * *',
			'active',
			0,
			0,
			$logger
		);
		$scheduler1->creation_date = time() - 60;

		$scheduler2 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'*/1 * * * *',
			'active',
			0,
			0,
			$logger
		);
		$scheduler2->creation_date = time() - 60;

		$scheduler1->save();
		$scheduler2->save();

		self::$items[] = $scheduler1->id;
		self::$items[] = $scheduler2->id;

		$manager = new \Scheduler_Manager($logger);
		$manager->run();

		$this->assertNotEmpty($scheduler1->getLastRun());
		$this->assertNotEmpty($scheduler2->getLastRun());
	}
}
