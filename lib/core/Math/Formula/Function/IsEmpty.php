<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_IsEmpty extends Math_Formula_Function
{
	function evaluate($element)
	{
		foreach ($element as $child) {
			try {
				$component = $this->evaluateChild($child);
			} catch (Math_Formula_Exception $e) {
				// if the child value is not in the variables (i.e. index) catch exception and return IsEmpty = true
				return true;
			}
			if (! empty($component)) {
				return false;
			}
		}

		return true;
	}
}
