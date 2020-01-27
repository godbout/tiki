<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextNotContains extends Operator
{
	function __construct()
	{
		parent::__construct(tr('does not contain'), Text::class, '.val().indexOf("%argument%") === -1');
	}
}
