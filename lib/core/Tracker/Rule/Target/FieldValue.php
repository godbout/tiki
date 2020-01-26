<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;

use Tiki\Lib\core\Tracker\Rule\Type\Text;

class FieldValue extends Target
{
	public function __construct()
	{
		parent::__construct(tr('Field value'), Text::class);
	}
}