<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;

use Tiki\Lib\core\Tracker\Rule\Type\Boolean;

class FieldShowing extends Target
{
	public function __construct()
	{
		parent::__construct(tr('Field showing'), Boolean::class);
	}
}