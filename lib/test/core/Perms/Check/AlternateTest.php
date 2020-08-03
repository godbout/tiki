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
class Perms_Check_AlternateTest extends PHPUnit\Framework\TestCase
{
    public function testUnconfigured()
    {
        $resolver = new Perms_Resolver_Default(true);

        $check = new Perms_Check_Alternate('admin');
        $this->assertFalse($check->check($resolver, [], 'view', ['Registered']));
    }

    public function testWithReplacementResolver()
    {
        $resolver = new Perms_Resolver_Default(false);
        $replacement = new Perms_Resolver_Static(
            ['Registered' => ['admin'], ]
        );

        $check = new Perms_Check_Alternate('admin');
        $check->setResolver($replacement);
        $this->assertTrue($check->check($resolver, [], 'view', ['Registered']));
    }

    public function testWithReplacementNotAllowing()
    {
        $resolver = new Perms_Resolver_Default(false);
        $replacement = new Perms_Resolver_Static(
            ['Registered' => ['view', 'edit'], ]
        );

        $check = new Perms_Check_Alternate('admin');
        $check->setResolver($replacement);
        $this->assertFalse($check->check($resolver, [], 'view', ['Registered']));
    }
}
