<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;

class DateTimeOn extends Operator
{
	function __construct()
	{
		parent::__construct(tr('on'), DateTime::class);
	}
}
