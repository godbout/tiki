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

class Perms_CheckSequenceTest extends TikiTestCase
{
	private $mockA;
	private $mockB;

	protected function setUp() : void
	{
		$perms = new Perms;
		$perms->setResolverFactories(
			[
				new Perms_ResolverFactory_StaticFactory(
					'static',
					new Perms_Resolver_Static(
						['Admins' => ['admin_wiki'],]
					)
				)
			]
		);

		$perms->setGroups(['Admins']);
		$perms->setCheckSequence(
			[
				new Perms_Check_Direct,
				$this->mockA = $this->createMock('Perms_Check'),
				$this->mockB = $this->createMock('Perms_Check'),
			]
		);
		Perms::set($perms);
	}

	public function testOnlyFirstCalledWhenGranted()
	{
		$this->mockA->expects($this->never())
			->method('check');
		$this->mockB->expects($this->never())
			->method('check');

		$this->assertTrue(Perms::get()->admin_wiki);
	}

	public function testFirstFallbackHandles()
	{
		$this->mockA->expects($this->once())
			->method('check')
			->willReturn(true);
		$this->mockB->expects($this->never())
			->method('check');

		$this->assertTrue(Perms::get()->view);
	}

	public function testNoneCatching()
	{
		$this->mockA->expects($this->once())
			->method('check')
			->willReturn(false);
		$this->mockB->expects($this->once())
			->method('check')
			->willReturn(false);

		$this->assertFalse(Perms::get()->view);
	}
}
