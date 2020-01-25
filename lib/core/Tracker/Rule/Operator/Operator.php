<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Type;

class Operator
{
	/** @var string */
	protected $label;
	/** @var Type */
	protected $argumentType;

	public function __construct($label, $argumentType)
	{
		$this->label        = $label;
		$this->argumentType = $argumentType;
	}

	public function getId() {
		$reflection = new \ReflectionClass($this);
		$name = $reflection->getName();
		return substr($name, strrpos($name, '\\') + 1);
	}

	public function get() {
		/** @var Type $argumentType */
		$argumentType = new $this->argumentType;
		return [
			'operator_id' => $this->getId(),
			'label' => $this->label,
			'argumentType_id' => $argumentType->getId(),
		];
	}
}