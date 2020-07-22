<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\core\Tracker\Rule;


use Tiki\Lib\core\Tracker\Rule\Operator;

class Rules
{
	private $conditions;
	private $actions;
	private $else;

	function __construct($data)
	{
		if (is_string($data)) {
			$data = json_decode($data);
		}

		if (! isset($data->conditions) || ! isset($data->actions) || ! isset($data->else)) {
			throw new \Exception(tr('Rule creation from data failed'));
		}

		$this->conditions = $data->conditions;
		$this->actions    = $data->actions;
		$this->else       = $data->else;

	}

	public static function fromData($fieldId, $data)
	{
		return new self($data);
	}

	/**
	 * @param int    $fieldId
	 * @param string $parentSelector
	 *
	 * @return string
	 */
	public function getJavaScript($fieldId, $parentSelector = '.form-group:first') {
		$js = '';
		$operator = ' && ';
		$conditions = [];

		if ($this->conditions->logicalType_id === 'any') {	// TODO deal with 'none'
			$operator = ' || ';
		}

		foreach ($this->conditions->predicates as $predicate) {
			$conditions[] = '$("[name=\'' . $predicate->target_id . '\']:last")' . $this->getPredicateSyntax($predicate, 'Operator');
		}

		$js = "\n  if (" . implode($operator, $conditions) . ')';

		$actions = [];

		foreach($this->actions->predicates as $predicate) {
			if ($predicate->operator_id !== 'NoOp') {
				$targetSelector = "\$(\"[name='{$predicate->target_id}']\")";
				$actions[] = "    if ($targetSelector.length === 0) { console.error('Tracker Rules: element $predicate->target_id not found'); return; }";
				if (strpos($predicate->operator_id, 'Required') === false) {
					// show/hide etc needs the parent object
					$actions[] = "    $targetSelector.parents('$parentSelector')" .
						$this->getPredicateSyntax($predicate, 'Action') . ';';
				} else {
					// validation doesn't need parent
					$actions[] = "    $targetSelector" .
						$this->getPredicateSyntax($predicate, 'Action') . ';';
				}
			}
		}

		$js .= " {\n" . implode("\n", $actions) . "\n  }";

		$else = [];
		if ($this->else->predicates) {
			foreach ($this->else->predicates as $predicate) {
				if ($predicate->operator_id !== 'NoOp') {
					$targetSelector = "\$(\"[name='{$predicate->target_id}']\")";
					$else[] = "    if ($targetSelector.length === 0) { console.error('Tracker Rules: element $predicate->target_id not found'); return; }";
					if (strpos($predicate->operator_id, 'Required') === false) {
						$else[] = "    $targetSelector.parents('$parentSelector')" .
							$this->getPredicateSyntax($predicate, 'Action') . ';';
					} else {
						$else[] = "    $targetSelector" .
							$this->getPredicateSyntax($predicate, 'Action') . ';';
					}
				}
			}
			$js .= " else {\n" . implode("\n", $else) . "\n  }\n";
		} else {
			$js .= "\n";
		}

		if ($actions || $else) {
			$js = '$("[name=\'ins_' . $fieldId . '\']:last").change(function () {' . $js . "}).change();\n";
		} else {
			$js = '';
		}

		return $js;
	}

	/**
	 * @param       $predicate
	 * @param array $conditions
	 *
	 * @return array
	 */
	private function getPredicateSyntax($predicate, $parentClass)
	{
		$operatorClass = 'Tiki\\Lib\\core\\Tracker\\Rule\\' . $parentClass . '\\' . $predicate->operator_id;
		/** @var Operator\Operator $operatorObject */
		$operatorObject = new $operatorClass();
		$syntax = $operatorObject->getSyntax();
		if ($predicate->argument !== null) {
			$syntax = str_replace('%argument%', $predicate->argument, $syntax);
			$syntax = str_replace('%field%', $predicate->target_id, $syntax);
		} else if (strpos($syntax, '%argument%') !== false) {
			$syntax = str_replace('%argument%', tr('No argument for \"%0\" rule', $predicate->operator_id), $syntax);
		}

		return $syntax;
	}
}
