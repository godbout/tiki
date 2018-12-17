<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for ItemsList
 *
 * Letter key: ~l~
 *
 */
class Tracker_Field_ItemsList extends Tracker_Field_Abstract implements Tracker_Field_Exportable
{
	public static function getTypes()
	{
		return [
			'l' => [
				'name' => tr('Items List'),
				'description' => tr('Display a list of field values from another tracker that has a relation with this tracker.'),
				'readonly' => true,
				'help' => 'Items List and Item Link Tracker Fields',
				'prefs' => ['trackerfield_itemslist'],
				'tags' => ['advanced'],
				'default' => 'n',
				'params' => [
					'trackerId' => [
						'name' => tr('Tracker ID'),
						'description' => tr('Tracker from which to list items'),
						'filter' => 'int',
						'legacy_index' => 0,
						'profile_reference' => 'tracker',
					],
					'fieldIdThere' => [
						'name' => tr('Link Field ID'),
						'description' => tr('Field ID from the other tracker containing an item link pointing to the item in this tracker or some other value to be matched.'),
						'filter' => 'int',
						'legacy_index' => 1,
						'profile_reference' => 'tracker_field',
						'parent' => 'trackerId',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
					'fieldIdHere' => [
						'name' => tr('Value Field ID'),
						'description' => tr('Field ID from this tracker matching the value in the link field ID from the other tracker if the field above is not an item link. If the field chosen here is an ItemLink, Link Field ID above can be left empty.'),
						'filter' => 'int',
						'legacy_index' => 2,
						'profile_reference' => 'tracker_field',
						'parent' => 'input[name=trackerId]',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
					'displayFieldIdThere' => [
						'name' => tr('Fields to display'),
						'description' => tr('Display alternate fields from the other tracker instead of the item title'),
						'filter' => 'int',
						'separator' => '|',
						'legacy_index' => 3,
						'profile_reference' => 'tracker_field',
						'parent' => 'trackerId',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
					'displayFieldIdThereFormat' => [
						'name' => tr('Format for customising fields to display'),
						'description' => tr('Uses the translate function to replace %0 etc with the field values. E.g. "%0 any text %1"'),
						'filter' => 'text',
					],
					'sortField' => [
						'name' => tr('Sort Fields'),
						'description' => tr('Order results by one or more fields from the other tracker.'),
						'filter' => 'int',
						'separator' => '|',
						'legacy_index' => 6,
						'profile_reference' => 'tracker_field',
						'parent' => 'trackerId',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
					'linkToItems' => [
						'name' => tr('Display'),
						'description' => tr('How the link to the items should be rendered'),
						'filter' => 'int',
						'options' => [
							0 => tr('Value'),
							1 => tr('Link'),
						],
						'legacy_index' => 4,
					],
					'status' => [
						'name' => tr('Status Filter'),
						'description' => tr('Limit the available items to a selected set'),
						'filter' => 'alpha',
						'options' => [
							'opc' => tr('all'),
							'o' => tr('open'),
							'p' => tr('pending'),
							'c' => tr('closed'),
							'op' => tr('open, pending'),
							'pc' => tr('pending, closed'),
						],
						'legacy_index' => 5,
					],
				],
			],
		];
	}


	/**
	 * Get field data
	 * @see Tracker_Field_Interface::getFieldData()
	 *
	 */
	function getFieldData(array $requestData = [])
	{
		$items = $this->getItemIds();
		$list = $this->getItemLabels($items);

		$ret = [
			'value' => '',
			'items' => $list,
		];

		return $ret;
	}

	function renderInput($context = [])
	{
		if (empty($this->getOption('fieldIdHere'))) {
			return $this->renderOutput();
		} else {
			TikiLib::lib('header')->add_jq_onready(
				'
$("input[name=ins_' . $this->getOption('fieldIdHere') . '], select[name=ins_' . $this->getOption('fieldIdHere') . ']").change(function(e, initial) {
	if(initial == "initial" && $(this).data("triggered-' . $this->getInsertId() . '")) {
		return;
	}
	$(this).data("triggered-' . $this->getInsertId() . '", true);
	$.getJSON(
		"tiki-ajax_services.php",
		{
			controller: "tracker",
			action: "itemslist_output",
			field: "' . $this->getConfiguration('fieldId') . '",
			fieldIdHere: "' . $this->getOption('fieldIdHere') . '",
			value: $(this).val()
		},
		function(data, status) {
			$ddl = $("div[name=' . $this->getInsertId() . ']");
			$ddl.html(data);
			if (jqueryTiki.chosen) {
				$ddl.trigger("chosen:updated");
			}
			$ddl.trigger("change");
		}
	);
});
			'
			);
			// this is smart enough to attach only once even if multiple fields attach the same code
			TikiLib::lib('header')->add_jq_onready('
$("input[name=ins_' . $this->getOption('fieldIdHere') . '], select[name=ins_' . $this->getOption('fieldIdHere') . ']").trigger("change", "initial");
', 1);
			return '<div name="' . $this->getInsertId() . '"></div>';
		}
	}

	function renderOutput($context = [])
	{
		if (isset($context['search_render']) && $context['search_render'] == 'y') {
			$items = $this->getData($this->getConfiguration('fieldId'));
		} else {
			$items = $this->getItemIds();
		}

		$list = $this->getItemLabels($items, $context);

		// if nothing found check definition for previous list (used for output render)
		if (empty($list)) {
			$list = $this->getConfiguration('items', []);
			$items = array_keys($list);
		}

		if (isset($context['list_mode']) && $context['list_mode'] === 'csv') {
			return implode('%%%', $list);
		} else {
			return $this->renderTemplate(
				'trackeroutput/itemslist.tpl',
				$context,
				[
					'links' => (bool) $this->getOption('linkToItems'),
					'raw' => (bool) $this->getOption('displayFieldIdThere'),
					'itemIds' => implode(',', $items),
					'items' => $list,
					'num' => count($list),
				]
			);
		}
	}

	function itemsRequireRefresh($trackerId, $modifiedFields)
	{
		if ($this->getOption('trackerId') != $trackerId) {
			return false;
		}

		$displayFields = $this->getOption('displayFieldIdThere');
		if (! is_array($displayFields)) {
			$displayFields = [$displayFields];
		}

		$usedFields = array_merge(
			[$this->getOption('fieldIdThere')],
			$displayFields
		);

		$intersect = array_intersect($usedFields, $modifiedFields);

		return count($intersect) > 0;
	}

	function watchCompare($old, $new)
	{
		$o = '';
		$items = $this->getItemIds();
		$n = $this->getItemLabels($items);

		return parent::watchCompare($o, $n);	// then compare as text
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$baseKey = $this->getBaseKey();
		$items = $this->getItemIds();

		$list = $this->getItemLabels($items);
		$listtext = implode(' ', $list);

		return [
			$baseKey => $typeFactory->multivalue($items),
			"{$baseKey}_text" => $typeFactory->sortable($listtext),
		];
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		return [
			$baseKey,
			"{$baseKey}_text",
		];
	}

	function getGlobalFields()
	{
		return [];
	}

	function getTabularSchema()
	{
		$schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');

		$schema->addNew($permName, 'multi-id')
			->setLabel($name)
			->setReadOnly(true)
			->setRenderTransform(function ($value) {
				return implode(';', $value);
			})
			->setParseIntoTransform(function (& $info, $value) use ($permName) {
				$info['fields'][$permName] = $value;
			});

		$schema->addNew($permName, 'multi-name')
			->setLabel($name)
			->addQuerySource('itemId', 'object_id')
			->setReadOnly(true)
			->setRenderTransform(function ($value, $extra) {

				if (is_string($value) && empty($value)) {
					// ItemsLists have no stored value, so when called from \Tracker\Tabular\Source\TrackerSourceEntry...
					// we have to: get a copy of this field
					$field = $this->getTrackerDefinition()->getFieldFromPermName($this->getConfiguration('permName'));
					// get a new handler for it
					$factory = $this->getTrackerDefinition()->getFieldFactory();
					$handler = $factory->getHandler($field, ['itemId' => $extra['itemId']]);
					// for which we can then get the itemIds array of the "linked" items
					$value = $handler->getItemIds();
					// and then get the labels from the id's we've now found as if they were the field's data
				}

				$labels = $this->getItemLabels($value, ['list_mode' => 'csv']);
				return implode(';', $labels);
			})
			->setParseIntoTransform(function (& $info, $value) use ($permName) {
				$info['fields'][$permName] = $value;
			});

		// json format for export and import (which will recreate missing linked items)

		$fieldIdHere = $this->getOption('fieldIdHere');
		$definition = $this->getTrackerDefinition();
		$fieldHere = $definition->getField($fieldIdHere);
		$extraFieldName = "tracker_field_{$fieldHere['permName']}";

		$fieldIdThere = $this->getOption('fieldIdThere');
		$trackerIdThere = $this->getOption('trackerId');
		$trackerThere = Tracker_Definition::get($trackerIdThere);
		$fieldThere = $trackerThere->getField($fieldIdThere);
		$queryFieldName = "tracker_field_{$fieldThere['permName']}";

		if ($fieldHere['type'] === 'r' && $fieldThere['type'] !== 'r') {
			$extraFieldName .= '_text';
		}

		// cache the other tracker's items to test when importing
		$itemsThereLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
		$tiki_tracker_items = TikiDb::get()->table('tiki_tracker_items');
		$itemsThereLookup->setInit(
			function ($count) use ($tiki_tracker_items, $trackerIdThere) {
				return $tiki_tracker_items->fetchMap(
					'itemId', 'status',
					[
						'trackerId' => $trackerIdThere,
					],
					$count, 0
				);
			}
		);
		$itemsThereLookup->setLookup(
			function ($value) use ($tiki_tracker_items, $trackerIdThere) {
				return $tiki_tracker_items->fetchOne(
					'itemId', [
						'trackerId' => $trackerIdThere,
						'itemId'    => $value,
					]
				);
			}
		);

		$attributelib = TikiLib::lib('attribute');
		$unifiedsearchlib = TikiLib::lib('unifiedsearch');
		$trackerUtilities = new Services_Tracker_Utilities;

		$schema->addNew($permName, 'multi-json')
			->setLabel($name)
			// these query sources appear in the $extra array in the render transform fn
			->addQuerySource('itemId', 'object_id')
			->addQuerySource('fieldIdHere', $extraFieldName)
			->setRenderTransform(
				function ($value, $extra) use ($trackerIdThere, $queryFieldName, $unifiedsearchlib) {

					if (! empty($extra['fieldIdHere'])) {
						$content = $extra['fieldIdHere'];
					} else {
						$content = (string)$extra['itemId'];
					}

					$query = $unifiedsearchlib->buildQuery(
						[
							'type'          => 'trackeritem',
							'tracker_id'    => (string)$trackerIdThere,
							$queryFieldName => $content,
						]
					);

					$result = $query->search($unifiedsearchlib->getIndex());
					$out = [];

					if ($result->count()) {
						foreach ($result as $entry) {
							$item = Tracker_Item::fromId($entry['object_id']);
							$data = $item->getData();
							$data['fields'] = array_filter($data['fields']);
							$out[] = $data;
						}
					}

					if ($out) {
						$out = json_encode($out);
					}

					return $out;
				}
			)
			->setParseIntoTransform(
				function (& $info, $value) use ($permName, $trackerUtilities, $trackerThere, $itemsThereLookup, $attributelib, $fieldThere, $schema) {
					static $newItemsThereCreated = [];

					$data = json_decode($value, true);

					if ($data && is_array($data)) {

						foreach ($data as $row) {
							if (! empty($row['itemId'])) {

								// check the old itemId as an attribute to avoid repeat imports
								$attr = $attributelib->find_objects_with('tiki.trackeritem.olditemid', $row['itemId']);

								// not done this time?
								if (! isset($newItemsThereCreated[$row['itemId']])) {

									$item = Tracker_Item::fromInfo($row);

									// FIXME $schema here doesn't know if it's a transaction type so this never executes
									if ($schema->isImportTransaction()) {
										$trackerThereId = $trackerThere->getConfiguration('trackerId');
										if (! $item->canModify()) {
											throw new \Tracker\Tabular\Exception\Exception(tr(
												'Permission denied importing into linked tracker %0',
												$trackerThereId
											));
										}
										$errors = $trackerUtilities->validateItem($trackerThere, $item->getData());
										if ($errors) {
											throw new \Tracker\Tabular\Exception\Exception(tr(
												'Errors occurred importing into linked tracker %0',
												$trackerThereId
											));
										}
									}

									$itemData = $item->getData();

									// no item with this itemId and we didn't create it before? so let's make one!
									if (! $itemsThereLookup->get($row['itemId']) && empty($attr)) {

										// needs to be done after the new main item has been created
										if (! isset($info['postprocess'])) {
											$info['postprocess'] = [];
										}
										$info['postprocess'][] = function ($newMainItemId) use ($trackerUtilities, $trackerThere, $itemData, $fieldThere, $attributelib) {

											// fix the ItemLink there to point at our new item
											if ($fieldThere['type'] === 'r') {
												$itemData['fields'][$fieldThere['permName']] = $newMainItemId;
											}

											$newItemId = $trackerUtilities->insertItem($trackerThere, $itemData);

											if ($newItemId) {
												$newItemsThereCreated[$itemData['itemId']] = $newItemId;
												// store the old itemId as an attribute of this item so we don't import it again
												$attributelib->set_attribute(
													'trackeritem',
													$newItemId,
													'tiki.trackeritem.olditemid',
													$itemData['itemId']
												);

											} else {
												Feedback::error(
													tr(
														'Creating replacement linked item for itemId %0 for ItemsList field "%1" import failed on item #%2',
														$itemData['itemId'], $this->getConfiguration('permName'), $this->getItemId()
													)
												);
											}
										};

									} else if ($itemsThereLookup->get($row['itemId'])) {    // linked item exists, so update it
										$item = Tracker_Item::fromInfo($row);
										$itemData = $item->getData();
										$result = $trackerUtilities->updateItem($trackerThere, $itemData);
										if (! $result) {
											Feedback::error(
												tr(
													'Updating linked item for itemId %0 for ItemsList field "%1" import failed on item #%2',
													$itemData['itemId'], $this->getConfiguration('permName'), $this->getItemId()
												)
											);
										}
									}

								}

							}
						}
					}
					$info['fields'][$permName] = '';
				}
			);

		return $schema;
	}



	private function getItemIds()
	{
		$trklib = TikiLib::lib('trk');
		$trackerId = (int) $this->getOption('trackerId');

		$filterFieldIdHere = (int) $this->getOption('fieldIdHere');
		$filterFieldIdThere = (int) $this->getOption('fieldIdThere');

		$filterFieldHere = $this->getTrackerDefinition()->getField($filterFieldIdHere);
		$filterFieldThere = $trklib->get_tracker_field($filterFieldIdThere);

		$sortFieldIds = $this->getOption('sortField');
		if (is_array($sortFieldIds)) {
			$sortFieldIds = array_filter($sortFieldIds);
		} else {
			$sortFieldIds = [];
		}
		$status = $this->getOption('status', 'opc');
		$tracker = Tracker_Definition::get($trackerId);



		// note: if itemlink or dynamic item list is used, than the final value to compare with must be calculated based on the current itemid

		$technique = 'value';

		// not sure this is working
		// r = item link
		if ($tracker && $filterFieldThere && (! $filterFieldIdHere || $filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w')) {
			if ($filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w') {
				$technique = 'id';
			}
		}

		// not sure this is working
		// q = Autoincrement
		if ($filterFieldHere['type'] == 'q' && isset($filterFieldHere['options_array'][3]) && $filterFieldHere['options_array'][3] == 'itemId') {
			$technique = 'id';
		}

		if ($technique == 'id') {
			$itemId = $this->getItemId();
			if (! $itemId) {
				$items = [];
			} else {
				$items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $itemId, $status, false, $sortFieldIds);
			}
		} else {
			// when this is an item link or dynamic item list field, localvalue contains the target itemId
			$localValue = $this->getData($filterFieldIdHere);
			if (! $localValue) {
				// in some cases e.g. pretty tracker $this->getData($filterFieldIdHere) is not reliable as the info is not there
				// Note: this fix only works if the itemId is passed via the template
				$itemId = $this->getItemId();
				$localValue = $trklib->get_item_value($trackerId, $itemId, $filterFieldIdHere);
			}
			if (! $filterFieldThere && $filterFieldHere && ( $filterFieldHere['type'] === 'r' || $filterFieldHere['type'] === 'w' ) && $localValue) {
				// itemlink/dynamic item list field in this tracker pointing directly to an item in the other tracker
				return [$localValue];
			}
			// r = item link - not sure this is working
			if ($filterFieldHere['type'] == 'r' && isset($filterFieldHere['options_array'][0]) && isset($filterFieldHere['options_array'][1])) {
				$localValue = $trklib->get_item_value($filterFieldHere['options_array'][0], $localValue, $filterFieldHere['options_array'][1]);
			}

			// w = dynamic item list - localvalue is the itemid of the target item. so rewrite.
			if ($filterFieldHere['type'] == 'w') {
				$localValue = $trklib->get_item_value($trackerId, $localValue, $filterFieldIdThere);
			}
			// u = user selector, might be mulitple users so need to find multiple values
			if ($filterFieldHere['type'] == 'u' && ! empty($filterFieldHere['options_map']['multiple'])) {
				$theUsers = explode(',', $localValue);
				$items = [];
				foreach ($theUsers as $theUser) {
					$items = array_merge(
						$items,
						$trklib->get_items_list($trackerId, $filterFieldIdThere, $theUser, $status, false, $sortFieldIds)
					);
				}

				return $items;
			}
			// Skip nulls
			if ($localValue) {
				$items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $localValue, $status, false, $sortFieldIds);
			} else {
				$items = [];
			}
		}

		return $items;
	}

	/**
	 * Get value of displayfields from given array of itemIds
	 * @param array $items
	 * @param array $context
	 * @return array array of values by itemId
	 */
	private function getItemLabels($items, $context = ['list_mode' => ''])
	{
		$displayFields = $this->getOption('displayFieldIdThere');
		$trackerId = (int) $this->getOption('trackerId');
		$status = $this->getOption('status', 'opc');

		$definition = Tracker_Definition::get($trackerId);
		if (! $definition) {
			return [];
		}

		$list = [];
		$trklib = TikiLib::lib('trk');
		foreach ($items as $itemId) {
			if ($displayFields && $displayFields[0]) {
				$list[$itemId] = $trklib->concat_item_from_fieldslist(
					$trackerId,
					$itemId,
					$displayFields,
					$status,
					' ',
					isset($context['list_mode']) ? $context['list_mode'] : '',
					$this->getOption('linkToItems'),
					$this->getOption('displayFieldIdThereFormat'),
					$trklib->get_tracker_item($itemId)
				);
			} else {
				$list[$itemId] = $trklib->get_isMain_value($trackerId, $itemId);
			}
		}

		return $list;
	}

	/**
	 * Get remote items' values in an array as opposed to a string label.
	 * Useful in Math calculations where individual field values are needed.
	 * @return array associated array of field names and values
	 */
	public function getItemValues()
	{
		$displayFields = $this->getOption('displayFieldIdThere');
		$trackerId = (int) $this->getOption('trackerId');

		$definition = Tracker_Definition::get($trackerId);
		if (! $definition) {
			return [];
		}

		$itemsValues = [];

		$items = $this->getItemIds();
		foreach ($items as $itemId) {
			$item = TikiLib::lib('trk')->get_tracker_item($itemId);
			$itemValues = [];
			if ($displayFields) {
				foreach ($displayFields as $fieldId) {
					$field = $definition->getField($fieldId);
					$itemValues[$field['permName']] = isset($item[$fieldId]) ? $item[$fieldId] : '';
				}
			}
			$itemsValues[] = $itemValues;
		}

		return $itemsValues;
	}
}
