<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
class Search_Lucene_SortTest extends Search_Index_SortTest
{
	private $dir;

	protected function setUp() : void
	{
		$this->dir = __DIR__ . '/test_index';
		$this->tearDown();

		$this->index = new Search_Lucene_Index($this->dir);

		$this->populate($this->index);
	}

	protected function tearDown() : void
	{
		if ($this->index) {
			$this->index->destroy();
		}
	}
}
