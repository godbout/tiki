<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Scheduler_Item;
use Scheduler_Manager;
use Tiki_Log;
use TikiLib;
use UsersLib;

/**
 * Class ItemTest
 */
class ManagerTest extends TestCase
{

	const USER = 'membershiptest_a';
	protected static $items = [];

	public static function tearDownAfterClass() : void
	{
		$schedlib = TikiLib::lib('scheduler');

		foreach (self::$items as $itemId) {
			$schedlib->remove_scheduler($itemId);
		}
		$userlib = new UsersLib();
		$userlib->remove_user(self::USER);
	}

	/**
	 * Test if two active schedulers scheduled to run at same same, run.
	 */
	public function testSchedulersRunAtSameRunTime()
	{

		$logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
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
			null,
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
			null,
			$logger
		);
		$scheduler2->creation_date = time() - 60;

		$scheduler1->save();
		$scheduler2->save();

		self::$items[] = $scheduler1->id;
		self::$items[] = $scheduler2->id;

		$manager = new Scheduler_Manager($logger);
		$manager->run();

		$this->assertNotEmpty($scheduler1->getLastRun());
		$this->assertNotEmpty($scheduler2->getLastRun());
	}


	/**
	 * Test if two active schedulers scheduled to run at same same, run.
	 */
	public function testSchedulersRunNow()
	{
		$userlib = new UsersLib();
		$userlib->add_user(self::USER, 'abc', 'a@example.com');

		$logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
		$scheduler1 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'* * * * *',
			Scheduler_Item::STATUS_ACTIVE,
			0,
			0,
			self::USER,
			$logger
		);

		$scheduler2 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'* * * * *',
			Scheduler_Item::STATUS_INACTIVE,
			0,
			0,
			self::USER,
			$logger
		);

		$scheduler3 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'* * * * *',
			Scheduler_Item::STATUS_INACTIVE,
			0,
			0,
			null,
			$logger
		);
		$scheduler3->creation_date = time() - 60;


		$scheduler4 = new Scheduler_Item(
			null,
			'Test Scheduler',
			'Test Scheduler',
			'ConsoleCommandTask',
			'{"console_command":"list"}',
			'* * * * *',
			'active',
			0,
			0,
			null,
			$logger
		);
		$scheduler4->creation_date = time() - 60;


		$scheduler1->save();
		$scheduler2->save();
		$scheduler3->save();
		$scheduler4->save();

		self::$items[] = $scheduler1->id;
		self::$items[] = $scheduler2->id;
		self::$items[] = $scheduler3->id;
		self::$items[] = $scheduler4->id;

		$manager = new Scheduler_Manager($logger);
		$manager->run();
		$lastRun = $scheduler1->getLastRun();
		$this->assertNotEmpty($lastRun);
		$this->assertStringContainsString('Run triggered by ', $lastRun['output']);

		$lastRun2 = $scheduler2->getLastRun();
		$this->assertNotEmpty($lastRun2);
		$this->assertStringContainsString('Run triggered by ' . self::USER, $lastRun['output']);

		$this->assertEmpty($scheduler3->getLastRun());
		$this->assertNotEmpty($scheduler4->getLastRun());

		$manager = new Scheduler_Manager($logger);
		$manager->run();
		$this->assertEquals($lastRun2['id'], $scheduler2->getLastRun()['id']);
	}
}
