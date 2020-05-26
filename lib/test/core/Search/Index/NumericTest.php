<?php

abstract class Search_Index_NumericTest extends PHPUnit\Framework\TestCase
{
	protected $index;

	protected function populate($index)
	{
		$typeFactory = $index->getTypeFactory();
		$index->addDocument(
			[
				'object_type' => $typeFactory->identifier('wiki page'),
				'object_id' => $typeFactory->identifier('HomePage'),
				'contents' => $typeFactory->plaintext('module 7, 2.5.3')->filter(
					[
						new Search_ContentFilter_VersionNumber,
					]
				),
			]
		);
	}

	public function testMatchVersion()
	{
		$this->assertResultCount(1, '2.5.3');
	}

	public function testNoMatchLesserVersionPortion()
	{
		$this->assertResultCount(0, '5.3');
	}

	public function testNoMatches()
	{
		$this->assertResultCount(0, '2.3.5');
	}

	public function testMatchHigherVersionPortion()
	{
		$this->assertResultCount(1, '2.5');
	}

	private function assertResultCount($count, $argument)
	{
		$query = new Search_Query;
		$query->filterContent($argument);

		$this->assertCount($count, $query->search($this->index));
	}
}
