<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\CustomRoute;

use Tiki\CustomRoute\CustomRoute;
use Tiki\CustomRoute\Item;
use TikiLib;

/**
 * Class CustomRouteTest
 */
class CustomRouteTest extends \PHPUnit_Framework_TestCase
{
	protected static $routes = [];

	public static function tearDownAfterClass()
	{
		$routeLib = TikiLib::lib('custom_route');

		foreach (self::$routes as $routeId) {
			$routeLib->removeRoute($routeId);
		}
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::getShortUrlRoute()
	 */
	public function testGetShortUrlRoute()
	{

		$url = 'https://tiki.org/Homepage';

		$route = CustomRoute::getShortUrlRoute($url, 'Test route');

		self::$routes[] = $route->id;

		$this->assertNotEmpty($route);
		$this->assertEquals(Item::TYPE_DIRECT, $route->type);

		$params = json_decode($route->redirect, true);
		$this->assertEquals($url, $params['to']);
		$this->assertTrue((bool) $route->short_url);
		$this->assertTrue((bool) $route->active);
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::getShortUrlRoute()
	 */
	public function testGetExistingShortUrl()
	{
		$url = 'https://tiki.org/Homepage';
		$route = CustomRoute::getShortUrlRoute($url, 'Test route');

		$this->assertTrue(in_array($route->id, self::$routes));
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::matchRoute()
	 */
	public function testMatchExistingRoute()
	{
		$hash = CustomRoute::generateShortUrlHash();
		$objectType = 'wiki page';
		$objectId = 'myShortUrlPage-' . $hash;

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test custom url route', 1, 1);

		$errors = $route->validate();
		$this->assertEmpty($errors);

		$route->save();

		self::$routes[] = $route->id;

		$match = CustomRoute::matchRoute($hash);

		$this->assertNotEmpty($match);
		$this->assertEquals($route, $match);
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::matchRoute()
	 */
	public function testMatchInactiveRoute()
	{
		$hash = CustomRoute::generateShortUrlHash();
		$objectType = 'wiki page';
		$objectId = 'myShortUrlPage-' . $hash;

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test custom url route', 0, 1);

		$errors = $route->validate();
		$this->assertEmpty($errors);

		$route->save();

		self::$routes[] = $route->id;

		$match = CustomRoute::matchRoute($hash);
		$this->assertEmpty($match);
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::matchRoute()
	 */
	public function testMatchNonExistingRoute()
	{
		$hash = CustomRoute::generateShortUrlHash() . '-empty';
		$match = CustomRoute::matchRoute($hash);
		$this->assertEmpty($match);
	}

	public function testAddDirectCustomRoute()
	{
		$route = new Item(Item::TYPE_DIRECT, 'direct-test-route', ['to' => 'https://tiki.org'], 'Custom route test', 1, 0);

		$errors = $route->validate();
		$this->assertEmpty($errors);

		$route->save();

		self::$routes[] = $route->id;

		$this->assertNotEmpty($route->id);
	}

	/**
	 * @dataProvider getDirectRouteData
	 */
	public function testAddInvalidDirectCustomRoute($from, $routerDetails, $expectError)
	{
		$route = new Item(Item::TYPE_DIRECT, $from, $routerDetails, 'Custom route test', 1, 0);

		$errors = $route->validate();
		$this->assertEquals($expectError, ! empty($errors));
	}

	public function getDirectRouteData()
	{
		return [
			[
				'test-direct-route',
				['to' => ''],
				true
			],
			[
				'test-direct-route',
				[],
				true
			],
			[
				'',
				['to' => 'http://example.com'],
				true
			],
			[
				'direct-test-route', // same From as route saved in testAddDirectCustomRoute()
				['to' => 'http://example.com'],
				true
			],
			[
				'test-direct-route',
				['to' => 'http://example.com'],
				false
			]
		];
	}

	public function testAddTikiObjectCustomRoute()
	{

		$routerDetails = [
			'type' => 'wiki page',
			'object' => '1'
		];

		$route = new Item(Item::TYPE_OBJECT, 'object-test-route', $routerDetails, 'Custom route test', 1, 0);

		$errors = $route->validate();
		$this->assertEmpty($errors);

		$route->save();

		self::$routes[] = $route->id;

		$this->assertNotEmpty($route->id);
	}

	/**
	 * @dataProvider getTikiObjectRouteData
	 */
	public function testAddInvalidTikiObjectCustomRoute($from, $routerDetails, $expectError)
	{
		$route = new Item(Item::TYPE_OBJECT, $from, $routerDetails, 'Custom route test', 1, 0);

		$errors = $route->validate();
		$this->assertEquals($expectError, ! empty($errors));
	}

	public function getTikiObjectRouteData()
	{
		return [
			[
				'test-object-route',
				[
					'type' => '',
					'object' => '1'
				],
				true
			],
			[
				'test-object-route',
				[
					'type' => 'wiki page',
					'object' => ''
				],
				true
			],
			[
				'test-object-route',
				[
					'type' => 'wiki page',
				],
				true
			],
			[
				'',
				[
					'type' => 'wiki page',
					'object' => '1'
				],
				true
			],
			[
				'object-test-route', // same From as route saved in testAddDirectCustomRoute()
				[
					'type' => 'wiki page',
					'object' => '1'
				],
				true
			],
			[
				'test-object-route',
				[
					'type' => 'wiki page',
					'object' => '1'
				],
				false
			]
		];
	}

	public function testAddTrackerFieldCustomRoute()
	{
		$from = '|^test-(\w+)$|';
		$routerDetails = ['tracker' => '2', 'tracker_field' => '3'];

		$route = new Item(Item::TYPE_TRACKER_FIELD, $from, $routerDetails, 'Custom route test', 1, 0);

		$errors = $route->validate();
		$this->assertEmpty($errors);

		$route->save();

		self::$routes[] = $route->id;

		$this->assertNotEmpty($route->id);
	}

	/**
	 * @dataProvider getTrackerFieldRouteData
	 */
	public function testAddValidTrackerFieldCustomRoute($from, $routerDetails, $expectError)
	{
		$route = new Item(Item::TYPE_TRACKER_FIELD, $from, $routerDetails, 'Custom route test', 1, 0);

		$errors = $route->validate();

		$this->assertEquals($expectError, ! empty($errors));
	}

	public function getTrackerFieldRouteData()
	{
		return [
			[
				'|^test-user-(\w+)$|',
				['tracker' => '2'],
				true
			],
			[
				'',
				['tracker' => '2', 'tracker_field' => '3'],
				true
			],
			[
				'|^test-user-(\w+)$|',
				['tracker_field' => '3'],
				true
			],
			[
				'|^test-user-(\w+)$|',
				['tracker' => '2', 'tracker_field' => '3'],
				false
			],
		];
	}
}
