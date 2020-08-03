<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\CustomRoute;

use PHPUnit\Framework\TestCase;
use Tiki\CustomRoute\Controller;

/**
 * Class ControllerTest
 */
class ControllerTest extends TestCase
{

    /**
     * @covers \Tiki\CustomRoute\Controller::populateFromRequest()
     */
    public function testPopulateFromRequest()
    {
        $request = [
            'route' => 10,
            'router_type' => 'Direct',
            'router_from' => 'http://dummy.tiki.org',
            'router_description' => 'Test route',
            'router_active' => 1,
            'router_short_url' => 0,
            'direct_to' => 'http://tiki.org',
        ];

        $controller = new Controller();
        $item = $controller->populateFromRequest($request);

        $this->assertEquals($item->id, $request['route']);
        $this->assertEquals($item->type, $request['router_type']);
        $this->assertEquals($item->from, $request['router_from']);
        $this->assertEquals($item->description, $request['router_description']);
        $this->assertEquals($item->active, $request['router_active']);
        $this->assertEquals($item->short_url, $request['router_short_url']);
        $this->assertEquals($item->redirect, json_encode(['to' => $request['direct_to']]));
    }
}
