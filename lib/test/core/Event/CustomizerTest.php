<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Event_CustomizerTest extends PHPUnit\Framework\TestCase
{
	private $manager;
	private $runner;
	private $called;
	private $lastEvent;
	private $lastArguments;

	protected function setUp() : void
	{
		$this->called = 0;
		$manager = $this->manager = new Tiki_Event_Manager;
		$self = $this;
		$this->runner = new Math_Formula_Runner(
			[
				function ($verb) use ($manager, $self) {
					switch ($verb) {
						case 'event-trigger':
							return new Tiki_Event_Function_EventTrigger($manager);
						case 'event-record':
							return new Tiki_Event_Function_EventRecord($self);
					}
				},
				'Math_Formula_Function_' => '',
				'Tiki_Event_Function_' => '',
			]
		);
	}

	public function testBindThroughCustomizedEventFilter()
	{
		$this->manager->bind('custom.event', [$this, 'callbackAdd']);

		$customizer = new Tiki_Event_Customizer;
		$customizer->addRule('tiki.trackeritem.save', '(event-trigger custom.event)');
		$customizer->bind($this->manager, $this->runner);

		$this->manager->trigger('tiki.trackeritem.save');

		$this->assertEquals(1, $this->called);
	}

	public function testPassCustomArguments()
	{
		$this->manager->bind('custom.event', [$this, 'callbackAdd']);

		$customizer = new Tiki_Event_Customizer;
		$customizer->addRule(
			'tiki.trackeritem.save',
			'(event-trigger custom.event
			(map
				(amount (add args.a args.b))
				(test args.c)
			))'
		);
		$customizer->bind($this->manager, $this->runner);

		$this->manager->trigger(
			'tiki.trackeritem.save',
			[
				'a' => 2,
				'b' => 3,
				'c' => 4,
			]
		);

		$this->assertEquals(5, $this->called);
	}

	public function testDirectArgumentRecording()
	{
		$customizer = new Tiki_Event_Customizer;
		$customizer->addRule('tiki.trackeritem.save', '(event-record event args)');
		$customizer->bind($this->manager, $this->runner);

		$args = [
			'a' => 2,
			'b' => 3,
			'c' => 4,
			'EVENT_ID' => 1,
		];

		$this->manager->trigger('tiki.trackeritem.save', $args);

		$this->assertEquals('tiki.trackeritem.save', $this->lastEvent);
		$this->assertEquals($args, $this->lastArguments);
	}

	public function testChainedArgumentRecording()
	{
		$customizer = new Tiki_Event_Customizer;
		$customizer->addRule('tiki.trackeritem.save', '(event-record event args)');
		$customizer->bind($this->manager, $this->runner);

		$args = [
			'a' => 2,
			'b' => 3,
			'c' => 4,
			'EVENT_ID' => 1,
		];

		$this->manager->bind('tiki.trackeritem.update', 'tiki.trackeritem.save');
		$this->manager->trigger('tiki.trackeritem.update', $args);

		$this->assertEquals('tiki.trackeritem.update', $this->lastEvent);
		$this->assertEquals($args, $this->lastArguments);
	}

	public function testCustomEventRecording()
	{

		$customizer = new Tiki_Event_Customizer;
		$customizer->addRule('custom.event', '(event-record event args)');
		$customizer->addRule(
			'tiki.trackeritem.save',
			'(event-trigger custom.event
			(map
				(amount (add args.a args.b))
				(test args.c)
			))'
		);
		$customizer->bind($this->manager, $this->runner);

		$this->manager->trigger(
			'tiki.trackeritem.save',
			[
				'a' => 2,
				'b' => 3,
				'c' => 4,
			]
		);

		$this->assertEquals('custom.event', $this->lastEvent);
		$this->assertEquals(
			[
				'amount' => 5,
				'test' => 4,
				'EVENT_ID' => 2,
			],
			$this->lastArguments
		);
	}

	public function callbackAdd($arguments)
	{
		$this->called += $arguments['amount'] ?? 1;
	}

	public function callbackMultiply($arguments)
	{
		$this->called *= $arguments['amount'] ?? 2;
	}

	public function recordEvent($event, $arguments)
	{
		$this->lastEvent = $event;
		$this->lastArguments = $arguments;
	}
}
