<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_MySql_PaginationTest extends Search_Index_PaginationTest
{
	protected function setUp() : void
	{
		$this->index = new Search_MySql_Index(TikiDb::get(), 'test_index');
		$this->index->destroy();
	}

	protected function tearDown() : void
	{
		if ($this->index) {
			$this->index->destroy();
		}
	}
}
