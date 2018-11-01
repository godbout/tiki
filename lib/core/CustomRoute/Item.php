<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\CustomRoute;

use \TikiLib;

/**
 * Custom route item
 */
class Item
{
	public $id;
	public $type;
	public $from;
	public $redirect;
	public $description;
	public $active;
	public $short_url;

	const TYPE_DIRECT = 'Direct';
	const TYPE_OBJECT = 'TikiObject';
	const TYPE_TRACKER_FIELD = 'TrackerField';

	/**
	 * Item constructor.
	 *
	 * @param $type
	 * @param $from
	 * @param $redirect
	 * @param $description
	 * @param int $active
	 * @param int $short_url
	 * @param null $id
	 */
	public function __construct($type, $from, $redirect, $description, $active = 1, $short_url = 0, $id = null)
	{
		$this->type = $type;
		$this->from = $from;
		$this->redirect = is_array($redirect) ? json_encode($redirect) : $redirect;
		$this->description = $description;
		$this->active = $active;
		$this->short_url = $short_url;
		$this->id = $id;
	}

	/**
	 * Save item in database
	 */
	public function save()
	{
		$routeLib = TikiLib::lib('custom_route');
		$id = $routeLib->setRoute($this->type, $this->from, $this->redirect, $this->description, $this->active, $this->short_url, $this->id);

		if ($id) {
			$this->id  = $id;
		}
	}

	/**
	 * Load a custom route by ID
	 *
	 * @param $id
	 * @return array|null|Item
	 */
	public static function load($id)
	{
		$routeLib = TikiLib::lib('custom_route');
		$details = $routeLib->getRoute($id);

		if (empty($details)) {
			return null;
		}

		return new self(
			$details['type'],
			$details['from'],
			$details['redirect'],
			$details['description'],
			$details['active'],
			$details['short_url'],
			$details['id']
		);
	}

	/**
	 * Check if a given path matches a custom route
	 *
	 * @param $path
	 * @return bool|string
	 */
	public function matchRoute($path)
	{
		switch ($this->type) {
			case self::TYPE_DIRECT:
				if ($path === $this->from) {
					return true;
				}
				break;
			case self::TYPE_OBJECT:
				if ($path === $this->from) {
					return true;
				}
				break;
			case self::TYPE_TRACKER_FIELD:
				preg_match($this->from, $path, $matches);
				if (isset($matches[1])) {
					return true;
				}
				break;
			default:
				break;
		}

		return false;
	}

	/**
	 * Attempts to determine if is viable to do an in-place redirect and return the appropriate setting (or false)
	 *
	 * @param $path
	 * @return array|bool The setting for in place redirect of false
	 */
	public function getInPlaceRoutingParameters($path)
	{
		switch ($this->type) {
			case self::TYPE_OBJECT:
				$redirectDetails = json_decode($this->redirect, true);

				$objectType = $redirectDetails['type'];
				$objectId = $redirectDetails['object'];

				break;

			case self::TYPE_TRACKER_FIELD:
				$redirectDetails = json_decode($this->redirect, true);

				preg_match($this->from, $path, $matches);

				if (! isset($matches[1])) {
					return false;
				}

				if ($redirectDetails['tracker_field'] == 'itemId') {
					$itemId = $matches[1];
				} else {
					$itemId = TikiLib::lib('trk')->get_item_id(
						$redirectDetails['tracker'],
						$redirectDetails['tracker_field'],
						$matches[1]
					);
				}

				if (empty($itemId)) {
					$itemId = '0';
				}

				$objectType = 'tracker item';
				$objectId = $itemId;

				break;

			default:
				return false;
		}

		switch ($objectType) {
			case 'article':
				$file = 'tiki-read_article.php';
				$params = ['articleId' => $objectId];
				break;

			case 'blog':
				$file = 'tiki-view_blog.php';
				$params = ['blogId' => $objectId];
				break;

			case 'forum':
				$file = 'tiki-view_forum.php';
				$params = ['forumId' => $objectId];
				break;

			case 'tracker item':
				$file = 'tiki-view_tracker_item.php';
				$params = ['itemId' => $objectId];
				break;

			case 'wiki page':
				/** @var \WikiLib $wikiLib */
				$wikiLib = TikiLib::lib('wiki');
				$pageName = $wikiLib->get_page_name_from_id($objectId);
				$pageSlug = $wikiLib->get_slug_by_page($pageName);

				if (empty($pageSlug)) {
					return false;
				}

				$file = 'tiki-index.php';
				$params = ['page' => $pageSlug];

				break;

			default:
				return false;
		}

		return [
			'file' => $file,
			'get_param' => $params
		];
	}

