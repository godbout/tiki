<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation;

use PHPUnit\Framework\TestCase;
use Tiki\Recommendation\Input\UserInput as U;

class BatchTest extends TestCase implements Store\StoreInterface
{
	private $inputs = false;
	private $storeCalls = [];
	private $checkCallback;

	protected function setUp() : void
	{
		$this->checkCallback = function ($input, $recomendation) {
			return false;
		};
	}

	public function testNoEngines()
	{
		$engineSet = new EngineSet;

		$this->inputs = [new U('a'), new U('B')];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$this->assertCount(0, $this->storeCalls);
	}

	public function testNoInput()
	{
		$engineSet = new EngineSet;
		$engineSet->register('test-a', new Engine\FakeEngine([
			['type' => 'wiki page', 'object' => 'Content A'],
			['type' => 'wiki page', 'object' => 'Content B'],
		]));

		$this->inputs = [];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$this->assertCount(0, $this->storeCalls);
	}

	public function testNoRecommendations()
	{
		$engineSet = new EngineSet;
		$engineSet->register('test-a', new Engine\FakeEngine([
		]));

		$this->inputs = [new U('a'), new U('B')];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$this->assertCount(0, $this->storeCalls);
	}

	public function testProcessOne()
	{
		$engineSet = new EngineSet;
		$engineSet->register('test-a', new Engine\FakeEngine([
			['type' => 'wiki page', 'object' => 'Content A'],
			['type' => 'wiki page', 'object' => 'Content B'],
		]));

		$this->inputs = [new U('a')];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$expect = new RecommendationSet('test-a');
		$expect->add(new Recommendation('wiki page', 'Content A'));
		$expect->add(new Recommendation('wiki page', 'Content B'));

		$this->assertCount(1, $this->storeCalls);
		$this->assertEquals([new U('a'), $expect], $this->storeCalls[0]);
	}

	public function testFilterAlreadyReceied()
	{
		$i = 0;

		$this->checkCallback = function ($input, $rec) use (&$i) {
			return $i++ == 0;
		};

		$engineSet = new EngineSet;
		$engineSet->register('test-a', new Engine\FakeEngine([
			['type' => 'wiki page', 'object' => 'Content A'],
			['type' => 'wiki page', 'object' => 'Content B'],
		]));

		$this->inputs = [new U('a'), new U('b')];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$expectA = new RecommendationSet('test-a');
		$expectA->add(new Recommendation('wiki page', 'Content B'));

		$expectB = new RecommendationSet('test-a');
		$expectB->add(new Recommendation('wiki page', 'Content A'));
		$expectB->add(new Recommendation('wiki page', 'Content B'));

		$this->assertEquals([new U('a'), $expectA], $this->storeCalls[0]);
		$this->assertEquals([new U('b'), $expectB], $this->storeCalls[1]);
	}

	public function testBatchIgnoresDebugInformation()
	{
		$engineSet = new EngineSet;
		$engineSet->register('test-a', new Engine\FakeEngine([
			new Debug\SourceDocument('wiki page', 'Content A'),
			['type' => 'wiki page', 'object' => 'Content B'],
		]));

		$this->inputs = [new U('a')];
		$batch = new BatchProcessor($this, $engineSet);
		$batch->process();

		$expect = new RecommendationSet('test-a');
		$expect->add(new Recommendation('wiki page', 'Content B'));

		$this->assertEquals([new U('a'), $expect], $this->storeCalls[0]);
	}

	// StoreInterface

	public function isReceived($input, Recommendation $recommendation)
	{
		$cb = $this->checkCallback;
		return $cb($input, $recommendation);
	}

	public function store($input, RecommendationSet $recommendation)
	{
		$this->storeCalls[] = func_get_args();
	}

	public function getInputs()
	{
		$this->assertIsArray($this->inputs);
		return $this->inputs;
	}

	public function terminate()
	{
		$this->inputs = false;
	}
}
