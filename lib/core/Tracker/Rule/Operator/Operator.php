<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Column;
use Tiki\Lib\core\Tracker\Rule\Type\Type;

abstract class Operator extends Column
{
	public function __construct($label, $type)
	{
		parent::__construct($label, $type);
	}

	public function get() {
		/** @var Type $argumentType */
		$argumentType = new $this->type;

		return [
			'operator_id' => $this->getId(),
			'label' => $this->label,
			'argumentType_id' => $argumentType->getId(),
		];
	}
}