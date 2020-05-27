<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */


class WikiParser_PluginParserTest extends TikiTestCase
{
	public function testNothingToParse()
	{
		$data = 'Hello world this is a simple test';
		$parser = new WikiParser_PluginParser;

		$this->assertEquals($data, $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testCallToArgumentParser()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginArgumentParser')
			->expects($this->once())
			->method('parse')
			->with($this->equalTo('hello=world'));

		$data = 'This is a {TEST(hello=world)}Hello{TEST} without any changes';

		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($this->createMock('WikiParser_PluginRunner'));
		$parser->setArgumentParser($mock);
		$parser->parse($data);
	}

	public function testPluginWithoutRunner()
	{
		$data = 'This is a {TEST(hello=world)}Hello{TEST} without any changes';
		$parser = new WikiParser_PluginParser;

		$this->assertEquals($data, $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testFullSyntax()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->with(
				$this->equalTo('test'),
				$this->equalTo('Hello'),
				$this->equalTo(['hello' => 'world'])
			)
			->willReturn('test');

		$data = 'This is a {TEST(hello=world)}Hello{TEST} and will change';
		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('This is a test and will change', $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testShortSyntax()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->with(
				$this->equalTo('test'),
				$this->equalTo(null),
				$this->equalTo(['hello' => 'world'])
			)
			->willReturn('test');

		$data = 'This is a {test hello=world} and will change';
		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('This is a test and will change', $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testShortSyntaxWithoutArguments()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->with(
				$this->equalTo('test'),
				$this->equalTo(null),
				$this->equalTo([])
			)
			->willReturn('test');

		$data = 'This is a {test} and will change';
		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('This is a test and will change', $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testSkipNoParse()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->with($this->equalTo('b'), $this->equalTo(null), $this->equalTo([]))
			->willReturn('return');

		$data = '~np~ {a} ~/np~ {b} ~np~ {c} ~/np~';
		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('~np~ {a} ~/np~ return ~np~ {c} ~/np~', $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testNestingNoSecondCall()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->with($this->equalTo('a'), $this->equalTo(' {b} '), $this->equalTo([]))
			->willReturn('no plugin');

		$data = '{A()} {b} {A}';
		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('no plugin', $parser->parse($data));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testPluginReturningPlugin()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->exactly(2))
			->method('run')
			->willReturnOnConsecutiveCalls('__{b}__', 'hello');

		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('before __hello__ after', $parser->parse('before {a} after'));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testInnerPluginNotExecutedFirst()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->exactly(2))
			->method('run')
			->willReturnOnConsecutiveCalls('__{b}__', 'hello');

		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$this->assertEquals('__hello__', $parser->parse('{A()} {b} {A}'));
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testPluginReturnsNonParseCode()
	{
		$this->markTestIncomplete('Implementation not written yet');
		$mock = $this->createMock('WikiParser_PluginRunner')
			->expects($this->once())
			->method('run')
			->willReturn('~np~{b}~/np~');

		$parser = new WikiParser_PluginParser;
		$parser->setPluginRunner($mock);
		$parser->setArgumentParser(new WikiParser_PluginArgumentParser);
		$parser->parse('{a}');
	}
}
