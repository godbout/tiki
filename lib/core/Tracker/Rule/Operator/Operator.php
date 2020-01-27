<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Column;
use Tiki\Lib\core\Tracker\Rule\Type\Type;

abstract class Operator extends Column
{
	/** @var string syntax in javascript */
	private $syntax;

	public function __construct($label, $type, $syntax)
	{
		$this->syntax = $syntax;

		parent::__construct($label, $type);
	}

	/**
	 * @return string JavaScript condition syntax using %argument% as the arg placeholder
	 */
	public function getSyntax(): string
	{
		return $this->syntax;
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