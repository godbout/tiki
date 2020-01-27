<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextEquals extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is'), Text::class, '.val()==="%argument%"');
	}
}