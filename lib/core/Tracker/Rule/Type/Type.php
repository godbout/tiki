<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;


use Tiki\Lib\core\Tracker\Rule\Column;
use Tiki\Lib\core\Tracker\Rule\Operator\Operator;

abstract class Type extends Column
{
	/** @var array */
	protected $operators = [];

	/**
	 * Type constructor.
	 *
	 * @param string $type
	 * @param array  $operators
	 */
	public function __construct($type, array $operators)
	{
		parent::__construct('', $type);
		$this->operators = $operators;
	}

	public function get() {
		$operator_ids = array_map(function (Operator $operator) {
			return $operator->getId();
		}, $this->operators);


		return [
			'type_id' => $this->getId(),
			'operator_ids' => $operator_ids,
		];
	}
}