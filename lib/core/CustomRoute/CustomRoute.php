<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\CustomRoute;

use TikiLib;

/**
 * Custom Route
 */
class CustomRoute
{
	/**
	 * Checks if the $path matches a custom route
	 *
	 * @param string $path Web URI to match
	 * @return Item|bool
	 */
	public static function matchRoute($path)
	{
		$routeLib = TikiLib::lib('custom_route');
		$routes = $routeLib->getRouteByType(null, ['type' => 'asc']);

		foreach ($routes as $row) {
			$route = new Item($row['type'], $row['from'], $row['redirect'], $row['active'], $row['id']);
			if ($match = $route->matchRoute($path)) {
				return $route;
			}
		}

		return false;
	}

	/**
	 * Perform the redirect based on $route and $path
	 * The user will be redirected either to the page (using http redirect) or to a error (404)
	 *
	 * @param Item $route
	 * @param string $path
	 */
	public static function executeRoute($route, $path)
	{
		$access = TikiLib::lib('access');
		if ($redirect = $route->getRedirectPath($path)) {
			$access->redirect($redirect);
		} else {
			$access->display_error($path, tra("Page cannot be found"), '404');
		}
	}
}
