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

class WikiParser_PluginRepositoryTest extends TikiTestCase
{
	function testPluginDoesNotExist()
	{
		$repository = new WikiParser_PluginRepository;
		$this->assertFalse($repository->pluginExists('test'));
	}

	function testTestPhysicalPlugin()
	{
		$repository = new WikiParser_PluginRepository;
		$repository->addPluginFolder(__DIR__);

		$this->assertTrue($repository->pluginExists('foo'));
		$this->assertFalse($repository->pluginExists('fake'));
	}

	function testObtainInfoForNormalPlugin()
	{
		$repository = new WikiParser_PluginRepository;
		$repository->addPluginFolder(__DIR__);

		$info = $repository->getInfo('foo');

		$this->assertEquals(2, count($info['params']));
		$this->assertEquals(tra('Foo'), $info['name']);
	}

	function testGetListWithNormalOnly()
	{
		$repository = new WikiParser_PluginRepository;
		$repository->addPluginFolder(__DIR__);

		$this->assertEquals(['foo'], $repository->getList());
	}
}
