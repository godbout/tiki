<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Boolean;

class BooleanTrueFalse extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is checked'), Boolean::class, '.is(":checked")==%argument%');
	}
}
