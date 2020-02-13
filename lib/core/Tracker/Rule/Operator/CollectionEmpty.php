<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Collection;

class CollectionEmpty extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is empty'), Collection::class, '.val().length===0');
	}
}
