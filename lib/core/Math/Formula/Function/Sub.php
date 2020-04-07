<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Sub extends Math_Formula_Function
{
	function evaluate($element)
	{
		$elements = [];

		foreach ($element as $child) {
			$elements[] = $this->evaluateChild($child);
		}

		$out = array_shift($elements);
		if (! $out instanceof Math_Formula_Applicator) {
			foreach ($elements as $element) {
				if ($element instanceof Math_Formula_Applicator) {
					$out = $element->clone($out);
					break;
				}
			}
		}

		foreach ($elements as $element) {
			if ($out instanceof Math_Formula_Applicator) {
				$out = $out->sub($element);
			} elseif (is_numeric($element)) {
				$out -= $element;
			}
		}

		return $out;
	}
}
