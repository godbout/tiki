<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_Relation extends Tracker_Field_Abstract
{
	const OPT_RELATION = 'relation';
	const OPT_FILTER = 'filter';
	const OPT_READONLY = 'readonly';
	const OPT_INVERT = 'invert';

	static $refreshedTargets = [];

	public static function getTypes()
	{
		return [
			'REL' => [
				'name' => tr('Relations'),
				'description' => tr('Allow arbitrary relations to be created between the trackers and other objects in the system.'),
				'prefs' => ['trackerfield_relation'],
				'tags' => ['advanced'],
				'default' => 'n',
				'help' => 'Relations Tracker Field',
				'params' => [
					'relation' => [
						'name' => tr('Relation'),
						'description' => tr('Relation qualifier. Must be a three-part qualifier containing letters and separated by dots.'),
						'filter' => 'attribute_type',
						'legacy_index' => 0,
					],
					'filter' => [
						'name' => tr('Filter'),
						'description' => tr('URL-encoded list of filters to be applied on object selection.'),
						'filter' => 'url',
						'legacy_index' => 1,
						'profile_reference' => 'search_urlencoded',
					],
					'format' => [
						'name' => tr('Format'),
						'description' => tr('Customize display of search results of object selection. Default is {title} listing the object title. Note that including other fields in the format will make search look up exactly those fields intead of the title field.'),
						'filter' => 'text',
					],
					'readonly' => [
						'name' => tr('Read-only'),
						'description' => tr('Only display the incoming relations instead of manipulating them.'),
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'legacy_index' => 2,
					],
					'invert' => [
						'name' => tr('Include Invert'),
						'description' => tr('Include invert relations in the list'),
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'legacy_index' => 3,
					],
					'display' => [
						'name' => tr('Display'),
						'description' => tr('Control how the relations are displayed in view mode'),
						'filter' => 'word',
						'options' => [
							'list' => tr('List'),
							'count' => tr('Count'),
							'toggle' => tr('Count with toggle for list'),
						],
						'legacy_index' => 4,
					],
					'refresh' => [
						'name' => tr('Force Refresh'),
						'description' => tr('Re-save related tracker items.'),
						'filter' => 'alpha',
						'options' => [
							'' => tr('No'),
							'save' => tr('On Save'),
						],
					],
					'parentFilter' => [
						'name' => tr('Extra Filter Field'),
						'description' => tr('Filter objects by value of another field. Use a jQuery seletor to target the element in the page.'),
						'filter' => 'text',
					],
					'parentFilterKey' => [
						'name' => tr('Extra Filter Key'),
						'description' => tr('Key to filter objects by using the value from the Extra Filter Field above.'),
						'filter' => 'text',
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$insertId = $this->getInsertId();

		$data = [];
		if (! $this->getOption(self::OPT_READONLY) && isset($requestData[$insertId])) {
			$selector = TikiLib::lib('objectselector');
			$entries = $selector->readMultiple($requestData[$insertId]);
			$data = array_map('strval', $entries);
		} else {
			$relation = $this->getOption(self::OPT_RELATION);
			$relations = TikiLib::lib('relation')->get_relations_from('trackeritem', $this->getItemId(), $relation);
			foreach ($relations as $rel) {
				$data[] = $rel['type'] . ':' . $rel['itemId'];
			}
			if ($this->getOption(self::OPT_INVERT)) {
				$relations = TikiLib::lib('relation')->get_relations_to('trackeritem', $this->getItemId(), $relation);
				foreach ($relations as $rel) {
					$data[] = $rel['type'] . ':' . $rel['itemId'];
				}
			}
			$data = array_unique($data);
		}

		return [
			'value' => implode("\n", $data),
			'relations' => $data,
		];
	}

	function addValue($value) {
		$existing = explode("\n", $this->getValue());
		if (! in_array($value, $existing)) {
			$existing[] = $value;
		}
		return implode("\n", $existing);
	}

	function removeValue($value) {
		$existing = explode("\n", $this->getValue());
		$existing = array_filter($existing, function($v) use ($value) {
			return $v != $value;
		});
		return implode("\n", $existing);
	}

	function renderInput($context = [])
	{
		if ($this->getOption(self::OPT_READONLY)) {
			return tra('Read-only');
		}

		$labels = [];
		foreach ($this->getConfiguration('relations') as $rel) {
			list($type, $id) = explode(':', $rel, 2);
			$labels[$rel] = TikiLib::lib('object')->get_title($type, $id, $this->getOption('format'));
		}

		$filter = $this->buildFilter();

		if (isset($filter['tracker_id']) &&
				$this->getConfiguration('trackerId') == $filter['tracker_id'] &&
				! isset($filter['object_id']) && $this->getItemId()) {
			$filter['object_id'] = 'NOT ' . $this->getItemId();	// exclude this item if we are related to the same tracker_id
		}

		return $this->renderTemplate(
			'trackerinput/relation.tpl',
			$context,
			[
				'labels' => $labels,
				'filter' => $filter,
				'format' => $this->getOption('format'),
				'parent' => $this->getOption('parentFilter'),
				'parentkey' => $this->getOption('parentFilterKey'),
			]
		);
	}

	function renderOutput($context = [])
	{
		if ($context['list_mode'] === 'csv') {
			$fieldId = $this->getConfiguration('fieldId');
			if (! empty($fieldId)) {
				$itemData = $this->getData($fieldId);
				$items = preg_split('/[\s]+/', $itemData);

				$returnValue = '';
				foreach ($items as $itemValue) {
					$itemFieldValue = explode(':', $itemValue);
					if (! empty($itemFieldValue[0]) && ! empty($itemFieldValue[1])) {
						$objectLib = TikiLib::lib('object');
						$value = $objectLib->get_title($itemFieldValue[0], $itemFieldValue[1]);
					} else {
						$value = $itemValue;
					}

					$returnValue .= ! empty($returnValue) ? ', ' . $value : $value;
				}

				return ! empty($returnValue) ? $returnValue : $itemData;
			}
			return $this->getConfiguration('value');
		} elseif ($context['list_mode'] === 'text') {
			return implode(
				"\n",
				array_map(
					function ($identifier) {
						list($type, $object) = explode(':', $identifier, 2);
						return TikiLib::lib('object')->get_title($type, $object, $this->getOption('format'));
					},
					$this->getConfiguration('relations')
				)
			);
		} else {
			$display = $this->getOption('display');
			if (! in_array($display, ['list', 'count', 'toggle'])) {
				$display = 'list';
			}

			$relations = $this->getConfiguration('relations');
			foreach ($relations as $key => $identifier) {
				list($type, $object) = explode(':', $identifier, 2);
				if ($type != 'trackeritem') {
					continue;
				}
				$item = Tracker_Item::fromId($object);
				if ($item && !$item->canView()) {
					unset($relations[$key]);
				}
			}
			$relations = array_values($relations);

			return $this->renderTemplate(
				'trackeroutput/relation.tpl',
				$context,
				[
					'display' => $display,
					'relations' => $relations,
					'format' => $this->getOption('format')
				]
			);
		}
	}

	function handleSave($value, $oldValue)
	{
		if ($value) {
			$target = explode("\n", trim($value));
		} else {
			$target = [];
		}

		// saved items should not refresh themselves later => solves odd issues with relation disappearing
		self::$refreshedTargets[] = 'trackeritem:' . $this->getItemId();

		if ($this->getOption(self::OPT_READONLY)) {
			if ($this->getOption('refresh') == 'save') {
				$this->prepareRefreshRelated($target);
			}

			return [
				'value' => $value,
			];
		}

		$relationlib = TikiLib::lib('relation');
		$current = $relationlib->get_relations_from('trackeritem', $this->getItemId(), $this->getOption(self::OPT_RELATION));
		$map = [];
		foreach ($current as $rel) {
			$key = $rel['type'] . ':' . $rel['itemId'];
			$id = $rel['relationId'];
			$map[$key] = $id;
		}
		if ($this->getOption(self::OPT_INVERT)) {
			$current = $relationlib->get_relations_to('trackeritem', $this->getItemId(), $this->getOption(self::OPT_RELATION));
			foreach ($current as $rel) {
				$key = $rel['type'] . ':' . $rel['itemId'];
				$id = $rel['relationId'];
				$map[$key] = $id;
			}
		}

		$toRemove = array_diff(array_keys($map), $target);
		$toAdd = array_diff($target, array_keys($map));

		foreach ($toRemove as $v) {
			$id = $map[$v];
			$relationlib->remove_relation($id);
		}

		foreach ($toAdd as $key) {
			list($type, $id) = explode(':', $key, 2);

			if ($type == 'trackeritem' && ! TikiLib::lib('trk')->get_item_info($id)) {
				continue;
			}

			$relationlib->add_relation($this->getOption(self::OPT_RELATION), 'trackeritem', $this->getItemId(), $type, $id);
		}

		if ($this->getOption('refresh') == 'save') {
			$this->prepareRefreshRelated(array_merge($target, $toRemove));
		}

		return [
			'value' => $value,
		];
	}

	function watchCompare($old, $new)
	{
	}

	/**
	 * Update existing data in relations table when changing the relation name.
	 * Used when updating field options.
	 *
	 * @param $params - array of field options
	 */
	public function convertFieldOptions($params)
	{
		if (empty($params['relation'])) {
			return;
		}
		if ($params['relation'] != $this->getOption(self::OPT_RELATION)) {
			$relationlib = TikiLib::lib('relation');
			$relationlib->update_relation($this->getOption(self::OPT_RELATION), $params['relation']);
		}
	}

	public function handleFieldSave($data)
	{
		$trackerId = $this->getConfiguration('trackerId');
		$options = json_decode($data['options'], true);

		if (preg_match("/tracker_id=[^&]*{$trackerId}/", $options['filter']) && $options['invert'] && $options['refresh']) {
			Feedback::warning(tr('Self-related fields with Include Invert option set to Yes should not have Force Refresh option on save.'));
		}
	}

	/**
	 * When Relation field is removed, clean up the relations table.
	 */
	public function handleFieldRemove()
	{
		$trackerId = $this->getTrackerDefinition()->getConfiguration('trackerId');
		$relationlib = TikiLib::lib('relation');
		$relationlib->remove_relation_type($this->getOption(self::OPT_RELATION), $trackerId);
	}

	private function prepareRefreshRelated($target)
	{
		$itemId = $this->getItemId();
		// After saving the field, bind a temporary event on save to refresh child elements

		TikiLib::events()->bind('tiki.trackeritem.save', function ($args) use ($itemId, $target) {
			if ($args['type'] == 'trackeritem' && $args['object'] == $itemId) {
				$utilities = new Services_Tracker_Utilities;

				foreach ($target as $key) {
					if (in_array($key, self::$refreshedTargets)) {
						continue;
					}
					self::$refreshedTargets[] = $key;

					list($type, $id) = explode(':', $key, 2);

					if ($type == 'trackeritem') {
						$utilities->resaveItem($id);
					}
				}
			}
		});
	}

	private function buildFilter()
	{
		parse_str($this->getOption(self::OPT_FILTER), $filter);
		return $filter;
	}

	public static function syncRelationAdded($args)
	{
		if ($args['sourcetype'] == 'trackeritem') {
			// It should be a forward relation
			$relation = $args['relation'];
			$trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['sourceobject']);
			if (! $trackerId) {
				return;
			}
			$definition = Tracker_Definition::get($trackerId);
			if (! $definition) {
				return;
			}
			if ($fieldId = $definition->getRelationField($relation)) {
				$itemId = $args['sourceobject'];
				$value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
				$other = $args['type'] . ':' . $args['object'];
				if (! in_array($other, $value)) {
					$value[] = $other;
				}
				if ($value != $old_value) {
					$value = implode("\n", $value);
					TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
				}
			}
		}
		if ($args['type'] == 'trackeritem') {
			// It should be an invert relation
			$relation = $args['relation'] . '.invert';
			$trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['object']);
			if (! $trackerId) {
				return;
			}
			$definition = Tracker_Definition::get($trackerId);
			if (! $definition) {
				return;
			}
			if ($fieldId = $definition->getRelationField($relation)) {
				$itemId = $args['object'];
				$value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
				$other = $args['sourcetype'] . ':' . $args['sourceobject'];
				if (! in_array($other, $value)) {
					$value[] = $other;
				}
				if ($value != $old_value) {
					$value = implode("\n", $value);
					TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
				}
			}
		}
	}

	public static function syncRelationRemoved($args)
	{
		if ($args['sourcetype'] == 'trackeritem') {
			// It should be a forward relation
			$relation = $args['relation'];
			$trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['sourceobject']);
			if (! $trackerId) {
				return;
			}
			$definition = Tracker_Definition::get($trackerId);
			if (! $definition) {
				return;
			}
			if ($fieldId = $definition->getRelationField($relation)) {
				$itemId = $args['sourceobject'];
				$value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
				$other = $args['type'] . ':' . $args['object'];
				if (in_array($other, $value)) {
					$value = array_diff($value, [$other]);
				}
				if ($value != $old_value) {
					$value = implode("\n", $value);
					TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
				}
			}
		}
		if ($args['type'] == 'trackeritem') {
			// It should be an invert relation
			$relation = $args['relation'] . '.invert';
			$trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['object']);
			if (! $trackerId) {
				return;
			}
			$definition = Tracker_Definition::get($trackerId);
			if (! $definition) {
				return;
			}
			if ($fieldId = $definition->getRelationField($relation)) {
				$itemId = $args['object'];
				$value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
				$other = $args['sourcetype'] . ':' . $args['sourceobject'];
				if (in_array($other, $value)) {
					$value = array_diff($value, [$other]);
				}
				if ($value != $old_value) {
					$value = implode("\n", $value);
					TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
				}
			}
		}
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$baseKey = $this->getBaseKey();

		$data = $this->getFieldData();
		$value = $this->getValue();

		// we don't have all the data in the field definition at this point, so just render the labels here
		$objectLib = TikiLib::lib('object');
		$format = $this->getOption('format');
		$labels = [];
		static $called = [];
		foreach ($data['relations'] as $identifier) {
			list($type, $object) = explode(':', $identifier);
			if (in_array($type.$object, $called)) {
				// prevent circular-reference calls to objectlib->get_title method as getDocumentPart is used to populate the
				// search results with field values which is called in get_title itself
				// only happens for bi-directional tracker item relation
				continue;
			}
			$called[] = $type.$object;
			$labels[] = $objectLib->get_title($type, $object, $format);
		}

		$plain = implode(', ', $labels);

		$text = '';
		$count = count($labels);
		for ($i = 0; $i < $count; $i++) {
			$text .= $labels[$i];
			if ($i === $count - 2) {
				$text .= ' ' . tr('and') . ' ';
			} else if ($i < $count - 1) {
				$text .= ', ';
			}
		}
		return [
			$baseKey => $typeFactory->sortable($value),
			"{$baseKey}_multi" => $typeFactory->multivalue(explode("\n", $value)),
			"{$baseKey}_plain" => $typeFactory->plaintext($plain),
			"{$baseKey}_text" => $typeFactory->plaintext($text),
		];
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		return [
			$baseKey,				// comma separated object_type:object_id
			"{$baseKey}_multi",		// array [object_type:object_id]
			"{$baseKey}_plain",		// comma separated formatted object titles
			"{$baseKey}_text",		// comma separated formatted object titles with "and" before the last one
		];
	}

	function getGlobalFields()
	{
		$baseKey = $this->getBaseKey();
		return ["{$baseKey}_plain" => true];	// index contents with the object titles
	}

}
