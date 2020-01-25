<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;


use Tiki\Lib\core\Tracker\Rule\Operator;

class Number extends Type
{
	public function __construct()
	{
		parent::__construct('number', [
			new Operator\NumberEquals(),
			new Operator\NumberNotEquals(),
			new Operator\NumberGreaterThan(),
			new Operator\NumberLessThan(),
		]);
	}
}