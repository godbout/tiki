<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;

use Tiki\Lib\core\Tracker\Rule\Operator;

class DateTime extends Type
{
	public function __construct()
	{
		parent::__construct('datetime', [
			new Operator\DateTimeOn(),
			new Operator\DateTimeAfter(),
			new Operator\DateTimeBefore(),
		]);
	}
}