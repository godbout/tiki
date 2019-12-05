<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;

const TARGET_ID = 'field.showing';

class FieldShowing extends Target
{
	public function __construct($fieldId)
	{
		$this->targetId = 'field.showing';
		parent::__construct($fieldId);
	}

}