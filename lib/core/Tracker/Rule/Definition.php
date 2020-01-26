<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Rule;

use Tiki\Lib\core\Tracker\Rule\Operator;
use Tiki\Lib\core\Tracker\Rule\Type;
use Tiki\Lib\core\Tracker\Rule\Action;

class Definition
{

	public static function get() {
		$out = [];

		// TODO these lists should be generated automatically somehow one day
		$operators = [
			new Operator\BooleanTrueFalse(),
			new Operator\DateTimeAfter(),
			new Operator\DateTimeBefore(),
			new Operator\DateTimeOn(),
			new Operator\NumberEquals(),
			new Operator\NumberGreaterThan(),
			new Operator\NumberLessThan(),
			new Operator\NumberNotEquals(),
			new Operator\TextContains(),
			new Operator\TextEquals(),
			new Operator\TextIsEmpty(),
			new Operator\TextIsNotEmpty(),
			new Operator\TextNotContains(),
		];

		$types = [
			new Type\Boolean(),
			new Type\DateTime(),
			new Type\Field(),
			new Type\Nothing(),
			new Type\Number(),
			new Type\Text(),
		];

		$actions = [
			new Action\Hide(),
			new Action\NotRequired(),
			new Action\Required(),
			new Action\Show(),
		];

		$out['operators'] = array_map(function (Operator\Operator $operator) {
			return $operator->get();
		}, $operators);

		$out['types'] = array_map(function (Type\Type $type) {
			return $type->get();
		}, $types);

		$out['actions'] = array_map(function (Action\Action $action) {
			return $action->get();
		}, $actions);

		return $out;
	}

}
