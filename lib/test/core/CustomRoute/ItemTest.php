<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\CustomRoute;

use PHPUnit\Framework\TestCase;
use Tiki\CustomRoute\Item;

/**
 * Class ItemTest
 */
class ItemTest extends TestCase
{

    /** @var Item */
    protected $item;

    protected function setUp() : void
    {
        $id = 10;
        $from = 'test-route';
        $type = 'Direct';
        $redirect = ['to' => 'http://tiki.org'];
        $description = 'Test route';
        $active = 1;
        $shortUrl = 0;

        $this->item = new Item($type, $from, $redirect, $description, $active, $shortUrl, $id);
    }

    /**
     * @covers \Tiki\CustomRoute\Item::toArray()
     */
    public function testToArray()
    {
        $expect = [
            'id' => $this->item->id,
            'type' => $this->item->type,
            'from' => $this->item->from,
            'params' => json_decode($this->item->redirect, true),
            'description' => $this->item->description,
            'active' => $this->item->active,
            'short_url' => $this->item->short_url,
        ];

        $this->assertEquals($this->item->toArray(), $expect);
    }

    /**
     * @covers \Tiki\CustomRoute\Item::getRedirectPath()
     */
    public function testGetRedirectPath()
    {
        $path = 'test-route';
        $anotherRoute = 'test-another-route';

        $this->assertEquals('http://tiki.org', $this->item->getRedirectPath($path));
        $this->assertEmpty($this->item->getRedirectPath($anotherRoute));
    }
}
