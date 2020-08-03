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
class Perms_Check_DirectTest extends TikiTestCase
{
    public function testCallForwarded()
    {
        $direct = new Perms_Check_Direct;

        $mock = $this->createMock('Perms_Resolver');
        $mock->expects($this->once())
            ->method('check')
            ->with($this->equalTo('view'), $this->equalTo(['Admins', 'Anonymous']))
            ->willReturn(true);

        $this->assertTrue($direct->check($mock, [], 'view', ['Admins', 'Anonymous']));
    }

    public function testCallForwardedWhenFalseToo()
    {
        $direct = new Perms_Check_Direct;

        $mock = $this->createMock('Perms_Resolver');
        $mock->expects($this->once())
            ->method('check')
            ->with($this->equalTo('view'), $this->equalTo(['Admins', 'Anonymous']))
            ->willReturn(false);

        $this->assertFalse($direct->check($mock, [], 'view', ['Admins', 'Anonymous']));
    }
}
