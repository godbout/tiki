<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$relationlib = TikiLib::lib('relation');

class RelationTest extends TikiTestCase
{
	protected function setUp() : void
	{
		parent::setUp();
		TikiDb::get()->query('DELETE FROM `tiki_object_relations` WHERE `relation` LIKE ?', ['tiki.test%']);
	}

	protected function tearDown() : void
	{
		parent::tearDown();
		TikiDb::get()->query('DELETE FROM `tiki_object_relations` WHERE `relation` LIKE ?', ['tiki.test%']);
	}

	public function testNoRelations(): void
	{
		$lib = new RelationLib;

		$this->assertEquals([], $lib->get_relations_from('test wiki page', 'HomePage'));
	}

	public function testAddRelation(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

		$this->assertEquals(
			[['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage']],
			$this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
		);
	}

	public function testDuplicateRelation(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

		$this->assertEquals(
			[['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage']],
			$this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
		);
	}

	public function testMultipleResults(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test tracker item', '23');
		$lib->add_relation('tiki.test.something', 'test wiki page', 'HomePage', 'test tracker item', '23');
		$lib->add_relation('tiki.test.link', 'test tracker item', '23', 'test wiki page', 'SomePage');

		$result = $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'));

		$this->assertContains(['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage'], $result);
		$this->assertContains(['relation' => 'tiki.test.link', 'type' => 'test tracker item', 'itemId' => '23'], $result);
		$this->assertContains(['relation' => 'tiki.test.something', 'type' => 'test tracker item', 'itemId' => '23'], $result);
	}

	public function testFilterByType(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test tracker item', '23');
		$lib->add_relation('tiki.test.something', 'test wiki page', 'HomePage', 'test tracker item', '23');
		$lib->add_relation('tiki.test.link', 'test tracker item', '23', 'test wiki page', 'SomePage');

		$this->assertEquals(
			[['relation' => 'tiki.test.something', 'type' => 'test tracker item', 'itemId' => '23'],],
			$this->removeId($lib->get_relations_from('test wiki page', 'HomePage', 'tiki.test.something'))
		);
	}

	public function testRelationNamesChecked(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('TIKI . test  . link  ', 'test wiki page', 'HomePage', 'test tracker item', '23');

		$this->assertEquals(
			[['relation' => 'tiki.test.link', 'type' => 'test tracker item', 'itemId' => '23'],],
			$this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
		);
	}

	public function testLoadGroupOfRelations(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('tiki.test.sem.source', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');

		$result = $this->removeId($lib->get_relations_from('test wiki page', 'HomePage', 'tiki.test.sem.'));

		$this->assertContains(['relation' => 'tiki.test.sem.related', 'type' => 'test wiki page', 'itemId' => 'SomePage'], $result);
		$this->assertContains(['relation' => 'tiki.test.sem.source', 'type' => 'test external', 'itemId' => 'http://wikipedia.org'], $result);
		$this->assertNotContains(['relation' => 'tiki.test.link', 'type' => 'test external', 'itemId' => 'http://wikipedia.org'], $result);
	}

	public function testRevert(): void
	{
		$lib = new RelationLib;
		$lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
		$lib->add_relation('tiki.test.sem.source', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');
		$lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');

		$result = $this->removeId($lib->get_relations_to('test external', 'http://wikipedia.org', 'tiki.test.sem.'));

		$this->assertEquals(
			[['relation' => 'tiki.test.sem.source', 'type' => 'test wiki page', 'itemId' => 'HomePage']],
			$result
		);
	}

	public function testGetSingle(): void
	{
		$lib = new RelationLib;
		$id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

		$data = $lib->get_relation($id);

		$this->assertEquals('tiki.test.sem.related', $data['relation']);
	}

	public function testRemoveSingle(): void
	{
		$lib = new RelationLib;
		$id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

		$lib->remove_relation($id);

		$this->assertFalse($lib->get_relation($id));
	}

	private function removeId($data)
	{
		foreach ($data as & $row) {
			unset($row['relationId']);
		}

		return $data;
	}
}
