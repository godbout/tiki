<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;


use Tiki\Lib\core\Tracker\Rule\Type;

const TARGET_ID = 'field';

abstract class Target
{

	private $trackerlib;
	private $field;

	public function __construct($fieldId)
	{
		$this->trackerlib = \TikiLib::lib('trk');
		$this->field = $this->trackerlib->get_tracker_field($fieldId);
	}

	public function getType() {
		if (in_array($this->field['type'], ['f', 'j', 'CAL'])) {
			return new Type\DateTime();
		} else if (in_array($this->field['type'], ['n', 'q', 'b'])) {
			return new Type\Number();
		} else {
			return new Type\Text();
		}
	}

	public static function getTargetId() {
		return TARGET_ID;
	}

}