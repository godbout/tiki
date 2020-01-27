<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Number;

class NumberNotEquals extends Operator
{
	function __construct()
	{
		parent::__construct('<>', Number::class, '.val()!==%argument%');
	}
}
