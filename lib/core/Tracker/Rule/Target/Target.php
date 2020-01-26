<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;


use Tiki\Lib\core\Tracker\Rule\Column;
use Tiki\Lib\core\Tracker\Rule\Type;

abstract class Target extends Column
{
	public function __construct($label, $type)
	{
		parent::__construct($label, $type);
	}

	public function get() {
		/** @var Type\Type $argumentType */
		$argumentType = new $this->argumentType;
		return [
			'operator_id' => $this->getId(),
			'label' => $this->label,
			'argumentType_id' => $argumentType->getId(),
		];
	}
}