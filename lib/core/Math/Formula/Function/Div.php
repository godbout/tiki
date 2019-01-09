<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Div extends Math_Formula_Function
{
	function evaluate($element)
	{
		$elements = [];

		foreach ($element as $child) {
			$elements[] = $this->evaluateChild($child);
		}

		$out = array_shift($elements);

		foreach ($elements as $element) {
			if ($element) {
				$out /= $element;
			} else {
				Feedback::warning(tr('Divide by zero on "%0"', implode(',', $elements)));
				$out = false;
			}
		}

		return $out;
	}
}
