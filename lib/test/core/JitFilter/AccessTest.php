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

class JitFilter_AccessTest extends TikiTestCase
{
	private $array;

	protected function setUp() : void
	{
		$this->array = [
			'foo' => 'bar',
			'bar' => 10,
			'baz' => [
				'hello',
				'world',
			],
		];

		$this->array = new JitFilter($this->array);
	}

	protected function tearDown() : void
	{
		$this->array = null;
	}

	function testBasicAccess()
	{
		$this->assertEquals('bar', $this->array['foo']);
		$this->assertEquals(10, $this->array['bar']);
		$this->assertEquals('world', $this->array['baz'][1]);
	}

	function testRecursiveness()
	{
		$this->assertInstanceOf(JitFilter::class, $this->array['baz']);
	}

	function testDefinition()
	{
		$this->assertTrue(isset($this->array['baz']));
		$this->assertFalse(isset($this->array['hello']));
	}

	function testDirectArray()
	{
		$this->assertEquals([], array_diff(['hello', 'world'], $this->array['baz']->asArray()));
	}

	function testKeys()
	{
		$this->assertEquals(['foo', 'bar', 'baz'], $this->array->keys());
	}

	function testIsArray()
	{
		$this->assertTrue($this->array->isArray('baz'));
	}

	function testAsArray()
	{
		$this->assertEquals(['bar'], $this->array->asArray('foo'));
		$this->assertEquals([], $this->array->asArray('not_exists'));
	}

	function testAsArraySplit()
	{
		$test = new JitFilter(['foo' => '1|2a|3']);
		$test->setDefaultFilter(new Zend\Filter\Digits);

		$this->assertEquals(['1', '2', '3'], $test->asArray('foo', '|'));
	}

	function testSubset()
	{
		$this->assertEquals(
			[
				'foo' => $this->array['foo'],
				'baz' => $this->array->asArray('baz')
			],
			$this->array->subset(['foo', 'baz'])->asArray()
		);
	}

	function testUnset()
	{
		unset($this->array['baz']);

		$this->assertFalse(isset($this->array['baz']));
	}

	function testSet()
	{
		$this->array['new'] = 'foo';

		$this->assertEquals('foo', $this->array['new']);
	}

	function testGetSingleWithoutPresetGeneric()
	{
		$this->assertEquals('BAR', $this->array->foo->filter(new Zend\Filter\StringToUpper));
	}

	function testGetSinfleWithoutPresetNamed()
	{
		$this->assertEquals('10', $this->array->bar->digits());
	}

	function testGetStructuredWithoutPresetGeneric()
	{
		$filtered = $this->array->baz->filter(new Zend\Filter\StringToUpper);
		$this->assertEquals(['HELLO', 'WORLD'], $filtered);
	}

	function testGetStructuredWithoutPresetNamed()
	{
		$filtered = $this->array->baz->alpha();
		$this->assertEquals(['hello', 'world'], $filtered);
	}
}
