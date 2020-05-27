<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_ResultLoaderTest extends PHPUnit\Framework\TestCase
{
	private $read;

	public function testFetchNothing()
	{
		$this->read = [
			[],
		];
		$this->assertLoaderData([], new Services_ResultLoader([$this, 'read']));
	}

	public function testFetchOnePartial()
	{
		$this->read = [
			[2, 4, 6],
		];
		$this->assertLoaderData([2, 4, 6], new Services_ResultLoader([$this, 'read']));
	}

	public function testFetchMultipleComplete()
	{
		$this->read = [
			[2, 4, 6],
			[8, 9, 0],
			[],
		];
		$this->assertLoaderData([2, 4, 6, 8, 9, 0], new Services_ResultLoader([$this, 'read'], 3));
	}

	public function testCompleteAndPartial()
	{
		$this->read = [
			[2, 4, 6],
			[8],
		];
		$this->assertLoaderData([2, 4, 6, 8], new Services_ResultLoader([$this, 'read'], 3));
	}

	public function assertLoaderData($expect, $loader)
	{
		$accumulate = [];
		foreach ($loader as $value) {
			$accumulate[] = $value;
		}

		$this->assertEquals($expect, $accumulate);
	}

	public function read($offset, $count)
	{
		$this->assertEquals(0, $offset % $count);
		return array_shift($this->read);
	}
}
