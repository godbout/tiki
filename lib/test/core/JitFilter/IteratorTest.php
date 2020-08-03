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
class JitFilter_IteratorTest extends TikiTestCase
{
    private $array;

    protected function setUp() : void
    {
        $this->array = [
            'foo' => 'bar',
            'bar' => 10,
            'baz' => [
                'hello',
                'world',
            ],
        ];

        $this->array = new JitFilter($this->array);
        $this->array->setDefaultFilter(new Laminas\Filter\StringToUpper);
    }

    protected function tearDown() : void
    {
        $this->array = null;
    }

    public function testForeach()
    {
        foreach ($this->array as $key => $value) {
            switch ($key) {
                case 'foo':
                    $this->assertEquals('BAR', $value);

                    break;
                case 'bar':
                    $this->assertEquals(10, $value);

                    break;
                case 'baz':
                    $this->assertCount(2, $value);

                    break;
                default:
                    $this->assertTrue(false, 'Unknown key found');
            }
        }
    }
}
