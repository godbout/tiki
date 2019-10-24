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
			$field = $tracker->getField(preg_replace('/^tracker_field_/', '', $this->currency_field));
		} else {
			$field = $tracker->getField(substr($name, 14));
		}
		if( !$field || $field['type'] != 'b') {
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
		if (! $currencyTracker) {
			return $value;
		}

		$rates = $trklib->exchange_rates($currencyTracker, $this->date);
		$data = $handler->getFieldData();
		$amount = $data['amount'];
		$source_currency = $data['currency'];
		$target_currency = $this->target_currency;
		$default_currency = $handler->getOption('currency');
		if (empty($default_currency)) {
			$default_currency = 'USD';
		}
		if (empty($source_currency)) {
			$source_currency = $default_currency;
		}
		if (empty($target_currency)) {
			$target_currency = $default_currency;
		}
		$currency = $source_currency;
		// convert amount to default currency before converting to other currencies
		if ($source_currency != $default_currency && !empty($rates[$source_currency])) {
			$amount = (float)$amount / (float)$rates[$source_currency];
			$currency = $default_currency;
		}
		if ($target_currency != $default_currency && !empty($rates[$target_currency])) {
			$amount = (float)$rates[$target_currency] * (float)$amount;
			$currency = $target_currency;
		}

		$locale = $handler->getOption('locale');
		if (! $locale) {
			$locale = 'en_US';
		}

		$symbol = 'n';
		if ($this->symbol != 'y') {
			$symbol = 'i';
		}

		if ($this->amount_only) {
			TikiLib::lib('smarty')->loadPlugin('smarty_modifier_number_format');
			return '~np~' . smarty_modifier_number_format($amount, 2, '.', '') . '~/np~';;
		} else {
			TikiLib::lib('smarty')->loadPlugin('smarty_modifier_money_format');
			return '~np~' . smarty_modifier_money_format($amount, $locale, $currency, '%(#10'.$symbol, 1) . '~/np~';
		}
	}
}
