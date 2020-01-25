<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;


use Tiki\Lib\core\Tracker\Rule\Operator\Operator;

abstract class Type
{
	/** @var string */
	protected $type_id;
	/** @var array */
	protected $operator_ids = [];

	/**
	 * Type constructor.
	 *
	 * @param string $type_id
	 * @param array  $operator_ids
	 */
	public function __construct($type_id, array $operator_ids)
	{
		$this->type_id = $type_id;
		$this->operator_ids = $operator_ids;
	}

	public function getId() {
		$reflection = new \ReflectionClass($this);
		$name = $reflection->getName();
		return substr($name, strrpos($name, '\\') + 1);
	}

	public function get() {
		$operator_ids = array_map(function (Operator $operator) {
			return $operator->getId();
		}, $this->operator_ids);


		return [
			'type_id' => $this->getId(),
			'operator_ids' => $operator_ids,
		];
	}
}