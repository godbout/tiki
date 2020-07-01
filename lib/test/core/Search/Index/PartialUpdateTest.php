<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
abstract class Search_Index_PartialUpdateTest extends PHPUnit\Framework\TestCase
{
	abstract protected function getIndex();

	public function testPartialUpdate()
	{
		$initialSource = new Search_ContentSource_Static(
			[
				'HomePage' => ['data' => 'initial'],
				'SomePage' => ['data' => 'initial'],
				'Untouchable' => ['data' => 'initial'],
			],
			['data' => 'sortable']
		);

		$finalSource = new Search_ContentSource_Static(
			[
				'SomePage' => ['data' => 'final'],
				'OtherPage' => ['data' => 'final'],
				'Untouchable' => ['data' => 'final'],
			],
			['data' => 'sortable']
		);

		$index = $this->getIndex();

		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $initialSource);
		$indexer->rebuild();

		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $finalSource);
		$indexer->update(
			[
				['object_type' => 'wiki page', 'object_id' => 'HomePage'],
				['object_type' => 'wiki page', 'object_id' => 'SomePage'],
				['object_type' => 'wiki page', 'object_id' => 'OtherPage'],
			]
		);

		$query = new Search_Query;
		$query->filterType('wiki page');

		$result = $query->search($index);

		$this->assertCount(3, $result);

		$untouchableFound = false;
		foreach ($result as $doc) {
			$expected = $doc['object_id'] == 'Untouchable' ? 'initial' : 'final';
			$untouchableFound = $untouchableFound ?: $doc['object_id'] == 'Untouchable';
			$this->assertEquals($expected, $doc['data']);
		}

		$this->assertTrue($untouchableFound);
	}
}
