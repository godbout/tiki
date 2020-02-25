<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_MoreThan extends Math_Formula_Function
{
	function evaluate($element)
	{

		if (count($element) > 2) {
			$this->error(tr('Too many arguments on more than.'));
		}

		$reference = $this->evaluateChild($element[0]);
		$mynumber = $this->evaluateChild($element[1]);

		if ($mynumber instanceof Math_Formula_Applicator) {
			if ($mynumber->moreThan($reference)) {
				return false;
			}
		} elseif ($reference instanceof Math_Formula_Applicator) {
			if ($reference->lessThan($mynumber)) {
				return false;
			}
		} else {
			if ($mynumber > $reference) {
				return false;
			}
		}

		return true;
	}
}
