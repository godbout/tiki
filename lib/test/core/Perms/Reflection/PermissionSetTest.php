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
class Perms_Reflection_PermissionSetTest extends TikiTestCase
{
    public function testEmptySet()
    {
        $set = new Perms_Reflection_PermissionSet;

        $this->assertEquals([], $set->getPermissionArray());
    }

    public function testBasicSet()
    {
        $set = new Perms_Reflection_PermissionSet;
        $set->add('Registered', 'view');
        $set->add('Registered', 'edit');
        $set->add('Anonymous', 'view');

        $this->assertEquals(
            [
                'Registered' => ['view', 'edit'],
                'Anonymous' => ['view'],
            ],
            $set->getPermissionArray()
        );
    }

    public function testDuplicateEntry()
    {
        $set = new Perms_Reflection_PermissionSet;
        $set->add('Registered', 'view');
        $set->add('Registered', 'edit');
        $set->add('Registered', 'view');

        $this->assertEquals(
            ['Registered' => ['view', 'edit'], ],
            $set->getPermissionArray()
        );
    }

    public function testPositiveHas()
    {
        $set = new Perms_Reflection_PermissionSet;
        $set->add('Anonymous', 'view');

        $this->assertTrue($set->has('Anonymous', 'view'));
    }

    public function testNegativeHas()
    {
        $set = new Perms_Reflection_PermissionSet;

        $this->assertFalse($set->has('Anonymous', 'view'));
    }

    public function testAddMultiple()
    {
        $equivalent = new Perms_Reflection_PermissionSet;
        $equivalent->add('Anonymous', 'a');
        $equivalent->add('Anonymous', 'b');
        $equivalent->add('Anonymous', 'c');

        $multi = new Perms_Reflection_PermissionSet;
        $multi->add('Anonymous', ['a', 'b', 'c']);

        $this->assertEquals($equivalent, $multi);
    }
}
