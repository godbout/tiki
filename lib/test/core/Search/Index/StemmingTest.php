<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
abstract class Search_Index_StemmingTest extends PHPUnit\Framework\TestCase
{
	protected $index;

	protected function populate($index)
	{
		$typeFactory = $index->getTypeFactory();
		$index->addDocument(
			[
				'object_type' => $typeFactory->identifier('wikipage?!'),
				'object_id' => $typeFactory->identifier('Comité Wiki'),
				'description' => $typeFactory->plaintext('a descriptions for the pages éducation Case'),
				'contents' => $typeFactory->plaintext('a descriptions for the pages éducation Case'),
				'hebrew' => $typeFactory->plaintext('מחשב הוא מכונה המעבדת נתונים על פי תוכנית, כלומר על פי רצף פקודות נתון מראש. מחשבים הם חלק בלתי נפרד מחיי היומיום '),
			]
		);
	}

	public function testSearchWithAdditionalS()
	{
		$query = new Search_Query('description');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testSearchWithMissingS()
	{
		$query = new Search_Query('page');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testSearchAccents()
	{
		$query = new Search_Query('education');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testSearchAccentExactMatch()
	{
		$query = new Search_Query('éducation');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testSearchExtraAccents()
	{
		$query = new Search_Query('pagé');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testCaseSensitivity()
	{
		$query = new Search_Query('casE');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testFilterIdentifierExactly()
	{
		$query = new Search_Query;
		$query->filterType('wikipage?!');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testSearchObject()
	{
		$query = new Search_Query;
		$query->addObject('wikipage?!', 'Comité Wiki');

		$this->assertGreaterThan(0, count($query->search($this->index)));
	}

	public function testStopWords()
	{
		$query = new Search_Query('a for the');
		$this->assertCount(0, $query->search($this->index));
	}

	public function testHebrewString()
	{
		$query = new Search_Query;
		$query->filterContent('מחשב', 'hebrew');
		$this->assertCount(1, $query->search($this->index));
	}
}
