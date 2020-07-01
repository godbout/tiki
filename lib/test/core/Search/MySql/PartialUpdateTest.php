<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_MySql_PartialUpdateTest extends Search_Index_PartialUpdateTest
{
	protected $index;

	protected function setUp() : void
	{
		$this->index = $this->getIndex();
		$this->index->destroy();
	}

	protected function getIndex()
	{
		return new Search_MySql_Index(TikiDb::get(), 'test_index');
	}

	protected function tearDown() : void
	{
		if ($this->index) {
			$this->index->destroy();
		}
	}
}
