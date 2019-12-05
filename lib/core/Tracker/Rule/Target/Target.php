<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;
use Tiki\Lib\core\Tracker\Rule\Type\Integer;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

class Target
{
	protected $targetId = 'field';
	private $trackerlib;
	private $field;

	public function __construct($fieldId)
	{
		$this->trackerlib = \TikiLib::lib('trk');
		$this->field = $this->trackerlib->get_tracker_field($fieldId);
	}

	public function getType() {
		if (in_array($this->field['type'], ['f', 'j', 'CAL'])) {
			return new DateTime();
		} else if (in_array($this->field['type'], ['n', 'q', 'b'])) {
			return new Integer();
		} else {
			return new Text();
		}
	}

}