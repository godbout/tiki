<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Theme;

use TikiLib;

/**
 * Class that handles tiki theme menus operations
 *
 * @access public
 */
class Menu
{
	/**
	 * Add or update menu and their submenus
	 *
	 * @param array $data
	 * @return bool|string
	 */
	public function addOrUpdate($data)
	{
		if (empty($data)) {
			return false;
		}

		$tikiLib = TikiLib::lib('tiki');
		$menuLib = TikiLib::lib('menu');

		$defaults = [
			'name' => '',
			'description' => '',
			'collapse' => 'collapsed',
			'icon' => '',
			'groups' => [],
			'items' => [],
			'cache' => 0,
		];
		$data = array_merge($defaults, $data);

		$position = 0;
		foreach ($data['items'] as &$item) {
			$this->optionFixItem($item, $position);
		}

		$items = [];
		$this->flatten($data['items'], $items);
		$data['items'] = $items;

		$type = 'f';
		if ($data['collapse'] == 'collapsed') {
			$type = 'd';
		} elseif ($data['collapse'] == 'expanded') {
			$type = 'e';
		}
		if (! isset($data['use_items_icons'])) {
			$data['use_items_icons'] = '';
		}
		if (! isset($data['parse'])) {
			$data['parse'] = '';
		}
		if (! isset($data['icon'])) {
			$data['icon'] = null;
		}

		$menuId = 0;
		$searchMenu = $menuLib->list_menus(0, -1, 'menuId_desc', $data['name']);
		if ($searchMenu['cant'] == 1) {
			$menuId = $searchMenu['data'][0]['menuId'];
		}
		$menuLib->replace_menu($menuId, $data['name'], $data['description'], $type, $data['icon'], $data['use_items_icons'], $data['parse']);
		$result = $tikiLib->query("SELECT MAX(`menuId`) FROM `tiki_menus`");
		$resultRow = $result->fetchRow();
		$menuId = reset($resultRow);

		if (empty($menuId)) {
			return false;
		}

		if (! empty($data['items'])) {
			foreach ($data['items'] as $item) {
				$menuOptionDefault = [
					'name' => '',
					'url' => '',
					'type' => 'o',
					'position' => 1,
					'section' => '',
					'permissions' => [],
					'groups' => [],
					'level' => 0,
					'icon' => null,
				];
				$item = array_merge($menuOptionDefault, $item);
				$menuOptionId = 0;
				$menuOption = $menuLib->get_option($menuId, $item['url']);
				if (! empty($menuOption)) {
					$menuOptionId = $menuOption;
				}
				$menuLib->replace_menu_option($menuId, $menuOptionId, $item['name'], $item['url'], $item['type'], $item['position'], $item['section'], implode(',', $item['permissions']), implode(',', $item['groups']), $item['level'], $item['icon']);
			}
		}
		return $data['name'];
	}

	/**
	 * Fix menu option items
	 *
	 * @param array $item
	 * @param int $position
	 * @param array $parent
	 * @return null
	 */
	protected function optionFixItem(&$item, &$position, $parent = null)
	{
		$position += 10;

		if (! isset($item['name'])) {
			$item['name'] = 'Unspecified';
		}
		if (! isset($item['url'])) {
			$item['url'] = 'tiki-index.php';
		}
		if (! isset($item['section'])) {
			$item['section'] = null;
		}
		if (! isset($item['level'])) {
			$item['level'] = 0;
		}
		if (! isset($item['permissions'])) {
			$item['permissions'] = [];
		}
		if (! isset($item['groups'])) {
			$item['groups'] = [];
		}
		if (! isset($item['items'])) {
			$item['items'] = [];
		}

		if (! isset($item['position'])) {
			$item['position'] = $position;
		}

		if (! isset($item['type'])) {
			$item['type'] = 's';

			if ($parent) {
				if ($parent['type'] === 's') {
					$item['type'] = 1;
				} else {
					$item['type'] = $parent['type'] + 1;
				}

				$item['level'] = $parent['level'] + 1;
				$item['permissions'] = array_unique(array_merge($parent['permissions'], $item['permissions']));
				$item['groups'] = array_unique(array_merge($parent['groups'], $item['groups']));
			}
		}

		foreach ($item['items'] as &$child) {
			$this->optionFixItem($child, $position, $item);
		}

		foreach ($item['permissions'] as &$perm) {
			if (strpos($perm, 'tiki_p_') !== 0) {
				$perm = 'tiki_p_' . $perm;
			}
		}
	}

	/**
	 * Flatten menu options with children
	 *
	 * @param array $entries
	 * @param array $list
	 * @return null
	 */
	protected function flatten($entries, &$list) // {{{
	{
		if (! empty($entries)) {
			foreach ($entries as $item) {
				$children = $item['items'];
				unset($item['items']);

				$list[] = $item;
				$this->flatten($children, $list);
			}
		}
	}
}
