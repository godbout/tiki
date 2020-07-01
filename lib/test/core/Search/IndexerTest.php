<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
class Search_IndexerTest extends PHPUnit\Framework\TestCase
{
	public function testWithoutContentSource()
	{
		$index = new Search_Index_Memory();

		$indexer = new Search_Indexer($index);
		$indexer->rebuild();

		$this->assertEquals(0, $index->size());
	}

	public function testSingleContentProvider()
	{
		$timeA = strtotime('2010-10-10 10:10:10');
		$timeB = strtotime('2010-10-26 12:00:00');

		$data = [
			'HomePage' => ['wiki_page_name' => 'HomePage', 'wiki_content' => 'Hello World', 'modification_date' => $timeA],
			'Help' => ['wiki_page_name' => 'Help', 'wiki_content' => 'None available.', 'modification_date' => $timeB],
		];

		$typeMap = [
			'modification_date' => 'timestamp',
			'wiki_content' => 'wikitext',
			'wiki_page_name' => 'plaintext',
		];

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', new Search_ContentSource_Static($data, $typeMap));
		$indexer->rebuild();

		$this->assertEquals(2, $index->size());

		$document = $index->getDocument(0);

		$typeFactory = $index->getTypeFactory();
		$this->assertEquals($typeFactory->identifier('wiki page'), $document['object_type']);
		$this->assertEquals($typeFactory->identifier('HomePage'), $document['object_id']);
		$this->assertEquals($typeFactory->wikitext('Hello World'), $document['wiki_content']);
		$this->assertEquals($typeFactory->timestamp($timeA), $document['modification_date']);
	}

	public function testSourceAggregation()
	{
		$timeA = strtotime('2010-10-10 10:10:10');
		$timeB = strtotime('2010-10-26 12:00:00');

		$typeMap = [
			'modification_date' => 'timestamp',
			'wiki_content' => 'wikitext',
			'wiki_page_name' => 'plaintext',
			'forum_post_title' => 'plaintext',
			'forum_post_body' => 'wikitext',
		];

		$data = [
			'HomePage' => ['wiki_page_name' => 'HomePage', 'wiki_content' => 'Hello World', 'modification_date' => $timeA],
			'Help' => ['wiki_page_name' => 'Help', 'wiki_content' => 'None available.', 'modification_date' => $timeB],
		];

		$wikiSource = new Search_ContentSource_Static($data, $typeMap);

		$data = [
			10 => ['forum_post_title' => 'Hello', 'forum_post_body' => 'Foobar.', 'modification_date' => $timeA],
		];

		$forumSource = new Search_ContentSource_Static($data, $typeMap);

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $wikiSource);
		$indexer->addContentSource('forum post', $forumSource);
		$indexer->rebuild();

		$this->assertEquals(3, $index->size());
	}

	public function testGlobalCollection()
	{
		$contentSource = new Search_ContentSource_Static(
			[
				'HomePage' => [],
				'OtherPage' => [],
				'Foobar' => [],
			],
			[]
		);

		$globalSource = new Search_GlobalSource_Static(
			[
				'wiki page:HomePage' => ['categories' => [1, 2, 3]],
				'wiki page:OtherPage' => ['categories' => [0]],
				'wiki page:Foobar' => ['categories' => [2]],
			],
			['categories' => 'multivalue']
		);

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $contentSource);
		$indexer->addGlobalSource($globalSource);
		$indexer->rebuild();

		$document = $index->getDocument(0);

		$typeFactory = $index->getTypeFactory();
		$this->assertEquals($typeFactory->multivalue([1, 2, 3]), $document['categories']);
	}

	public function testGlobalAssembly()
	{
		$contentSource = new Search_ContentSource_Static(
			['HomePage' => ['title' => 'Hello'],],
			['title' => 'plaintext']
		);

		$globalSource = new Search_GlobalSource_Static(
			['wiki page:HomePage' => ['freetags_text' => 'foobar baz'],],
			['freetags_text' => 'plaintext']
		);

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $contentSource);
		$indexer->addGlobalSource($globalSource);
		$stats = $indexer->rebuild();

		$document = $index->getDocument(0);

		$typeFactory = $index->getTypeFactory();
		$this->assertEquals($typeFactory->plainmediumtext('foobar baz Hello '), $document['contents']);
		$this->assertEquals(['wiki page' => 1], $stats['counts']);
		$this->assertArrayHasKey('wiki page', $stats['times']);
		$this->assertArrayHasKey('total', $stats['times']);
	}

	public function testContentSourceWithMultipleResults()
	{
		$contentSource = new Search_ContentSource_Static(
			[
				'HomePage' => [
					['title' => 'Hello'],
					['title' => 'Hello (latest)'],
				],
			],
			['title' => 'plaintext']
		);

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $contentSource);
		$stats = $indexer->rebuild();

		$document = $index->getDocument(1);

		$typeFactory = $index->getTypeFactory();
		$this->assertEquals($typeFactory->plaintext('Hello (latest)'), $document['title']);
		$this->assertEquals(['wiki page' => 2], $stats['counts']);
		$this->assertArrayHasKey('wiki page', $stats['times']);
		$this->assertArrayHasKey('total', $stats['times']);
	}

	public function testTemporaryFields()
	{
		$contentSource = new Search_ContentSource_Static(
			['HomePage' => ['_title' => 'Hello'],],
			['_title' => 'plaintext']
		);

		$index = new Search_Index_Memory;
		$indexer = new Search_Indexer($index);
		$indexer->addContentSource('wiki page', $contentSource);
		$stats = $indexer->rebuild();

		$document = $index->getDocument(0);

		$typeFactory = $index->getTypeFactory();
		$this->assertArrayNotHasKey('_title', $document);
	}
}
