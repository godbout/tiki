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
class JitFilter_FilterTest extends TikiTestCase
{
	private $array;

	protected function setUp() : void
	{
		$this->array = [
			'foo' => 'bar123',
			'bar' => 10,
			'baz' => [
				'hello',
				'world !',
			],
			'content' => '10 > 5 <script>',
		];

		$this->array = new JitFilter($this->array);
		$this->array->setDefaultFilter(new Laminas\I18n\Filter\Alnum);
	}

	protected function tearDown() : void
	{
		$this->array = null;
	}

	public function testValid()
	{
		$this->assertEquals('bar123', $this->array['foo']);
		$this->assertEquals(10, $this->array['bar']);
	}

	public function testInvalid()
	{
		$this->assertEquals('world', $this->array['baz'][1]);
	}

	public function testSpecifiedFilter()
	{
		$this->assertEquals('bar123', $this->array['foo']);

		$this->array->replaceFilter('foo', new Laminas\Filter\Digits);
		$this->assertEquals('123', $this->array['foo']);
	}

	public function testMultipleFilters()
	{
		$this->array->replaceFilters(
			[
				'foo' => new Laminas\Filter\Digits,
				'content' => new Laminas\Filter\StripTags,
				'baz' => [1 => new Laminas\Filter\StringToUpper,],
			]
		);

		$this->assertEquals('123', $this->array['foo']);
		$this->assertEquals('10  5 ', $this->array['content']);
		$this->assertEquals('WORLD !', $this->array['baz'][1]);
	}

	public function testNestedDefault()
	{
		$this->array->replaceFilters(
			[
				'foo' => new Laminas\Filter\Digits,
				'content' => new Laminas\Filter\StripTags,
				'baz' => new Laminas\Filter\StringToUpper,
			]
		);

		$this->assertEquals('123', $this->array['foo']);
		$this->assertEquals('10  5 ', $this->array['content']);
		$this->assertEquals('WORLD !', $this->array['baz'][1]);

		$this->array->replaceFilter('baz', new Laminas\I18n\Filter\Alpha);
		$this->assertEquals('world', $this->array['baz'][1]);

		$this->array->replaceFilters(
			['baz' => [1 => new Laminas\Filter\Digits,],]
		);

		$this->assertEquals('hello', $this->array['baz'][0]);
		$this->assertEquals('', $this->array['baz'][1]);
	}
}
