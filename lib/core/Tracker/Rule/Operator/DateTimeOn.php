<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;

class DateTimeOn extends Operator
{
	function __construct()
	{
		$syntax = '.val() * 1 - $("[name=tzoffset]").val() * 60 >= (new Date("%argument%")).getTime() / 1000 && ' .
			'$("[name=%field%:last]").val() * 1 - $("[name=tzoffset]").val() * 60 < ((new Date("%argument%")).getTime() / 1000 + 86400)';

		parent::__construct(tr('on'), DateTime::class, $syntax);
	}
}
