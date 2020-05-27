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

class Perms_Check_CreatorTest extends TikiTestCase
{
	public function testNoActionTakenWhenNoCreator()
	{
		$mock = $this->createMock('Perms_Resolver');
		$mock->expects($this->never())
			->method('check');

		$creator = new Perms_Check_Creator('foobar');
		$this->assertFalse($creator->check($mock, [], 'view', ['Registered']));
	}

	public function testNoActionTakenWhenWrongCreator()
	{
		$mock = $this->createMock('Perms_Resolver');
		$mock->expects($this->never())
			->method('check');

		$creator = new Perms_Check_Creator('foobar');
		$this->assertFalse($creator->check($mock, ['creator' => 'barbaz'], 'view', ['Registered']));
	}

	public function testCallForwarded()
	{
		$mock = $this->createMock('Perms_Resolver');
		$mock->expects($this->once())
			->method('check')
			->with($this->equalTo('view_own'), $this->equalTo(['Registered']))
			->willReturn(true);

		$creator = new Perms_Check_Creator('foobar');
		$this->assertTrue($creator->check($mock, ['creator' => 'foobar'], 'view', ['Registered']));
	}
}
