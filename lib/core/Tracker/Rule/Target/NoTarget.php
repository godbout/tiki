<?php


namespace Tiki\Lib\core\Tracker\Rule\Target;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class NoTarget extends Target
{
	public function __construct()
	{
		parent::__construct(tr(''), Nothing::class);
	}
}
