<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Mul extends Math_Formula_Function
{
	function evaluate($element)
	{
		$list = [];

		foreach ($element as $child) {
			$child = $this->evaluateChild($child);

			if (is_array($child)) {
				$list = array_merge($list, $child);
			} else {
				$list[] = $child;
			}
		}

		if (empty($list)) {
			return 1;
		} else {
			$initial = $this->firstOrApplicator($list);
			return array_reduce($list, function($carry, $item) {
				if ($carry instanceof Math_Formula_Applicator) {
					return $carry->mul($item);
				} else {
					return $carry * $item;
				}
			}, $initial);
		}
	}
}
