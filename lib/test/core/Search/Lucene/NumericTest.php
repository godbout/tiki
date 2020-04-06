<?php

class Search_Lucene_NumericTest extends Search_Index_NumericTest
{
	protected function setUp() : void
{
		$this->dir = __DIR__ . '/test_index';
		$this->tearDown();

		$index = new Search_Lucene_Index($this->dir);

		$this->populate($index);
		$this->index = $index;
	}

	protected function tearDown() : void
{
		if ($this->index) {
			$this->index->destroy();
		}
	}
}
