<?php


namespace Tiki\Lib\core\Tracker\Rule\Type;


use Tiki\Lib\core\Tracker\Rule\Action;

class Nothing extends Type
{
	public function __construct()
	{
		parent::__construct('Nothing', [
			new Action\NoOp(),
		]);
	}
}
