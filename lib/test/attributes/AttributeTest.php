<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$attributelib = TikiLib::lib('attribute');

class AttributeTest extends TikiTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        TikiDb::get()->query('DELETE FROM `tiki_object_attributes` WHERE `attribute` LIKE ?', ['tiki.test%']);
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        TikiDb::get()->query('DELETE FROM `tiki_object_attributes` WHERE `attribute` LIKE ?', ['tiki.test%']);
    }

    public function testNoAttributes(): void
    {
        $lib = new AttributeLib;

        $this->assertEquals([], $lib->get_attributes('test', 'HelloWorld'));
    }

    public function testSetAttributes(): void
    {
        $lib = new AttributeLib;
        $lib->set_attribute('test', 'HelloWorld', 'tiki.test.abc', 121.22);
        $lib->set_attribute('test', 'HelloWorld', 'tiki.test.def', 111);
        $lib->set_attribute('test', 'Hello', 'tiki.test.ghi', 'no');
        $lib->set_attribute('test', 'HelloWorldAgain', 'tiki.test.jkl', 'no');

        $this->assertEquals(
            ['tiki.test.abc' => 121.22, 'tiki.test.def' => 111, ],
            $lib->get_attributes('test', 'HelloWorld')
        );
    }

    public function testReplaceValue(): void
    {
        $lib = new AttributeLib;
        $this->assertTrue($lib->set_attribute('test', 'HelloWorld', 'tiki.test.abc', 121.22));
        $this->assertTrue($lib->set_attribute('test', 'HelloWorld', 'tiki.test.abc', 'replaced'));

        $this->assertEquals(
            ['tiki.test.abc' => 'replaced', ],
            $lib->get_attributes('test', 'HelloWorld')
        );
    }

    public function testEnforceFormat(): void
    {
        $lib = new AttributeLib;
        $this->assertFalse($lib->set_attribute('test', 'HelloWorld', 'tiki.test', 121.22));

        $this->assertEquals([], $lib->get_attributes('test', 'HelloWorld'));
    }

    public function testLowecase(): void
    {
        $lib = new AttributeLib;
        $this->assertTrue($lib->set_attribute('test', 'HelloWorld', 'tiki.TEST.aaa', 121.22));

        $this->assertEquals(
            ['tiki.test.aaa' => 121.22, ],
            $lib->get_attributes('test', 'HelloWorld')
        );
    }

    public function testFilterUndesired(): void
    {
        $lib = new AttributeLib;
        $this->assertTrue($lib->set_attribute('test', 'HelloWorld', 'tiki . test . aaa55bBb', 121.22));

        $this->assertEquals(
            ['tiki.test.aaa55bbb' => 121.22, ],
            $lib->get_attributes('test', 'HelloWorld')
        );
    }

    public function testRemoveEmpty(): void
    {
        $lib = new AttributeLib;
        $lib->set_attribute('test', 'HelloWorld', 'tiki.test.abc', 121.22);
        $lib->set_attribute('test', 'HelloWorld', 'tiki.test.abc', '');

        $this->assertEquals([], $lib->get_attributes('test', 'HelloWorld'));
    }
}
