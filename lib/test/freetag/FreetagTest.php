<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$freetaglib = TikiLib::lib('freetag');

class FreetagTest extends TikiTestCase
{
    private $lib;

    protected function setUp() : void
    {
        $this->lib = new FreetagLib();
    }

    public function testDumbParseTagsShouldReturnEmptyArray(): void
    {
        $this->assertEquals([], $this->lib->dumb_parse_tags(null));
        $this->assertEquals([], $this->lib->dumb_parse_tags([]));
        $this->assertEquals([], $this->lib->dumb_parse_tags(''));
    }

    public function testDumbParseTagsShouldReturnParsedArray(): void
    {
        //TODO: mock FreetagLib::_parse_tag() and FreetagLib::normalize_tag()
        $expectedResult = [
                'data' => [
                    0 => ['tag' => 'first'],
                    1 => ['tag' => 'multiple word tag'],
                    2 => ['tag' => 'third'],
                    3 => ['tag' => 'another multiple word tag']
                    ],
                'cant' => 4,
                ];

        $tagString = 'first "multiple word tag" third "another Multiple Word tag"';

        $this->assertEquals($expectedResult, $this->lib->dumb_parse_tags($tagString));
    }
}
