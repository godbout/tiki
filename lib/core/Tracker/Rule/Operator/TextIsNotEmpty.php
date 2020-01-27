<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextIsNotEmpty extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is not empty'), Text::class, '.val()!==""');
	}
}