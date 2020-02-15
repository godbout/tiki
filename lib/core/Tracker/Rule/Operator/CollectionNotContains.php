<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Collection;

class CollectionNotContains extends Operator
{
	function __construct()
	{
		parent::__construct(tr("doesn't contain"), Collection::class, '.val().indexOf("%argument%")===-1');
	}
}
