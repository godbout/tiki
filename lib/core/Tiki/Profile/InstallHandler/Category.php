<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Category extends Tiki_Profile_InstallHandler
{
	private $name;
	private $description = '';
	private $parent = 0;
	private $migrateparent = 0;
	private $items = [];

	function fetchData()
	{
		if ($this->name) {
			return;
		}

		$data = $this->obj->getData();

		if (array_key_exists('name', $data)) {
			$this->name = $data['name'];
		}
		if (array_key_exists('description', $data)) {
			$this->description = $data['description'];
		}
		if (array_key_exists('parent', $data)) {
			$this->parent = $data['parent'];
		}
		if (array_key_exists('migrateparent', $data)) {
			$this->migrateparent = $data['migrateparent'];
		}
		if (array_key_exists('items', $data) && is_array($data['items'])) {
			foreach ($data['items'] as $pair) {
				if (is_array($pair) && count($pair) == 2) {
					$this->items[] = $pair;
				}
			}
		}
	}

	function canInstall()
	{
		$this->fetchData();

		if (empty($this->name)) {
			return false;
		}

		return true;
	}

	function _install()
	{
		global $tikilib;
		$this->fetchData();
		$this->replaceReferences($this->name);
		$this->replaceReferences($this->description);
		$this->replaceReferences($this->parent);
		$this->replaceReferences($this->migrateparent);
		$this->replaceReferences($this->items);
		$this->replaceReferences($this->tplGroupContainer);
		$this->replaceReferences($this->tplGroupPattern);

		$categlib = TikiLib::lib('categ');
		if ($id = $categlib->exist_child_category($this->parent, $this->name)) {
			$categlib->update_category($id, $this->name, $this->description, $this->parent, $this->tplGroupContainer, $this->tplGroupPattern);
		} else {
			$id = $categlib->add_category($this->parent, $this->name, $this->description, $this->tplGroupContainer, $this->tplGroupPattern);
		}

		if ($this->migrateparent && $from = $categlib->exist_child_category($this->migrateparent, $this->name)) {
			$categlib->move_all_objects($from, $id);
		}

		foreach ($this->items as $item) {
			list( $type, $object ) = $item;

			$type = Tiki_Profile_Installer::convertType($type);
			$object = Tiki_Profile_Installer::convertObject($type, $object);
			$categlib->categorize_any($type, $object, $id);
		}

		return $id;
	}

	/**
	 * Export categories
	 *
	 * @param Tiki_Profile_Writer $writer
	 * @param int $categId
	 * @param bool $deep
	 * @param mixed $includeObjectCallback
	 * @param bool $all
	 * @return bool
	 */
	public static function export(Tiki_Profile_Writer $writer, $categId, $deep, $includeObjectCallback, $all = false)
	{
		$categlib = TikiLib::lib('categ');

		if (isset($categId) && ! $all) {
			$listCategories = [];
			$listCategories[] = $categlib->get_category($categId);
			$error = isset($listCategories[0]) ? false : true;
		} else {
			$listCategories = $categlib->getCategories();
			$error = isset($listCategories[1]) ? false : true;
		}

		if ($error) {
			return false;
		}

		foreach ($listCategories as $category) {
			$categId = $category['categId'];

			$items = [];
			foreach ($categlib->get_category_objects($categId) as $row) {
				if ($includeObjectCallback($row['type'], $row['itemId'])) {
					$items[] = [$row['type'], $writer->getReference($row['type'], $row['itemId'])];
				}
			}

			$data = [
				'name' => $category['name'],
			];

			if (! empty($category['parentId'])) {
				$data['parent'] = $writer->getReference('category', $category['parentId']);
			}

			if (! empty($items)) {
				$data['items'] = $items;
			}

			$writer->addObject('category', $categId, $data);

			if ($deep) {
				$descendants = $categlib->get_category_descendants($categId);
				array_shift($descendants);
				foreach ($descendants as $children) {
					self::export($writer, $children, $deep, $includeObjectCallback);
				}
			}
		}

		return true;
	}

	/**
	 * Remove category
	 *
	 * @param string $category
	 * @return bool
	 */
	function remove($category)
	{
		if (! empty($category)) {
			$categlib = TikiLib::lib('categ');
			$categoryId = $categlib->get_category_id($category);
			if (! empty($categoryId) && $categlib->remove_category($categoryId)->numRows()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return category object data
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->obj->getData();
	}

	/**
	 * Get current category data
	 *
	 * @param array $category
	 * @return mixed
	 */
	public function getCurrentData($category)
	{
		$categoryName = ! empty($category['name']) ? $category['name'] : '';
		if (! empty($categoryName)) {
			$categlib = TikiLib::lib('categ');
			$categId = $categlib->get_category_id($categoryName);
			if (isset($categId)) {
				$categData = $categlib->get_category($categId);
				if (! empty($categData)) {
					return $categData;
				}
			}
		}
		return false;
	}
}
