<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Category_Controller
{
	function setUp()
	{
		global $prefs;

		if ($prefs['feature_categories'] != 'y') {
			throw new Services_Exception_Disabled('feature_categories');
		}
	}

	function action_list_categories($input)
	{
		global $prefs;

		$parentId = $input->parentId->int();
		$descends = $input->descends->int();

		if (! $parentId) {
			throw new Services_Exception_MissingValue('parentId');
		}

		$categlib = TikiLib::lib('categ');
		return $categlib->getCategories(['identifier' => $parentId, 'type' => $descends ? 'descendants' : 'children']);
	}

	function action_categorize($input)
	{
		$categId = $input->categId->int();
		$objects = (array) $input->objects->none();

		$perms = Perms::get('category', $categId);

		if (! $perms->add_objects) {
			throw new Services_Exception(tr('Permission denied'), 403);
		}

		$filteredObjects = $originalObjects = $this->convertObjects($objects);
		$util =new Services_Utilities();
		if (count($originalObjects) && $util->isActionPost()) {
			//first determine if objects are already in the category
			$categlib = TikiLib::lib('categ');
			$inCategory = [];
			foreach ($originalObjects as $key => $object) {
				$objCategories = $categlib->get_object_categories($object['type'], $object['id']);
				if (in_array($categId, $objCategories)) {
					$inCategory[] = $object;
					unset($filteredObjects[$key]);
				}
			}
			//provide appropriate feedback for objects already in category
			if ($inCount = count($inCategory)) {
				$msg = $inCount === 1 ? tr('No change made for one object already in the category')
					: tr('No change made for %0 objects already in the category', $inCount);
				Feedback::note($msg);
			}
			//now add objects to the category
			if (count($filteredObjects)) {
				$return = $this->processObjects('doCategorize', $categId, $filteredObjects);
				$count = isset($return['objects']) ? count($return['objects']) : 0;
				if ($count) {
					$msg = $count === 1 ? tr('One object added to category')
						: tr('%0 objects added to category', $count);
					Feedback::success($msg);
				} else {
					Feedback::error(tr('No objects added to category'));
				}
				return $return;
			} else {
				//this code is reached when all objects selected were already in the category
				return [
					'categId'	=> $categId,
					'objects'	=> $objects,
					'count'		=> 'unchanged'
				];
			}
		} else {
			return [
				'categId' => $categId,
				'objects' => $objects,
			];
		}
	}

	function action_uncategorize($input)
	{
		$categId = $input->categId->digits();
		$objects = (array) $input->objects->none();

		$perms = Perms::get('category', $categId);

		if (! $perms->remove_objects) {
			throw new Services_Exception(tr('Permission denied'), 403);
		}

		$filteredObjects = $originalObjects = $this->convertObjects($objects);
		$util =new Services_Utilities();
		if (count($originalObjects) && $util->isActionPost()) {
			//first determine if objects are already not in the category
			$categlib = TikiLib::lib('categ');
			$notInCategory = [];
			foreach ($originalObjects as $key => $object) {
				$objCategories = $categlib->get_object_categories($object['type'], $object['id']);
				if (! in_array($categId, $objCategories)) {
					$notInCategory[] = $object;
					unset($filteredObjects[$key]);
				}
			}
			//provide appropriate feedback for objects already not in category
			if ($notCount = count($notInCategory)) {
				$msg = $notCount === 1 ? tr('No change made for one object not in the category')
					: tr('No change made for %0 objects not in the category', $notCount);
				Feedback::note($msg);
			}
			//now uncategorize objects that are in the category
			if (count($filteredObjects)) {
				$return = $this->processObjects('doUncategorize', $categId, $filteredObjects);
				$count = isset($return['objects']) ? count($return['objects']) : 0;
				if ($count) {
					$msg = $count === 1 ? tr('One object removed from category')
						: tr('%0 objects removed from category', $count);
					Feedback::success($msg);
				} else {
					Feedback::error(tr('No objects removed from category'));
				}
				return $return;
			} else {
				//this code is reached when all objects selected were already not in the category
				return [
					'categId'	=> $categId,
					'objects'	=> $objects,
					'count'		=> 'unchanged'
				];
			}
		} else {
			return [
				'categId' => $categId,
				'objects' => $objects,
			];
		}
	}

	function action_select($input)
	{
		$categlib = TikiLib::lib('categ');
		$objectlib = TikiLib::lib('object');
		$smarty = TikiLib::lib('smarty');

		$type = $input->type->text();
		$object = $input->object->text();

		$perms = Perms::get($type, $object);
		if (! $perms->modify_object_categories) {
			throw new Services_Exception_Denied('Not allowed to modify categories');
		}

		$input->replaceFilter('subset', 'int');
		$subset = $input->asArray('subset', ',');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$smarty->loadPlugin('smarty_modifier_sefurl');
			$name = $objectlib->get_title($type, $object);
			$url = smarty_modifier_sefurl($object, $type);
			$targetCategories = (array) $input->categories->int();
			$count = $categlib->update_object_categories($targetCategories, $object, $type, '', $name, $url, $subset, false);
		}

		$categories = $categlib->get_object_categories($type, $object);
		return [
			'subset' => implode(',', $subset),
			'categories' => array_combine(
				$subset,
				array_map(
					function ($categId) use ($categories) {
						return [
							'name' => TikiLib::lib('object')->get_title('category', $categId),
							'selected' => in_array($categId, $categories),
						];
					},
					$subset
				)
			),
		];
	}

	private function processObjects($function, $categId, $objects)
	{
		$tx = TikiDb::get()->begin();

		foreach ($objects as & $object) {
			$type = $object['type'];
			$id = $object['id'];

			$object['catObjectId'] = $this->$function($categId, $type, $id);
		}

		$tx->commit();

		$categlib = TikiLib::lib('categ');
		$category = $categlib->get_category((int) $categId);

		return [
			'categId' => $categId,
			'count' => $category['objects'],
			'objects' => $objects,
		];
	}

	private function doCategorize($categId, $type, $id)
	{
		$categlib = TikiLib::lib('categ');
		return $categlib->categorize_any($type, $id, $categId);
	}

	private function doUncategorize($categId, $type, $id)
	{
		$categlib = TikiLib::lib('categ');
		if ($oId = $categlib->is_categorized($type, $id)) {
			$result = $categlib->uncategorize($oId, $categId);
			return $oId;
		}
		return 0;
	}

	private function convertObjects($objects)
	{
		$out = [];
		foreach ($objects as $object) {
			$object = explode(':', $object, 2);

			if (count($object) == 2) {
				list($type, $id) = $object;
				$objectPerms = Perms::get($type, $id);

				if ($objectPerms->modify_object_categories) {
					$out[] = ['type' => $type, 'id' => $id];
				}
			}
		}

		return $out;
	}
}
