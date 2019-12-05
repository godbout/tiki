<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;


class FieldValue extends Target
{
	public function __construct($fieldId)
	{
		$this->targetId = 'field.value';
		parent::__construct($fieldId);
	}

}