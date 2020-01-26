<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;

use Tiki\Lib\core\Tracker\Rule\Type;


class Field extends Target
{

	private $trackerlib;
	private $field;

	public function __construct($fieldId)
	{
		$this->trackerlib = \TikiLib::lib('trk');
		$this->field = $this->trackerlib->get_tracker_field($fieldId);

		parent::__construct(tr('Field %0', $this->field['name']), $this->getType());
	}

	public function getType() {
		if (in_array($this->field['type'], ['f', 'j', 'CAL'])) {
			return Type\DateTime::class;
		} else if (in_array($this->field['type'], ['n', 'q', 'b'])) {
			return Type\Number::class;
		} else {
			return Type\Text::class;
		}
	}

	public function getId() {
		return $this->field['permName'];
	}

}