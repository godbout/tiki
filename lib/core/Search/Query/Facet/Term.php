<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Query_Facet_Term extends Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
	private $operator = 'or';
	private $count;

	static function fromField($field)
	{
		return new self($field);
	}

	function getCount()
	{
		return $this->count;
	}

	function setCount($count)
	{
		$this->count = $count;
		return $this;
	}

	function setRenderMap(array $map)
	{
		return $this->setRenderCallback(
			function ($value) use ($map) {
				if (isset($map[$value])) {
					return $map[$value];
				} else {
					return $value;
				}
			}
		);
	}

	function getType()
	{
		return 'terms';
	}

	function setOperator($operator)
	{
		$this->operator = in_array($operator, ['and', 'or']) ? $operator : 'or';
		return $this;
	}

	function getOperator()
	{
		return $this->operator;
	}
}
