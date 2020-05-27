<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */

class Perms_Reflection_PermissionComparatorTest extends TikiTestCase
{
	public function testIdenticalSets()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertTrue($comparator->equal());
	}

	public function testLeftHasLessPermissions()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');
		$b->add('Anonymous', 'edit');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertFalse($comparator->equal());
	}

	public function testLeftHasLessGroups()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');
		$b->add('Registered', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertFalse($comparator->equal());
	}

	public function testRightHasLessPermissions()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');
		$a->add('Anonymous', 'edit');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertFalse($comparator->equal());
	}

	public function testRightHasLessGroups()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');
		$a->add('Registered', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertFalse($comparator->equal());
	}

	public function testGetRemovals()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');
		$a->add('Registered', 'view');
		$a->add('Registered', 'edit');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Registered', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertEquals(
			[
				['Anonymous', 'view'],
				['Registered', 'edit'],
			],
			$comparator->getRemovals()
		);
	}

	public function testGetAdditions()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');
		$b->add('Registered', 'view');
		$b->add('Registered', 'edit');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertEquals(
			[
				['Registered', 'view'],
				['Registered', 'edit'],
			],
			$comparator->getAdditions()
		);
	}

	public function testIdenticalHasNoDifferences()
	{
		$a = new Perms_Reflection_PermissionSet;
		$a->add('Anonymous', 'view');

		$b = new Perms_Reflection_PermissionSet;
		$b->add('Anonymous', 'view');

		$comparator = new Perms_Reflection_PermissionComparator($a, $b);
		$this->assertEquals([], $comparator->getAdditions());
		$this->assertEquals([], $comparator->getRemovals());
	}
}
