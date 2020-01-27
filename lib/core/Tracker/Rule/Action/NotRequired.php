<?php


namespace Tiki\Lib\core\Tracker\Rule\Action;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class NotRequired extends Action
{
	public function __construct()
	{
		parent::__construct(tr('Not Required'), Nothing::class, '.rules("remove", "required")');
	}
}