<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Action_TrackerItemModify implements Search_Action_Action
{
	function getValues()
	{
		return [
			'object_type' => true,
			'object_id' => true,
			'field' => true,
			'value' => false,
			'calc' => false,
			'add' => false,
			'remove' => false,
			'aggregate_fields' => false,
			'method' => false,
			'ignore_errors' => false,	// ignore replaceItem errors such as isMandatory and validation
		];
	}

	function validate(JitFilter $data)
	{
		$object_type = $data->object_type->text();
		$object_id = $data->object_id->int();
		$field = $data->field->word();
		$value = $data->value->text();
		$calc = $data->calc->text();
		$add = $data->add->text();
		$remove = $data->remove->text();
		$method= $data->method->text();
		$aggregateFields = $data->aggregate_fields->none();

		if ($aggregateFields && $object_type != 'aggregate') {
			throw new Search_Action_Exception(tr('Cannot apply tracker_item_modify action to an aggregation type %0.', $object_type));
		}

		if (! $aggregateFields && $object_type != 'trackeritem') {
			throw new Search_Action_Exception(tr('Cannot apply tracker_item_modify action to an object type %0.', $object_type));
		}

		$trklib = TikiLib::lib('trk');

		if ($aggregateFields) {
			foreach ($aggregateFields as $agField => $_) {
				if (! $trklib->get_field_by_perm_name(str_replace('tracker_field_', '', $agField))) {
					throw new Search_Action_Exception(tr('Tracker field %0 not found.', $agField));
				}
			}
			if (! $trklib->get_field_by_perm_name($field)) {
				throw new Search_Action_Exception(tr('Tracker field %0 not found.', $field));
			}
		} else {
			$info = $trklib->get_item_info($object_id);
			if (! $info) {
				throw new Search_Action_Exception(tr('Tracker item %0 not found.', $object_id));
			}
			$definition = Tracker_Definition::get($info['trackerId']);
			if (! $definition->getFieldFromPermName($field)) {
				throw new Search_Action_Exception(tr('Tracker field %0 not found for tracker %1.', $field, $info['trackerId']));
			}
		}

		if (empty($value) && empty($calc) && empty($add) && empty($remove) && empty($method)) {
			throw new Search_Action_Exception(tr('tracker_item_modify action missing value, calc, add or remove parameter.'));
		}

		return true;
	}

	function execute(JitFilter $data)
	{
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
			$ok = true;
			foreach ($result as $entry) {
				if (! $this->executeOnItem($entry['object_id'], $data)) {
					$ok = false;
				}
			}
			$executed = $ok;
		} else {
			$executed = $this->executeOnItem($object_id, $data);
		}

		return $executed;
	}

	function requiresInput(JitFilter $data)
	{
		if (empty($data->value->text()) && empty($data->calc->text()) && empty($data->add->text()) && empty($data->remove->text())) {

			// return data for the call to fetch_item_field
			$permName = $data->field->text();
			$field = TikiLib::lib('trk')->get_field_by_perm_name($permName);

			if (! $field) {
				Feedback::error(tr('Field %0 not found on Action TrackerItemModify', $permName));
				return [];
			} else {
				return ['fieldId' => $field['fieldId'], 'trackerId' => $field['trackerId']];
			}
		}
	}

	private function executeOnItem($object_id, $data)
	{
		$field = $data->field->word();
		$value = $data->value->text();
		$calc = $data->calc->text();
		$add = $data->add->text();
		$remove = $data->remove->text();
		$method = $data->method->text();
		$ignore_errors = $data->ignore_errors->text() === 'y';	// y/n

		$trklib = TikiLib::lib('trk');

		$info = $trklib->get_tracker_item($object_id);
		$definition = Tracker_Definition::get($info['trackerId']);

		if (! empty($calc)) {
			$runner = new Math_Formula_Runner(
				[
					'Math_Formula_Function_' => '',
					'Tiki_Formula_Function_' => '',
				]
			);
			try {
				$runner->setFormula($calc);
				$data = ['itemId' => $object_id];
				foreach ($runner->inspect() as $fieldName) {
					if (is_string($fieldName) || is_numeric($fieldName)) {
						$data[$fieldName] = $trklib->field_render_value(['trackerId' => $info['trackerId'], 'permName' => $fieldName, 'item' => $info, 'process' => 'y']);
					}
				}
				$runner->setVariables($data);
				$value = $runner->evaluate();
			} catch (Math_Formula_Exception $e) {
				throw new Search_Action_Exception(tr('Error applying tracker_item_modify calc formula to item %0: %1', $object_id, $e->getMessage()));
			}
		}

		$fieldInfo = $definition->getField($field);
		$handler = $definition->getFieldFactory()->getHandler($fieldInfo, $info);

		if (! empty($add)) {
			$value = $handler->addValue($add);
		}

		if (! empty($remove)) {
			$value = $handler->removeValue($remove);
		}

		if (empty($add) && empty($remove)) {
			if(is_string($value)) {
				$value = ['ins_'.$fieldInfo['fieldId'] => $value];
			}
			$data = $handler->getFieldData($value);
			$value = $data['value'];

			switch ($method) {
				case 'add':
					$values = explode(',', $value);
					$value = '';
					foreach ($values as $val) {
						$value .= $handler->addValue($val) . ',';
					}
					$values = explode(',', $value);
					$value = implode(',', array_unique(array_filter($values)));
					break;
				case 'remove':
					$values = explode(',', $value);
					$value = '';
					foreach ($values as $val) {
						// FIXME only the last value gets removed
						$value = $handler->removeValue($val) . ',';
					}
					$values = explode(',', $value);
					$value = implode(',', array_unique(array_filter($values)));
					break;
			}
		}

		$utilities = new Services_Tracker_Utilities;
		return $utilities->updateItem(
			$definition,
			[
				'itemId' => $object_id,
				'status' => $info['status'],
				'fields' => [
					$field => $value,
				],
				'validate' => ! $ignore_errors,
			]
		);
	}
}
