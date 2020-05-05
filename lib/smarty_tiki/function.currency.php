<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_function_currency($params, $smarty)
{
	extract($params);

	if (! isset($amount)) {
		return tra('Parameter amount is not specified.');
	}

	if (! isset($sourceCurrency)) {
		return tra('Parameter sourceCurrency is not specified.');
	}

	$trk = TikiLib::lib('trk');
	$smarty = TikiLib::lib('smarty');

	if (is_numeric($params['date'])) {
		$date = date('Y-m-d', $params['date']);
	} elseif (! empty($params['date'])) {
		$date = date('Y-m-d', strtotime($params['date']));
	} else {
		$date = date('Y-m-d');
	}

	$conversions = [];
	if (! empty($exchangeRatesTrackerId)) {
		$rates = $trk->exchange_rates($exchangeRatesTrackerId, $date);

		$defaultCurrency = array_search(1, $rates);
		if (empty($defaultCurrency)) {
			$defaultCurrency = 'USD';
		}

		if (empty($sourceCurrency)) {
			$sourceCurrency = $defaultCurrency;
		}

		// convert amount to default currency before converting to other currencies
		$defaultAmount = $amount;
		if ($sourceCurrency != $defaultCurrency && !empty($rates[$sourceCurrency])) {
			$defaultAmount = (float)$defaultAmount / (float)$rates[$sourceCurrency];
			$conversions[$defaultCurrency] = $defaultAmount;
		}
		foreach ($rates as $currency => $rate) {
			if ($currency != $sourceCurrency) {
				$conversions[$currency] = (float)$rate * (float)$defaultAmount;
			}
		}
	}

	foreach ($params as $key => $val) {
		$smarty->assign($key, $val);
	}

	$smarty->assign('amount', $amount);
	$smarty->assign('currency', $sourceCurrency);
	$smarty->assign('conversions', $conversions);
	$smarty->assign('id', uniqid());

	return $smarty->fetch('currency_output.tpl');
}
