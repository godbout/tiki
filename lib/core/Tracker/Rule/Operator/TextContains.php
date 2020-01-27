<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextContains extends Operator
{
	function __construct()
	{
		parent::__construct(tr('contains'), Text::class, '.val().indexOf("%argument%") > -1');
	}
}
