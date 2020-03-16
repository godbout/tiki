<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Action_TrackerItemClone implements Search_Action_Action
{
	private $cloned_object_id = null;

	function getValues()
	{
		return [
			'object_type' => true,
			'object_id' => true,
		];
	}

	function validate(JitFilter $data)
	{
		$object_type = $data->object_type->text();
		$object_id = $data->object_id->int();

		if ($object_type != 'trackeritem') {
			throw new Search_Action_Exception(tr('Cannot apply tracker_item_clone action to an object type %0.', $object_type));
		}

		$trklib = TikiLib::lib('trk');
		$info = $trklib->get_item_info($object_id);
		if (! $info) {
			throw new Search_Action_Exception(tr('Tracker item %0 not found.', $object_id));
		}

		return true;
	}

	function execute(JitFilter $data)
	{
		$object_id = $data->object_id->int();

		$itemObject = Tracker_Item::fromId($object_id);
		if (! $itemObject->canView()) {
			throw new Search_Action_Exception(tr("The item to clone isn't visible"));
		}

		$itemData = $itemObject->getData();
		$itemData['itemId'] = null;

		$newItem = Tracker_Item::newItem($itemData['trackerId']);
		if (! $newItem->canModify()) {
			throw new Search_Action_Exception(tr("You don't have permission to create new items"));
		}

		$utilities = new Services_Tracker_Utilities;
		$itemObject = $utilities->cloneItem($itemObject->getDefinition(), $itemData, $object_id, $strict = true);
		if ($itemObject) {
			$this->cloned_object_id = $itemObject->getId();
			return true;
		} else {
			return false;
		}
	}

	function requiresInput(JitFilter $data)
	{
		return false;
	}

	function changeObject($data) {
		if (empty($this->cloned_object_id)) {
			return $data;
		}
		if ($data instanceof JitFilter) {
			$dataArr = $data->asArray();
			$dataArr['object_id'] = $this->cloned_object_id;
			$data = new JitFilter($dataArr);
		} else {
			$data['object_id'] = $this->cloned_object_id;
		}
		return $data;
	}
}
