<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_FactoryTest extends PHPUnit\Framework\TestCase
{
	private $plugin;

	protected function setUp() : void
	{
		$this->plugin = new Search_Formatter_Plugin_WikiTemplate("");
	}

	public function testInstantiation()
	{
		$formatter = Search_Formatter_Factory::newFormatter($this->plugin);
		$this->assertInstanceOf(Search_Formatter::class, $formatter);
	}

	public function testSequence()
	{
		$formatter1 = Search_Formatter_Factory::newFormatter($this->plugin);
		$formatter2 = Search_Formatter_Factory::newFormatter($this->plugin);
		$this->assertEquals($formatter1->getCounter() + 1, $formatter2->getCounter());
	}
}
