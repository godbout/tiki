<?php


namespace Tiki\Lib\core\Tracker\Rule\Action;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class Required extends Action
{
	public function __construct()
	{
		parent::__construct(tr('Required'), Nothing::class);
	}
}
