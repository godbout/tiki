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
class Perms_Resolver_DefaultTest extends TikiTestCase
{
    public function testAsExpected()
    {
        $resolver = new Perms_Resolver_Default(true);
        $this->assertTrue($resolver->check('view', []));

        $resolver = new Perms_Resolver_Default(false);
        $this->assertFalse($resolver->check('view', []));
    }

    public function testApplicableGroups()
    {
        $resolver = new Perms_Resolver_Default(true);
        $this->assertContains('Anonymous', $resolver->applicableGroups());
        $this->assertContains('Registered', $resolver->applicableGroups());
    }
}
