<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for duration field type
 *
 * Letter key: ~DUR~
 *
 */
class Tracker_Field_Duration extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
	public static function getTypes()
	{
		return [
			'DUR' => [
				'name' => tr('Duration Field'),
				'description' => tr('Provide a convenient way to enter time duration in different units.'),
				'help' => 'Duration Tracker Field',
				'prefs' => ['trackerfield_duration'],
				'tags' => ['basic'],
				'default' => 'y',
				'supported_changes' => ['n'],
				'params' => [
					'storage_unit' => [
						'name' => tr('Storage unit'),
						'description' => tr('Store entered duration in this unit. Changing this once there are entries for this field is dangerous.'),
						'deprecated' => false,
						'filter' => 'alpha',
						'default' => 'seconds',
						'options' => [
							'seconds' => tr('Seconds'),
							'minutes' => tr('Minutes'),
							'hours' => tr('Hours'),
							'days' => tr('Days'),
							'weeks' => tr('Weeks'),
							'months' => tr('Months'),
							'years' => tr('Years'),
						],
						'legacy_index' => 0,
					],
					'seconds' => [
						'name' => tr('Seconds'),
						'description' => tr('Allow selection of seconds.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 1,
					],
					'minutes' => [
						'name' => tr('Minutes'),
						'description' => tr('Allow selection of minutes.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 1,
						'legacy_index' => 2,
					],
					'hours' => [
						'name' => tr('Hours'),
						'description' => tr('Allow selection of hours.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 1,
						'legacy_index' => 3,
					],
					'days' => [
						'name' => tr('Days'),
						'description' => tr('Allow selection of days.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 4,
					],
					'weeks' => [
						'name' => tr('Weeks'),
						'description' => tr('Allow selection of weeks.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 5,
					],
					'months' => [
						'name' => tr('Months'),
						'description' => tr('Allow selection of months.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 6,
					],
					'years' => [
						'name' => tr('Years'),
						'description' => tr('Allow selection of years.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 7,
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$ins_id = $this->getInsertId();

		if (isset($requestData[$ins_id]) && is_array($requestData[$ins_id])) {
			$factors = $this->getFactors();

			$value = 0;
			foreach ($requestData[$ins_id] as $unit => $amount) {
				if (isset($factors[$unit])) {
					$value += floatval($amount) * $factors[$unit];
				} else {
					$value += floatval($amount);
				}
			}

			if ($unit = $this->getOption('storage_unit')) {
				$value /= $factors[$unit];
			}
		} else {
			$value = $this->getValue();
		}

		return ['value' => $value];
	}

	function renderInnerOutput($context = [])
	{
		return $this->humanize();
	}

	function renderInput($context = [])
	{
		return $this->renderTemplate('trackerinput/duration.tpl', $context, [
			'amounts' => $this->denormalize(),
			'units' => array_keys($this->getFactors())
		]);
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$value = $this->getValue();
		$baseKey = $this->getBaseKey();

		$out = [
			$baseKey => $typeFactory->numeric($value),
			$baseKey.'_text' => $typeFactory->sortable($this->humanize()),
		];
		return $out;
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

	function getTabularSchema()
	{
		// TODO
	}

	function getFilterCollection()
	{
		// TODO
	}

	private function getFactors() {
		return [
			'seconds' => 1,
			'minutes' => 60,
			'hours' => 3600,
			'days' => 86400,
			'weeks' => 604800,
			'months' => 2629800,
			'years' => 31587840,
		];
	}

	private function denormalize() {
		$value = $this->getValue();
		$factors = $this->getFactors();

		$calculated = [];
		$seconds = $value * ($factors[$this->getOption('storage_unit')] ?? 1);
		foreach (array_reverse($factors) as $unit => $factor) {
			if ($this->getOption($unit)) {
				$factor = $factors[$unit] ?? 1;
				$calculated[$unit] = floor($seconds / $factor);
				$seconds -= ($calculated[$unit] * $factor);
			}
		}

		return $calculated;
	}

	private function humanize() {
		$calculated = $this->denormalize();

		$output = '';
		foreach ($calculated as $unit => $amount) {
			$output .= ($output ? ', ' : '')."$amount $unit";
		}

		return $output;
	}
}
