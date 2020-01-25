<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;

class DateTimeBefore extends Operator
{
	function __construct()
	{
		parent::__construct(tr('before'), DateTime::class);
	}
}
