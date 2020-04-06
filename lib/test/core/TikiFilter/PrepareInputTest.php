<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiFilter_PrepareInputTest extends PHPUnit\Framework\TestCase
{
	private $obj;

	protected function setUp() : void
	{
		$this->obj = new TikiFilter_PrepareInput('.');
	}

	public function testNormalInput()
	{
		$input = [
			'foo' => 'bar',
			'hello' => 'world',
		];

		$this->assertEquals($input, $this->obj->prepare($input));
	}

	public function testConvertArray()
	{
		$input = [
			'foo.baz' => 'bar',
			'foo.bar' => 'baz',
			'hello' => 'world',
			'a.b.c' => '1',
			'a.b.d' => '2',
		];

		$expect = [
			'foo' => [
				'baz' => 'bar',
				'bar' => 'baz',
			],
			'hello' => 'world',
			'a' => [
				'b' => [
					'c' => '1',
					'd' => '2',
				],
			],
		];

		$this->assertEquals($expect, $this->obj->prepare($input));
	}

	public function testNormalFlatten()
	{
		$input = [
			'foo' => 'bar',
			'hello' => 'world',
		];

		$this->assertEquals($input, $this->obj->flatten($input));
	}

	public function testConvertArrayFlatten()
	{
		$input = [
			'foo' => [
				'baz' => 'bar',
				'bar' => 'baz',
			],
			'hello' => 'world',
			'a' => [
				'b' => [
					'c' => '1',
					'd' => '2',
				],
			],
		];

		$expect = [
			'foo.baz' => 'bar',
			'foo.bar' => 'baz',
			'hello' => 'world',
			'a.b.c' => '1',
			'a.b.d' => '2',
		];

		$this->assertEquals($expect, $this->obj->flatten($input));
	}
}
