<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;


use Tiki\Lib\core\Tracker\Rule\Action;

class Field extends Type
{
	public function __construct()
	{
		parent::__construct('Nothing', [
			new Action\Hide(),
			new Action\NotRequired(),
			new Action\Required(),
			new Action\Show(),
		]);
	}
}
