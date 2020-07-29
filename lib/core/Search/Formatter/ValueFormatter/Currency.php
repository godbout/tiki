<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_ValueFormatter_Currency extends Search_Formatter_ValueFormatter_Abstract
{
	private $date = null;
	private $target_currency = null;
	private $symbol = 'y';
	private $currency_field = null;
	private $amount_only = null;

	function __construct($arguments)
	{
		if (isset($arguments['date'])) {
			$this->date = $arguments['date'];
		} else {
			$this->date = null;
		}

		if (isset($arguments['target_currency'])) {
			$this->target_currency = $arguments['target_currency'];
		}

		if (isset($arguments['symbol'])) {
			$this->symbol = $arguments['symbol'];
		}

		if (isset($arguments['currency_field'])) {
			$this->currency_field = $arguments['currency_field'];
		}

		if (isset($arguments['amount_only'])) {
			$this->amount_only = $arguments['amount_only'];
		}
	}

	function render($name, $value, array $entry)
	{
		$trklib = TikiLib::lib('trk');

		$tracker = Tracker_Definition::get($entry['tracker_id']);
		if (! is_object($tracker)) {
			return $value;
		}
		
		if ($this->currency_field) {
			$field = preg_replace('/^tracker_field_/', '', $this->currency_field);
		} else {
			$field = substr($name, 14);
		}
		$field = $tracker->getField(preg_replace("/_base$/", "", $field));
		if ($field && $field['type'] == 'math') {
			$handler = $trklib->get_field_handler($field);
			if ($handler && $handler->getOption('mirrorField')) {
				$field = $trklib->get_field_info($handler->getOption('mirrorField'));
				if ($field) {
					$tracker = Tracker_Definition::get($field['trackerId']);
					$field = $tracker->getField($field['fieldId']);
				}
			}
		}
		if (!$field || $field['type'] != 'b') {
			return 'Field is not a Currency tracker field.';
		}

		if ($this->date && isset($entry[$this->date])) {
			$this->date = $entry[$this->date];
		}
		if (! $this->date) {
			$this->date = date('Y-m-d');
		} elseif(is_int($this->date)) {
			$this->date = date('Y-m-d', $this->date);
		} else {
			$this->date = date('Y-m-d', strtotime($this->date));
		}

		$field['value'] = $value;
		$handler = $trklib->get_field_handler($field);
		if (! $handler) {
			return $value;
		}

		$currencyTracker = $handler->getOption('currencyTracker');
		$data = $handler->getFieldData();

		if ($this->target_currency) {
			$rates = TikiLib::lib('trk')->exchange_rates($currencyTracker, $this->date);
			$currencyObj = new Math_Formula_Currency($data['amount'], $data['currency'], $rates);
			$currencyObj = $currencyObj->convertTo($this->target_currency);
			$amount = $currencyObj->getAmount();
			$currency = $currencyObj->getCurrency();
		} else {
			$amount = $data['amount'];
			$currency = $data['currency'];
		}

		if ($this->amount_only) {
			TikiLib::lib('smarty')->loadPlugin('smarty_modifier_number_format');
			return '~np~' . smarty_modifier_number_format($amount, 2, '.', '') . '~/np~';;
		} else {
			TikiLib::lib('smarty')->loadPlugin('smarty_function_currency');
			return smarty_function_currency(
				[
				'amount' => $amount,
				'sourceCurrency' => $currency,
				'exchangeRatesTrackerId' => $currencyTracker,
				'date' => $this->date,
				'prepend' => $handler->getOption('prepend'),
				'append' => $handler->getOption('append'),
				'locale' => $handler->getOption('locale'),
				'defaultCurrency' => $handler->getOption('currency'),
				'symbol' => $handler->getOption('symbol'),
				'allSymbol' => $handler->getOption('all_symbol'),
				],
				TikiLib::lib('smarty')->getEmptyInternalTemplate()
			);
		}
	}
}
