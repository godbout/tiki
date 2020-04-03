<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class TextIsEmpty extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is empty'), Nothing::class, '.val()===""');
	}
}