<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Action_TrackerItemDelete implements Search_Action_Action
{
	function getValues()
	{
		return array(
			'object_type' => true,
			'object_id' => true,
			'aggregate_fields' => false,
		);
	}

	function validate(JitFilter $data)
	{
		$object_type = $data->object_type->text();
		$object_id = $data->object_id->int();
		$aggregateFields = $data->aggregate_fields->none();

		if ($aggregateFields && $object_type != 'aggregate') {
			throw new Search_Action_Exception(tr('Cannot apply tracker_item_delete action to an aggregation type %0.', $object_type));
		}

		if (!$aggregateFields && $object_type != 'trackeritem') {
			throw new Search_Action_Exception(tr('Cannot apply tracker_item_delete action to an object type %0.', $object_type));
		}

		$trklib = TikiLib::lib('trk');

		if ($aggregateFields) {
			foreach ($aggregateFields as $agField => $_) {
				if (! $trklib->get_field_by_perm_name(str_replace('tracker_field_', '', $agField))) {
					throw new Search_Action_Exception(tr('Tracker field %0 not found.', $agField));
				}
			}
		} else {
			$info = $trklib->get_item_info($object_id);
			if (! $info) {
				throw new Search_Action_Exception(tr('Tracker item %0 not found.', $object_id));
			}
		}

		return true;
	}

	function execute(JitFilter $data)
	{
		global $access;
		if (substr(php_sapi_name(), 0, 3) !== 'cli') {
			// TODO: this probably needs to be handled in accesslib itself
			$access->check_authenticity(tr('Are you sure you want to permanently delete this item?'));
		}

		$object_id = $data->object_id->int();
		$aggregateFields = $data->aggregate_fields->none();

		if ($aggregateFields) {
			$unifiedsearchlib = TikiLib::lib('unifiedsearch');
			$index = $unifiedsearchlib->getIndex();
			$query = new Search_Query;
			$unifiedsearchlib->initQuery($query);
			foreach ($aggregateFields as $agField => $value) {
				$query->filterIdentifier((string)$value, $agField);
			}
			$result = $query->search($index);
			foreach ($result as $entry) {
				$this->executeOnItem($entry['object_id']);
			}
		} else {
			$this->executeOnItem($object_id);
		}

		return true;
	}

	function requiresInput(JitFilter $data) {
		return false;
	}

	private function executeOnItem($object_id) {
		$trklib = TikiLib::lib('trk');

		$item = Tracker_Item::fromId($object_id);
		if ($item->canRemove()) {
			$trklib->remove_tracker_item($object_id);
		} else {
			throw new Search_Action_Exception(tr('Permission denied'));
		}
	}
}
