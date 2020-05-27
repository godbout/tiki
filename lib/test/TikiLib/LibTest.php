<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiLib_LibTest extends PHPUnit\Framework\TestCase
{
	public function testLib_shouldReturnInstanceOfTikiLib(): void
	{
		$this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
	}

	public function testLib_shouldReturnInstanceOfCalendar(): void
	{
		$this->assertInstanceOf(CalendarLib::class, TikiLib::lib('calendar'));
	}

	public function testLib_shouldReturnNullForInvalidClass(): void
	{
		$this->expectException(Exception::class);
		TikiLib::lib('invalidClass');
	}
}
