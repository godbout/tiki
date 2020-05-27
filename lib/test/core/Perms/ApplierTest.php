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

class Perms_ApplierTest extends TikiTestCase
{
	public function testApplyFromNothing()
	{
		$global = new Perms_Reflection_PermissionSet;
		$global->add('Anonymous', 'view');

		$object = new Perms_Reflection_PermissionSet;

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Registered', 'view');
		$newSet->add('Registered', 'edit');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn($object);
		$target->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn($global);
		$target->expects($this->at(2))
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('view'));
		$target->expects($this->at(3))
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->apply($newSet);
	}

	public function testFromExistingSet()
	{
		$global = new Perms_Reflection_PermissionSet;
		$global->add('Anonymous', 'view');

		$object = new Perms_Reflection_PermissionSet;
		$object->add('Registered', 'view');
		$object->add('Registered', 'edit');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Registered', 'view');
		$newSet->add('Editor', 'edit');
		$newSet->add('Editor', 'view_history');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn($object);
		$target->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn($global);
		$target->expects($this->at(2))
			->method('add')
			->with($this->equalTo('Editor'), $this->equalTo('edit'));
		$target->expects($this->at(3))
			->method('add')
			->with($this->equalTo('Editor'), $this->equalTo('view_history'));
		$target->expects($this->at(4))
			->method('remove')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->apply($newSet);
	}

	public function testAsParent()
	{
		$global = new Perms_Reflection_PermissionSet;
		$global->add('Anonymous', 'view');

		$object = new Perms_Reflection_PermissionSet;
		$object->add('Registered', 'view');
		$object->add('Registered', 'edit');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Anonymous', 'view');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn($object);
		$target->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn($global);
		$target->expects($this->at(2))
			->method('remove')
			->with($this->equalTo('Registered'), $this->equalTo('view'));
		$target->expects($this->at(3))
			->method('remove')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->apply($newSet);
	}

	public function testParentNotAvailable()
	{
		$global = new Perms_Reflection_PermissionSet;
		$global->add('Anonymous', 'view');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Anonymous', 'view');
		$newSet->add('Registered', 'edit');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn($global);
		$target->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn(null);
		$target->expects($this->at(2))
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->apply($newSet);
	}

	public function testMultipleTargets()
	{
		$global = new Perms_Reflection_PermissionSet;
		$global->add('Anonymous', 'view');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Anonymous', 'view');
		$newSet->add('Registered', 'edit');

		$target1 = $this->createMock('Perms_Reflection_Container');
		$target1->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn($global);
		$target1->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn(null);
		$target1->expects($this->at(2))
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$target2 = $this->createMock('Perms_Reflection_Container');
		$target2->expects($this->at(0))
			->method('getDirectPermissions')
			->willReturn(new Perms_Reflection_PermissionSet);
		$target2->expects($this->at(1))
			->method('getParentPermissions')
			->willReturn(null);
		$target2->expects($this->at(2))
			->method('add')
			->with($this->equalTo('Anonymous'), $this->equalTo('view'));
		$target2->expects($this->at(3))
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target1);
		$applier->addObject($target2);
		$applier->apply($newSet);
	}

	public function testRestrictChangedPermissions()
	{
		$before = new Perms_Reflection_PermissionSet;
		$before->add('Admin', 'admin');
		$before->add('Registered', 'edit');
		$before->add('Registered', 'view');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->once())
			->method('getDirectPermissions')
			->willReturn($before);
		$target->expects($this->once())
			->method('getParentPermissions')
			->willReturn(new Perms_Reflection_PermissionSet);
		$target->expects($this->once())
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('view_history'));

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Registered', 'edit');
		$newSet->add('Registered', 'view');
		$newSet->add('Registered', 'view_history');
		$newSet->add('Registered', 'admin');

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->restrictPermissions(['view', 'view_history', 'edit']);
		$applier->apply($newSet);
	}

	public function testNoRevertToParentWithRestrictions()
	{
		$current = new Perms_Reflection_PermissionSet;
		$current->add('Anonymous', 'view');

		$parent = new Perms_Reflection_PermissionSet;
		$parent->add('Anonymous', 'view');
		$parent->add('Registered', 'edit');
		$parent->add('Admins', 'admin');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Anonymous', 'view');
		$newSet->add('Registered', 'edit');
		$newSet->add('Admins', 'admin');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->once())
			->method('getDirectPermissions')
			->willReturn($current);
		$target->expects($this->once())
			->method('getParentPermissions')
			->willReturn($parent);
		$target->expects($this->once())
			->method('add')
			->with($this->equalTo('Registered'), $this->equalTo('edit'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->restrictPermissions(['view', 'edit']);
		$applier->apply($newSet);
	}

	public function testRevertIfWithinBounds()
	{
		$current = new Perms_Reflection_PermissionSet;
		$current->add('Anonymous', 'view');

		$parent = new Perms_Reflection_PermissionSet;
		$parent->add('Anonymous', 'view');
		$parent->add('Registered', 'edit');
		$parent->add('Admins', 'admin');

		$newSet = new Perms_Reflection_PermissionSet;
		$newSet->add('Anonymous', 'view');
		$newSet->add('Registered', 'edit');
		$newSet->add('Admins', 'admin');

		$target = $this->createMock('Perms_Reflection_Container');
		$target->expects($this->once())
			->method('getDirectPermissions')
			->willReturn($current);
		$target->expects($this->once())
			->method('getParentPermissions')
			->willReturn($parent);
		$target->expects($this->once())
			->method('remove')
			->with($this->equalTo('Anonymous'), $this->equalTo('view'));

		$applier = new Perms_Applier;
		$applier->addObject($target);
		$applier->restrictPermissions(['view', 'edit', 'admin']);
		$applier->apply($newSet);
	}
}
