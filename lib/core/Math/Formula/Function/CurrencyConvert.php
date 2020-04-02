<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_CurrencyConvert extends Math_Formula_Function
{
	function evaluate($element)
	{
		if (count($element) != 2) {
			$this->error(tr('currency-convert expects exactly 2 arguments'));
		}

		$math_currency = $this->evaluateChild($element[0]);
		$target_currency = $this->evaluateChild($element[1]);

		if ($math_currency instanceof Math_Formula_Currency) {
			return $math_currency->convertTo($target_currency);
		} else {
			return $math_currency;
		}
	}
}
