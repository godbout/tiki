<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
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
	 * @covers \Tiki\CustomRoute\CustomRoute::getShortUrl()
	 */
	public function testGetEmptyShortUrl()
	{
		$this->assertEmpty(CustomRoute::getShortUrl('wiki page', 'myShortUrlPage'));
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::getShortUrl()
	 */
	public function testGetExistingShortUrl()
	{
		$hash = CustomRoute::generateShortUrlHash();
		$objectType = 'wiki page';
		$objectId = 'myShortUrlPage-' . $hash;

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test short url route', 1, 1);
		$route->save();

		self::$routes[] = $route->id;

		$from = CustomRoute::getShortUrl($objectType, $objectId);
		$this->assertEquals($hash, $from);
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::getShortUrl()
	 */
	public function testGetInactiveShortUrl()
	{
		$hash = CustomRoute::generateShortUrlHash();
		$objectType = 'wiki page';
		$objectId = 'myShortUrlPage-' . $hash;

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test short url route', 0, 1);
		$route->save();

		self::$routes[] = $route->id;

		$from = CustomRoute::getShortUrl($objectType, $objectId);
		$this->assertEmpty($from);
	}

	/**
	 * @covers \Tiki\CustomRoute\CustomRoute::matchRoute()
	 */
	public function testMatchExistingRoute()
	{
		$hash = CustomRoute::generateShortUrlHash();
		$objectType = 'wiki page';
		$objectId = 'myShortUrlPage-' . $hash;

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test short url route', 1, 1);
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

		$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], 'Test short url route', 0, 1);
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


}
