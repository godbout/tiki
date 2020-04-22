<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_EngineResult_MySQL implements Search_EngineResult_Interface
{
	private $index = null;

	public function __construct(Search_MySql_Index $index)
	{
		$this->index = $index;
	}

	/**
	 * Count the amount of fields used by the MySql search engine
	 * @return int
	 */
	public function getEngineFieldsCount()
	{
		return $this->index->getFieldsCount();
	}
}
