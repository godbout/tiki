<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class Math_Formula_Function
{
	private $callback;
	protected $suppress_error = false;

	function evaluateTemplate($element, $evaluateCallback)
	{
		$this->callback = $evaluateCallback;
		$this->suppress_error = false;
		return $this->evaluate($element);
	}

	function evaluateTemplateFull($element, $evaluateCallback)
	{
		$this->callback = $evaluateCallback;
		$this->suppress_error = true;
		if (method_exists($this, 'evaluateFull')) {
			return $this->evaluateFull($element);
		} else {
			return $this->evaluate($element);
		}
	}

	abstract function evaluate($element);

	protected function evaluateChild($child, array $extraVariables = [])
	{
		return call_user_func($this->callback, $child, $extraVariables);
	}

	protected function error($message)
	{
		throw new Math_Formula_Exception($message);
	}

	protected function firstOrApplicator(&$elements) {
		foreach ($elements as $key => $element) {
			if ($element instanceof Math_Formula_Applicator) {
				array_splice($elements, $key, 1);
				return $element;
			}
		}
		return array_shift($elements);
	}
}
