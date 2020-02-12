<?php


namespace Tiki\Lib\core\Tracker\Rule\Action;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class NoOp extends Action
{
	function __construct()
	{
		parent::__construct('', Nothing::class, '');
	}
}
