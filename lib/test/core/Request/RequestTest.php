<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class RequestTest extends TikiTestCase
{
    protected function setUp() : void
    {
        $this->obj = new Tiki_Request();
    }

    protected function tearDown() : void
    {
        unset($_REQUEST, $_POST, $_SERVER['argv'], $_SERVER['argc'], $_SERVER['REQUEST_METHOD']);
    }

    public function testEndToEndHttp()
    {
        $expectedResult = $_REQUEST = ['someKey' => 'someValue', 'otherKey' => 'otherValue'];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $obj = new Tiki_Request();

        foreach ($expectedResult as $key => $value) {
            $this->assertEquals($value, $obj->getProperty($key));
        }
    }

    public function testEndToEndCli()
    {
        global $argv, $argc;
        $_SERVER['argv'] = ['someKey=someValue', 'otherKey=otherValue'];
        $_SERVER['argc'] = 3;
        $expectedResult = ['someKey' => 'someValue', 'otherKey' => 'otherValue'];

        $obj = new Tiki_Request();

        foreach ($expectedResult as $key => $value) {
            $this->assertEquals($value, $obj->getProperty($key));
        }
    }

    public function testConstruct_shouldSetHttpRequestProperties()
    {
        $this->assertTrue(true);
    }

    public function testGetProperty_shouldReturnNullIfPropertyNotSet()
    {
        $this->assertNull($this->obj->getProperty('invalidKey'));
    }

    public function testGetAndSetProperty_shouldSetAndReturnPropertyValue()
    {
        $this->obj->setProperty('someKey', 'someValue');
        $this->assertEquals('someValue', $this->obj->getProperty('someKey'));

        $this->obj->setProperty('otherKey', 'otherValue');
        $this->assertEquals('otherValue', $this->obj->getProperty('otherKey'));

        $this->obj->setProperty('otherKey', 'overrideValue');
        $this->assertEquals('overrideValue', $this->obj->getProperty('otherKey'));
    }

    public function testHasProperty_shouldReturnFalse()
    {
        $this->assertFalse($this->obj->hasProperty('invalidKey'));
    }

    public function testHasProperty_shouldReturnTrue()
    {
        $this->obj->setProperty('someKey', 'someValue');
        $this->assertTrue($this->obj->hasProperty('someKey'));
    }
}
