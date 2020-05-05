<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for currency field
 *
 * Letter key: ~b~
 *
 */
class Tracker_Field_Currency extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
	public static function getTypes()
	{
		return [
			'b' => [
				'name' => tr('Currency Field'),
				'description' => tr('Provide a one-line field for numeric input only. Prepended or appended values may be alphanumeric.'),
				'help' => 'Currency Amount Tracker Field',
				'prefs' => ['trackerfield_currency'],
				'tags' => ['basic'],
				'default' => 'n',
				'supported_changes' => ['d', 'D', 'R', 'M', 't', 'a', 'n', 'q', 'b'],
				'params' => [
					'samerow' => [
						'name' => tr('Same Row'),
						'description' => tr('Displays the next field on the same line.'),
						'deprecated' => false,
						'filter' => 'int',
						'default' => 1,
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'legacy_index' => 0,
					],
					'size' => [
						'name' => tr('Display Size'),
						'description' => tr('Visible size of the field, in characters. Does not affect the numeric length.'),
						'filter' => 'int',
						'default' => 7,
						'legacy_index' => 1,
					],
					'prepend' => [
						'name' => tr('Prepend'),
						'description' => tr('Text to be displayed in front of the currency amount.'),
						'filter' => 'text',
						'default' => '',
						'legacy_index' => 2,
					],
					'append' => [
						'name' => tr('Append'),
						'description' => tr('Text to be displayed after the numeric value.'),
						'filter' => 'text',
						'default' => '',
						'legacy_index' => 3,
					],
					'locale' => [
						'name' => tr('Locale'),
						'description' => tr('Set locale for currency formatting, for example en_US or en_US.UTF-8 or en_US.ISO-8559-1. Default is en_US.'),
						'filter' => 'text',
						'default' => 'en_US',
						'legacy_index' => 4,
					],
					'currency' => [
						'name' => tr('Currency'),
						'description' => tr('A custom alphanumeric currency code. Not needed if locale is set and a standard code is desired. Default is USD.'),
						'filter' => 'alpha',
						'default' => 'USD',
						'legacy_index' => 5,
					],
					'symbol' => [
						'name' => tr('Symbol'),
						'description' => tr('Set whether the currency code (for example USD) or symbol (for example $) will display. Defaults to symbol.'),
						'filter' => 'alpha',
						'default' => 'n',
						'options' => [
							'i' => tr('Currency code'),
							'n' => tr('Currency symbol'),
						],
						'legacy_index' => 6,
					],
					'all_symbol' => [
						'name' => tr('First or all'),
						'description' => tr('Set whether the currency code or symbol will be displayed against all amounts or only the first amount.'),
						'filter' => 'int',
						'default' => 1,
						'options' => [
							0 => tr('First item only'),
							1 => tr('All'),
						],
						'legacy_index' => 7,
					],
					'currencyTracker' => [
						'name' => tr('Currency Tracker'),
						'description' => tr('Tracker containing available currencies and exchange rates.'),
						'filter' => 'int',
						'legacy_index' => 8,
						'profile_reference' => 'tracker',
					],
					'dateFieldId' => [
						'name' => tr('Date Field ID'),
						'description' => tr('Currency conversions will be performed based on a date in another field in this tracker rather than the current date. This is usually the date of the transaction.'),
						'filter' => 'int',
						'legacy_index' => 9,
						'profile_reference' => 'tracker_field',
						'parent' => 'input[name=trackerId]',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$ins_id = $this->getInsertId();
		if (isset($requestData[$ins_id])) {
			$amount = $requestData[$ins_id];
			$currency = $requestData[$ins_id.'_currency'] ?? '';
		} elseif (preg_match('/^([\d\.]*)([A-Za-z]*)?$/', $this->getValue(), $m)) {
			$amount = $m[1];
			$currency = $m[2];
		} else {
			$amount = $this->getValue();
			$currency = '';
		}

		$dateFieldId = $this->getOption('dateFieldId');
		if ($dateFieldId) {
			$date = TikiLib::lib('trk')->get_item_value($this->getConfiguration('trackerId'), $this->getItemId(), $dateFieldId);
		} else {
			$date = null;
		}

		return [
			'value' => $amount.$currency,
			'amount' => $amount,
			'currency' => $currency,
			'date' => $date,
		];
	}

	function renderInnerOutput($context = [])
	{
		$smarty = TikiLib::lib('smarty');

		$data = $this->getFieldData();

		$smarty->loadPlugin('smarty_function_currency');
		return smarty_function_currency(
			[
				'amount' => $data['amount'],
				'sourceCurrency' => $data['currency'],
				'exchangeRatesTrackerId' => $this->getOption('currencyTracker'),
				'date' => $data['date'],
				'prepend' => $this->getOption('prepend'),
				'append' => $this->getOption('append'),
				'locale' => $this->getOption('locale'),
				'defaultCurrency' => $this->getOption('currency'),
				'symbol' => $this->getOption('symbol'),
				'allSymbol' => $this->getOption('all_symbol'),
				'reloff' => $context['reloff'] ?? null,
			],
			$smarty->getEmptyInternalTemplate()
		);
	}

	function renderInput($context = [])
	{
		$data = $this->getAvailableCurrencies();
		return $this->renderTemplate('trackerinput/currency.tpl', $context, $data);
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$value = $this->getValue();
		$defaultAmount = $this->convertToDefaultCurrency($this->getFieldData());
		$baseKey = $this->getBaseKey();

		$out = [
			$baseKey => $typeFactory->plaintext($value),
			"{$baseKey}_base" => $typeFactory->numeric($defaultAmount),
		];
		return $out;
	}

	function getProvidedFields() {
		$baseKey = $this->getBaseKey();
		return [$baseKey, "{$baseKey}_base"];
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
		$schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

		$permName = $this->getConfiguration('permName');
		$schema->addNew($permName, 'default')
			->setLabel($this->getConfiguration('name'))
			->setRenderTransform(function ($value) {
				return $value;
			})
			->setParseIntoTransform(function (& $info, $value) use ($permName) {
				$info['fields'][$permName] = $value;
			})
			;

		return $schema;
	}

	function getFilterCollection()
	{
		$filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');
		$baseKey = $this->getBaseKey();
		$data = $this->getAvailableCurrencies();
		$data['size'] = $this->getOption('size');

		$filters->addNew($permName, 'range')
			->setLabel($name)
			->setControl(new Tracker\Filter\Control\CurrencyRange("tf_{$permName}_range", $data))
			->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
				if ($control->hasValue()) {
					$data = $this->getFieldData();
					$data['amount'] = $control->getFrom();
					$data['currency'] = $control->getFromCurrency();
					$from = round($this->convertToDefaultCurrency($data), 2);
					$data['amount'] = $control->getTo();
					$data['currency'] = $control->getToCurrency();
					$to = round($this->convertToDefaultCurrency($data), 2);
					$query->filterNumericRange($from, $to, "{$baseKey}_base");
				}
			});

		return $filters;
	}

	private function getAvailableCurrencies() {
		$data = [];

		$trk = TikiLib::lib('trk');
		$currencyTracker = $this->getOption('currencyTracker');

		if ($currencyTracker) {
			$fieldId = $trk->get_field_by_name($currencyTracker, 'Currency');
			if ($fieldId) {
				$data['currencies'] = $trk->list_tracker_field_values($currencyTracker, $fieldId);
				sort($data['currencies']);
			} else {
				$data['error'] = 'Missing Currency field in tracker '.$currencyTracker;
			}
		}

		return $data;
	}

	private function convertToDefaultCurrency($data) {
		$trk = TikiLib::lib('trk');
		$currencyTracker = $this->getOption('currencyTracker');

		if (! empty($currencyTracker)) {
			$rates = $trk->exchange_rates($currencyTracker, $data['date']);

			$defaultCurrency = array_search(1, $rates);
			if (empty($defaultCurrency)) {
				$defaultCurrency = 'USD';
			}

			$currency = new Math_Formula_Currency($data['amount'], $data['currency'], $rates);
			return $currency->convertTo($defaultCurrency)->getAmount();
		} else {
			return 0;
		}
	}
}
