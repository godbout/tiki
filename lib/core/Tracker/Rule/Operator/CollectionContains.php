<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Collection;

class CollectionContains extends Operator
{
	function __construct()
	{
		parent::__construct(tr('contains'), Collection::class, '.val().indexOf("%argument%")>-1');
	}
}
