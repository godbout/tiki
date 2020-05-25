<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;

class DateTimeAfter extends Operator
{
	function __construct()
	{
		parent::__construct(tr('after'), DateTime::class, '.val() * 1 - $("[name=tzoffset]").val() * 60 > (new Date("%argument%")).getTime() / 1000');
	}
}
