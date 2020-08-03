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
class DeclFilter_StaticKeyUnsetTest extends TikiTestCase
{
    public function testMatch()
    {
        $rule = new DeclFilter_StaticKeyUnsetRule(['hello', 'world']);

        $this->assertTrue($rule->match('hello'));
        $this->assertTrue($rule->match('world'));
        $this->assertFalse($rule->match('baz'));
    }

    public function testApply()
    {
        $rule = new DeclFilter_StaticKeyUnsetRule(['hello', 'world']);

        $data = [
            'hello' => '123abc',
            'world' => '123abc',
            'foo' => '123abc',
        ];

        $rule->apply($data, 'hello');
        $rule->apply($data, 'world');

        $this->assertFalse(isset($data['hello']));
        $this->assertFalse(isset($data['world']));
        $this->assertEquals('123abc', $data['foo']);
    }
}
