<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Rule;

use Tiki\Lib\core\Tracker\Rule\Operator;
use Tiki\Lib\core\Tracker\Rule\Target;

class Rule
{
	private $target;
	private $operator;
	private $argument;

	function __construct()
	{
	}

	public static function fromData($fieldId, $data)
	{
		if (is_string($data)) {
			$data = json_decode($data);
		}

		$rule = new self;

		if ($data->target_id === 'field.value') {
			$rule->target = new Target\FieldValue($fieldId);
		} else {
			if ($data->target_id === 'field.showing') {
				$rule->target = new Target\FieldShowing($fieldId);
			}
		}

		$rule->argument = $data->argument;
	}

}