	/**
	 * Check if a given path matches a custom route
	 *
	 * @param $path
	 * @return bool|string
	 */
	public function getRedirectPath($path)
	{
		switch ($this->type) {
			case self::TYPE_DIRECT:
				if ($path === $this->from) {
					$redirectDetails = json_decode($this->redirect, true);
					return $redirectDetails['to'];
				}
				break;

			case self::TYPE_OBJECT:
				if ($path === $this->from) {
					$redirectDetails = json_decode($this->redirect, true);

					$type = $redirectDetails['type'];
					$objectId = $redirectDetails['object'];

					if ($type == 'wiki page') {
						/** @var \WikiLib $wikiLib */
						$wikiLib = TikiLib::lib('wiki');
						$pageName = $wikiLib->get_page_name_from_id($objectId);
						$pageSlug = $wikiLib->get_slug_by_page($pageName);

						if (empty($pageSlug)) {
							return false;
						}

						$objectId = $pageSlug;
					}

					require_once('tiki-sefurl.php');
					$smarty = TikiLib::lib('smarty');
					$smarty->loadPlugin('smarty_modifier_sefurl');
					$isExternal = TikiLib::setExternalContext(true);
					$url = smarty_modifier_sefurl($objectId, $type);
					TikiLib::setExternalContext($isExternal);

					return $url;
				}
				break;

			case self::TYPE_TRACKER_FIELD:
				preg_match($this->from, $path, $matches);

				if (! isset($matches[1])) {
					return false;
				}

				$redirectDetails = json_decode($this->redirect, true);

				$trklib = TikiLib::lib('trk');

				if ($redirectDetails['tracker_field'] == 'itemId') {
					$itemId = $matches[1];
				} else {
					$itemId = $trklib->get_item_id(
						$redirectDetails['tracker'],
						$redirectDetails['tracker_field'],
						$matches[1]
					);
				}

				if (empty($itemId)) {
					$itemId = '0';
				}

				require_once('tiki-sefurl.php');
				$smarty = TikiLib::lib('smarty');
				$smarty->loadPlugin('smarty_modifier_sefurl');
				$isExternal = TikiLib::setExternalContext(true);
				$url = smarty_modifier_sefurl($itemId, 'trackeritem');
				TikiLib::setExternalContext($isExternal);

				return $url;

				break;
			default:
				break;
		}

		return false;
	}

	/**
	 * Validate the route requirements are met.
	 *
	 * @return array
	 */
	public function validate()
	{
		$errors = [];

		if (empty($this->from)) {
			$errors[] = tr('From is required');
		}

		$routeLib = TikiLib::lib('custom_route');
		if ($routeLib->checkRouteExists($this->from, $this->id)) {
			$errors[] = tr('There is a route with the same From path already defined.');
		}

		if (empty($this->type)) {
			$errors[] = tr('Type is required');
		}

		/** @var Type $class */
		$className = 'Tiki\\CustomRoute\\Type\\' . $this->type;
		if (class_exists($className)) {
			$class = new $className();

			$params = json_decode($this->redirect, true);
			$errors += $class->validateParams($params);
		} else {
			$errors[] = tr('Selected type is not supported');
		}

		return $errors;
	}

	/**
	 * Converts the Item object into a array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'from' => $this->from,
			'params' => json_decode($this->redirect, true),
			'description' => $this->description,
			'active' => $this->active,
			'short_url' => $this->short_url,
		];
	}

	/**
	 * Returns the full short url link
	 *
	 * @return string The absolute short url link
	 * @throws \Exception
	 */
	public function getShortUrlLink()
	{
		global $base_url;

		if (! $this->short_url) {
			throw new \Exception('This custom route is not Short URL');
		}

		$link = ! empty($prefs['sefurl_short_url_base_url']) ? $prefs['sefurl_short_url_base_url'] : $base_url;
		$link = rtrim($link, '/') . '/' . $this->from;

		return $link;
	}
}
