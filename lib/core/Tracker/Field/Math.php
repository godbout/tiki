<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler to perform a calculation for the tracker entry.
 *
 * Letter key: ~GF~
 *
 */
class Tracker_Field_Math extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Indexable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
	private static $runner;

	public static function getTypes()
	{
		return [
			'math' => [
				'name' => tr('Mathematical Calculation'),
				'description' => tr('Perform a calculation upon saving the item based on other fields within the same item.'),
				'help' => 'Mathematical Calculation Field',
				'prefs' => ['trackerfield_math'],
				'tags' => ['advanced'],
				'default' => 'n',
				'params' => [
					'calculation' => [
						'name' => tr('Calculation'),
						'type' => 'textarea',
						'description' => tr('Calculation in the Rating Language'),
						'filter' => 'text',
						'legacy_index' => 0,
					],
					'recalculate' => [
						'name' => tr('Re-calculation event'),
						'type' => 'list',
						'description' => tr('Set this to "Indexing" to update the value during reindexing as well as when saving. Selection of indexing is useful for dynamic score fields that will not be displayed.'),
						'filter' => 'word',
						'options' => [
							'save' => tr('Save'),
							'index' => tr('Indexing'),
						],
					],
					'mirrorField' => [
						'name' => tr('Mirror field'),
						'description' => tr('Field ID from any tracker that governs the output of this calculation. Useful if you want to mimic the behavior and output of a specific field but with value coming from a calculation: e.g. currency calculations, itemlink fields.'),
						'filter' => 'int',
						'profile_reference' => 'tracker_field',
						'sort_order' => 'title',
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		if (isset($requestData[$this->getInsertId()])) {
			$value = $requestData[$this->getInsertId()];
		} else {
			$value = $this->getValue();
		}

		return [
			'value' => $value,
		];
	}

	function renderInput($context = [])
	{
		return tr('Value will be re-calculated on save. Current value: %0', $this->getValue());
	}

	function renderOutput($context = [])
	{
		$mirrorField = $this->getOption('mirrorField');
		if ($mirrorField && $mirrorField != $this->getFieldId()) {
			return TikiLib::lib('trk')->field_render_value([
				'fieldId' => $mirrorField,
				'value' => $this->getValue(),
			]);
		} else {
			return $this->getValue();
		}
	}

	function importRemote($value)
	{
		return $value;
	}

	function exportRemote($value)
	{
		return $value;
	}

	function importRemoteField(array $info, array $syncInfo)
	{
		return $info;
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$value = $this->getValue();

		if ('index' == $this->getOption('recalculate')) {
			try {
				$runner = $this->getFormulaRunner();
				$data = ['itemId' => $this->getItemId()];

				foreach ($runner->inspect() as $fieldName) {
					if (is_string($fieldName) || is_numeric($fieldName)) {
						$data[$fieldName] = $this->getItemField($fieldName);
					}
				}

				$this->prepareFieldValues($data);
				$runner->setVariables($data);

				$value = (string)$runner->evaluate();
			} catch (Math_Formula_Exception $e) {
				$value = $e->getMessage();
				trigger_error("Error in Math field calculation: ".$value, E_USER_ERROR);
			}

			if ($value !== $this->getValue()) {
				$trklib = TikiLib::lib('trk');
				$trklib->modify_field($this->getItemId(), $this->getConfiguration('fieldId'), $value);
			}
		}

		$handler = $this->getMirroredHandler();
		$out = [];

		if ($handler && $handler instanceof Tracker_Field_Indexable) {
			$out = $handler->getDocumentPart($typeFactory);
		}

		$baseKey = $this->getBaseKey();
		$out[$baseKey] = $typeFactory->sortable($value);

		return $out;
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey];
	}

	function getGlobalFields()
	{
		return [];
	}

	/**
	 * Recalculate formula after saving all other fields in the tracker item
	 * @param array $data - field values to save - passed by reference as
	 * prepareFieldValues might add ItemsList field reference values here
	 * and make them available for other Math fields in the same item, thus
	 * greatly speeding up the process.
	 */
	function handleFinalSave(array &$data)
	{
		try {
			$this->prepareFieldValues($data);
			if (! isset($data['itemId'])) {
				$data['itemId'] = $this->getItemId();
			}
			$runner = $this->getFormulaRunner();
			$runner->setVariables($data);

			return (string)$runner->evaluate();
		} catch (Math_Formula_Exception $e) {
			return $e->getMessage();
		}
	}

	function getTabularSchema()
	{
		$schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

		$permName = $this->getConfiguration('permName');
		$schema->addNew($permName, 'default')
			->setLabel($this->getConfiguration('name'))
			->setRenderTransform(function ($value) {
				return $value;
			})
			;

		return $schema;
	}

	function getFilterCollection()
	{
		$collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');
		$baseKey = $this->getBaseKey();
		$handler = $this->getMirroredHandler();

		if ($handler && $handler instanceof Tracker_Field_Filterable) {
			$sub = $handler->getFilterCollection();
			foreach ($sub->getFilters() as $subfilter) {
				$subfilter->setLabel($name);
			}
			$collection->addCloned($permName, $sub);
		} else {
			$collection->addNew($permName, 'fulltext')
				->setLabel($name)
				->setHelp(tr('Full-text search of the content of the field.'))
				->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_ft"))
				->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
					$value = $control->getValue();

					if ($value) {
						$query->filterContent($value, $baseKey);
					}
				});
			$collection->addNew($permName, 'initial')
				->setLabel($name)
				->setHelp(tr('Search for a value prefix.'))
				->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_init"))
				->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
					$value = $control->getValue();

					if ($value) {
						$query->filterInitial($value, $baseKey);
					}
				});
			$collection->addNew($permName, 'exact')
				->setLabel($name)
				->setHelp(tr('Search for a precise value.'))
				->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_em"))
				->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
					$value = $control->getValue();

					if ($value) {
						$query->filterIdentifier($value, $baseKey);
					}
				});
		}

		return $collection;
	}

	/**
	 * Helper method to prepare field values for item fields that do not store their
	 * info in database - e.g. ItemsList.
	 * @param array data to be modified
	 */
	private function prepareFieldValues(&$data)
	{
		$fieldData = ['itemId' => $this->getItemId()];
		foreach ($data as $permName => $value) {
			$field = $this->getTrackerDefinition()->getFieldFromPermName($permName);
			if ($field) {
				$fieldData[$field['fieldId']] = $value;
			}
		}
		foreach ($data as $permName => $value) {
			if (! empty($value)) {
				continue;
			}
			$field = $this->getTrackerDefinition()->getFieldFromPermName($permName);
			if (! $field || $field['type'] != 'l') {
				continue;
			}
			$handler = TikiLib::lib('trk')->get_field_handler($field, $fieldData);
			$data[$permName] = $handler->getItemValues();
		}
	}

	private function getFormulaRunner()
	{
		static $cache = [];
		$fieldId = $this->getConfiguration('fieldId');
		if (! isset($cache[$fieldId])) {
			$cache[$fieldId] = $this->getOption('calculation');
		}

		$runner = self::getRunner();

		$cache[$fieldId] = $runner->setFormula($cache[$fieldId]);

		return $runner;
	}

	public static function getRunner()
	{
		if (! self::$runner) {
			self::$runner = new Math_Formula_Runner(
				[
					'Math_Formula_Function_' => '',
					'Tiki_Formula_Function_' => '',
				]
			);
		}

		return self::$runner;
	}

	public static function resetRunner()
	{
		self::$runner = null;
	}

	private function getMirroredHandler()
	{
		$mirrorField = $this->getOption('mirrorField');
		$handler = false;

		if ($mirrorField && $mirrorField != $this->getFieldId()) {
			$field = TikiLib::lib('trk')->get_field_info($mirrorField);
			$item = TikiLib::lib('trk')->get_tracker_item($this->getItemId());
			// use calculated value as the mirrored field value to allow handler produce results based on the math calculation
			$item[$mirrorField] = $item[$this->getFieldId()];
			$handler = TikiLib::lib('trk')->get_field_handler($field, $item);
			$handler->replaceBaseKey($this->getConfiguration('permName'));
		}

		return $handler;
	}
}
