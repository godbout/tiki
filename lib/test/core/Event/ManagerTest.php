<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Event_ManagerTest extends PHPUnit\Framework\TestCase
{
	private $called;

	protected function setUp() : void
	{
		$this->called = 0;
	}

	public function testTriggerUnknown()
	{
		$manager = new Tiki_Event_Manager;
		$manager->trigger('tiki.wiki.update');

		$this->assertEquals(0, $this->called);
	}

	public function testBindAndTrigger()
	{
		$manager = new Tiki_Event_Manager;
		$manager->bind('tiki.wiki.update', [$this, 'callbackAdd']);

		$manager->trigger('tiki.wiki.update');

		$this->assertEquals(1, $this->called);
	}

	public function testChaining()
	{
		$manager = new Tiki_Event_Manager;

		$manager->bind('tiki.wiki.update', 'tiki.wiki.save');
		$manager->bind('tiki.wiki.save', 'tiki.save');

		$manager->bind('tiki.save', [$this, 'callbackAdd']);
		$manager->bind('tiki.wiki.save', [$this, 'callbackMultiply']);
		$manager->bind('tiki.wiki.update', [$this, 'callbackMultiply']);

		$manager->trigger('tiki.wiki.update');

		$this->assertEquals(4, $this->called);
	}

	public function testProvideBindingArguments()
	{
		$manager = new Tiki_Event_Manager;
		$manager->bind(
			'tiki.wiki.update',
			[$this, 'callbackAdd'],
			['amount' => 4,]
		);

		$manager->bind(
			'tiki.wiki.update',
			[$this, 'callbackAdd'],
			['amount' => 5,]
		);

		$manager->trigger('tiki.wiki.update');

		$this->assertEquals(9, $this->called);
	}

	public function testCalltimeArgumentsOverrideBinding()
	{
		$manager = new Tiki_Event_Manager;

		$manager->bind('tiki.wiki.update', 'tiki.wiki.save');

		$manager->bind('tiki.save', [$this, 'callbackAdd']);
		$manager->bind('tiki.wiki.save', [$this, 'callbackAdd'], ['amount' => 3]);
		$manager->bind('tiki.wiki.update', [$this, 'callbackMultiply']);

		$manager->trigger('tiki.wiki.update', ['amount' => 4]);

		$this->assertEquals(16, $this->called);
	}

	public function testGenerateInheritenceGraph()
	{
		$manager = new Tiki_Event_Manager;

		$manager->bind('tiki.wiki.update', 'tiki.wiki.save');
		$manager->bind('tiki.wiki.save', 'tiki.save');
		$manager->bind('tiki.file.save', 'tiki.save');

		$manager->bind('tiki.wiki.save', [$this, 'callbackMultiply']);
		$manager->bind('tiki.wiki.update', [$this, 'callbackMultiply']);
		$manager->bind('tiki.pageload', [$this, 'callbackMultiply']);

		$this->assertEquals(
			[
				'nodes' => [
					'tiki.wiki.update',
					'tiki.wiki.save',
					'tiki.file.save',
					'tiki.pageload',
					'tiki.save',
				],
				'edges' => [
					['from' => 'tiki.wiki.update', 'to' => 'tiki.wiki.save'],
					['from' => 'tiki.wiki.save', 'to' => 'tiki.save'],
					['from' => 'tiki.file.save', 'to' => 'tiki.save'],
				],
			],
			$manager->getEventGraph()
		);
	}

	public function testBindWithPriority()
	{
		$manager = new Tiki_Event_Manager;

		$manager->bind('tiki.wiki.update', 'tiki.wiki.save');
		$manager->bind('tiki.wiki.save', 'tiki.save');

		$manager->bindPriority(10, 'tiki.save', [$this, 'callbackAdd']);
		$manager->bind('tiki.wiki.save', [$this, 'callbackMultiply']);
		$manager->bind('tiki.wiki.update', [$this, 'callbackMultiply']);

		$manager->trigger('tiki.wiki.update');

		$this->assertEquals(1, $this->called);
	}

	public function testIndependentTriggers()
	{
		$manager = new Tiki_Event_Manager;

		$manager->bind('tiki.wiki.update', 'tiki.wiki.save');
		$manager->bind('tiki.wiki.save', 'tiki.save');

		$manager->bindPriority(10, 'tiki.save', [$this, 'callbackAdd']);
		$manager->bind('tiki.wiki.save', [$this, 'callbackMultiply']);
		$manager->bind('tiki.wiki.update', [$this, 'callbackMultiply']);

		$manager->bindPriority(
			5,
			'tiki.test.foo',
			function () use ($manager) {
				$manager->trigger('tiki.wiki.update');
			}
		);

		$manager->trigger('tiki.test.foo');

		$this->assertEquals(1, $this->called);
	}

	public function callbackAdd($arguments)
	{
		$this->called += $arguments['amount'] ?? 1;
	}

	public function callbackMultiply($arguments)
	{
		$this->called *= $arguments['amount'] ?? 2;
	}
}
