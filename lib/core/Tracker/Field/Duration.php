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
				'supported_changes' => ['DUR', 'n'],
				'params' => [
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
					'chronometer' => [
						'name' => tr('Chronometer'),
						'description' => tr('Add chronometer type of UI with start/stop and reset buttons to count elapsed time.'),
						'deprecated' => false,
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
						'legacy_index' => 8,
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$ins_id = $this->getInsertId();

		if (isset($requestData[$ins_id]) && is_array($requestData[$ins_id])) {
			$value = json_encode($requestData[$ins_id]);
		} elseif (isset($requestData[$ins_id])) {
			$value = $requestData[$ins_id];
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
		global $prefs;

		if ($prefs['vuejs_enable'] === 'n') {
			return $this->renderTemplate('trackerinput/duration.tpl', $context, [
				'amounts' => $this->denormalize(),
				'units' => array_keys($this->getFactors())
			]);
		}

		// vue.js integration
		$headerlib = TikiLib::lib('header');

		if ($prefs['vuejs_always_load'] === 'n') {
			$headerlib->add_jsfile_cdn("vendor_bundled/vendor/npm-asset/vue/dist/{$prefs['vuejs_build_mode']}");
		}

		$headerlib->add_cssfile('lib/vue/duration/styles.css');
		$headerlib->add_jsfile('vendor_bundled/vendor/moment/moment/min/moment.min.js', true);
		$headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/moment-duration-format/lib/moment-duration-format.js');
		$headerlib->add_jsfile('lib/vue/duration/store.js');
		$value = $this->getValue();
		if (!$value) {
			$value = 0;
		}
		$headerlib->add_js('
momentDurationFormatSetup(moment);
var dpStore = DurationPickerStore();
dpStore.setInitialDuration({
	value: '.$value.',
	units: '.json_encode($this->enabledUnits()).',
	chronometer: '.$this->getOption("chronometer").'
});
dpStore.setInputName('.json_encode($this->getInsertId()).');
');

		$vuejslib = TikiLib::lib('vuejs');

		$params = [
			'store' => 'dpStore'
		];

		$appHtml = $vuejslib->processVue('lib/vue/duration/DurationPicker.vue', 'DurationPicker', true, $params);
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerModal.vue', 'DurationPickerModal');
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerAmounts.vue', 'DurationPickerAmounts');
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerEditor.vue', 'DurationPickerEditor');
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerChronometer.vue', 'DurationPickerChronometer');
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerHistory.vue', 'DurationPickerHistory');
		$appHtml .= $vuejslib->processVue('lib/vue/duration/DurationPickerTitle.vue', 'DurationPickerTitle');

		return $appHtml;
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$baseKey = $this->getBaseKey();

		$out = [
			$baseKey => $typeFactory->numeric($this->getValueInSeconds()),
			$baseKey.'_text' => $typeFactory->sortable($this->humanize()),
		];

		return $out;
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey, "{$baseKey}_text"];
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
			'months' => 2629746,
			'years' => 31556952,
		];
	}

	private function denormalize() {
		$value = json_decode($this->getValue(), true);
		if (! is_array($value)) {
			$value = [];
		}
		return $value;
	}

	private function humanize() {
		$value = $this->denormalize();

		$output = '';
		foreach ($value as $unit => $amount) {
			$output .= ($output ? ', ' : '')."$amount $unit";
		}

		return $output;
	}

	private function getValueInSeconds() {
		$factors = $this->getFactors();

		$value = 0;
		foreach ($this->denormalize() as $unit => $amount) {
			if (isset($factors[$unit])) {
				$value += floatval($amount) * $factors[$unit];
			} else {
				$value += floatval($amount);
			}
		}

		return $value;
	}

	private function enabledUnits() {
		return array_reverse(
			array_values(
				array_filter(
					array_keys($this->getFactors()),
					function($unit) {
						return $this->getOption($unit);
					}
				)
			)
		);
	}
}
