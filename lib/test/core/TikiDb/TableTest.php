<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiDb_TableTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected $tikiDb;

	public function testInsertOne()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'INSERT IGNORE INTO `my_table` (`label`) VALUES (?)';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['hello']));

		$mock->expects($this->once())
			->method('lastInsertId')
			->with()
			->willReturn(42);

		$table = new TikiDb_Table($mock, 'my_table');
		$this->assertEquals(
			42,
			$table->insert(
				['label' => 'hello',],
				true
			)
		);
	}

	public function testInsertWithMultipleValues()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'INSERT INTO `test_table` (`label`, `description`, `count`) VALUES (?, ?, ?)';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['hello', 'world', 15]));

		$mock->expects($this->once())
			->method('lastInsertId')
			->with()
			->willReturn(12);

		$table = new TikiDb_Table($mock, 'test_table');
		$this->assertEquals(
			12,
			$table->insert(
				[
					'label' => 'hello',
					'description' => 'world',
					'count' => 15,
				]
			)
		);
	}

	public function testDeletionOnSingleCondition()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'DELETE FROM `my_table` WHERE 1=1 AND `some_id` = ? LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo([15]));

		$table = new TikiDb_Table($mock, 'my_table');

		$table->delete(['some_id' => 15,]);
	}

	public function testDeletionOnMultipleConditions()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'DELETE FROM `other_table` WHERE 1=1 AND `objectType` = ? AND `objectId` = ? LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['wiki page', 'HomePage']));

		$table = new TikiDb_Table($mock, 'other_table');

		$table->delete(
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
			]
		);
	}

	public function testDeletionForMultiple()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'DELETE FROM `other_table` WHERE 1=1 AND `objectType` = ? AND `objectId` = ?';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['wiki page', 'HomePage']));

		$table = new TikiDb_Table($mock, 'other_table');

		$table->deleteMultiple(
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
			]
		);
	}

	public function testDeleteNullCondition()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'DELETE FROM `other_table` WHERE 1=1 AND `objectType` = ? AND `objectId` = ? AND (`lang` = ? OR `lang` IS NULL) LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['wiki page', 'HomePage', null]));

		$table = new TikiDb_Table($mock, 'other_table');

		$table->delete(
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
				'lang' => '',
			]
		);
	}

	public function testUpdate()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'UPDATE `my_table` SET `title` = ?, `description` = ? WHERE 1=1 AND `objectType` = ? AND `objectId` = ? LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['hello world', 'foobar', 'wiki page', 'HomePage']));

		$table = new TikiDb_Table($mock, 'my_table');
		$table->update(
			[
				'title' => 'hello world',
				'description' => 'foobar',
			],
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
			]
		);
	}

	public function testUpdateMultiple()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'UPDATE `my_table` SET `title` = ?, `description` = ? WHERE 1=1 AND `objectType` = ? AND `objectId` = ?';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['hello world', 'foobar', 'wiki page', 'HomePage']));

		$table = new TikiDb_Table($mock, 'my_table');
		$table->updateMultiple(
			[
				'title' => 'hello world',
				'description' => 'foobar',
			],
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
			]
		);
	}

	public function testInsertOrUpdate()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'INSERT INTO `my_table` (`title`, `description`, `objectType`, `objectId`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `title` = ?, `description` = ?';

		$mock->expects($this->once())
			->method('queryException')
			->with(
				$this->equalTo($query),
				$this->equalTo(
					[
						'hello world',
						'foobar',
						'wiki page',
						'HomePage',
						'hello world',
						'foobar'
					]
				)
			);

		$table = new TikiDb_Table($mock, 'my_table');
		$table->insertOrUpdate(
			[
				'title' => 'hello world',
				'description' => 'foobar',
			],
			[
				'objectType' => 'wiki page',
				'objectId' => 'HomePage',
			]
		);
	}

	public function testExpressionAssign()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'UPDATE `my_table` SET `hits` = `hits` + ? WHERE 1=1 AND `fileId` = ? LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo([5, 42]));

		$table = new TikiDb_Table($mock, 'my_table');
		$table->update(
			['hits' => $table->expr('$$ + ?', [5]),],
			['fileId' => 42,]
		);
	}

	public function testComplexBuilding()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'UPDATE `my_table` SET `hits` = `weight` * ? * (`hits` + ?) WHERE 1=1 AND `fileId` = ? LIMIT 1';

		$mock->expects($this->once())
			->method('queryException')
			->with(
				$this->equalTo($query),
				$this->equalTo([1.5, 5, 42])
			);

		$table = new TikiDb_Table($mock, 'my_table');
		$table->update(
			['hits' => $table->expr('`weight` * ? * ($$ + ?)', [1.5, 5]),],
			['fileId' => 42,]
		);
	}

	public function testComplexCondition()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'DELETE FROM `my_table` WHERE 1=1 AND `pageName` = ? AND `modified` < ?';

		$mock->expects($this->once())
			->method('queryException')
			->with($this->equalTo($query), $this->equalTo(['SomePage', 12345]));

		$table = new TikiDb_Table($mock, 'my_table');
		$table->deleteMultiple(
			[
				'pageName' => 'SomePage',
				'modified' => $table->expr('$$ < ?', [12345]),
			]
		);
	}

	public function testReadOne()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `user` FROM `tiki_user_watches` WHERE 1=1 AND `watchId` = ?';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(1), $this->equalTo(0))
			->willReturn(
				[['user' => 'hello'],]
			);

		$table = new TikiDb_Table($mock, 'tiki_user_watches');

		$this->assertEquals('hello', $table->fetchOne('user', ['watchId' => 42]));
	}

	public function testFetchColumn()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `group` FROM `tiki_group_watches` WHERE 1=1 AND `object` = ? AND `event` = ?';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42, 'foobar']), $this->equalTo(-1), $this->equalTo(-1))
			->willReturn(
				[
					['group' => 'hello'],
					['group' => 'world'],
				]
			);

		$table = new TikiDb_Table($mock, 'tiki_group_watches');
		$this->assertEquals(['hello', 'world'], $table->fetchColumn('group', ['object' => 42, 'event' => 'foobar']));
	}

	public function testFetchColumnWithSort()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `group` FROM `tiki_group_watches` WHERE 1=1 AND `object` = ? AND `event` = ? ORDER BY `group` ASC';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42, 'foobar']), $this->equalTo(-1), $this->equalTo(-1))
			->willReturn(
				[
					['group' => 'hello'],
					['group' => 'world'],
				]
			);

		$table = new TikiDb_Table($mock, 'tiki_group_watches');
		$this->assertEquals(['hello', 'world'], $table->fetchColumn('group', ['object' => 42, 'event' => 'foobar'], -1, -1, 'ASC'));
	}

	public function testFetchAll_shouldConsiderOnlyProvidedFields()
	{
		$expectedResult = [
			['user' => 'admin'],
			['user' => 'test']
		];

		$query = 'SELECT `user`, `email` FROM `users_users` WHERE 1=1';

		$tikiDb = $this->createMock('TikiDb');
		$tikiDb->expects($this->once())->method('fetchAll')
			->with($query, [], -1, -1)
			->willReturn($expectedResult);

		$table = new TikiDb_Table($tikiDb, 'users_users');

		$this->assertEquals($expectedResult, $table->fetchAll(['user', 'email'], []));
	}

	public function testFetchAll_shouldReturnAllFieldsIfFirstParamIsEmpty()
	{
		$expectedResult = [
			['user' => 'admin'],
			['user' => 'test']
		];

		$query = 'SELECT * FROM `users_users` WHERE 1=1';

		$tikiDb = $this->createMock('TikiDb');
		$tikiDb->expects($this->exactly(2))->method('fetchAll')
			->with($query, [], -1, -1)
			->willReturn($expectedResult);

		$table = new TikiDb_Table($tikiDb, 'users_users');

		$this->assertEquals($expectedResult, $table->fetchAll([], []));
		$this->assertEquals($expectedResult, $table->fetchAll());
	}

	public function testFetchRow()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `user`, `email` FROM `users_users` WHERE 1=1 AND `userId` = ?';

		$row = ['user' => 'hello', 'email' => 'hello@example.com'];

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(1), $this->equalTo(0))
			->willReturn([$row,]);

		$table = new TikiDb_Table($mock, 'users_users');

		$this->assertEquals($row, $table->fetchRow(['user', 'email'], ['userId' => 42]));
	}

	public function testFetchCount()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT COUNT(*) FROM `users_users` WHERE 1=1 AND `userId` = ?';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(1), $this->equalTo(0))
			->willReturn([[15],]);

		$table = new TikiDb_Table($mock, 'users_users');

		$this->assertEquals(15, $table->fetchCount(['userId' => 42]));
	}

	public function testFetchFullRow()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT * FROM `users_users` WHERE 1=1 AND `userId` = ?';

		$row = ['user' => 'hello', 'email' => 'hello@example.com'];

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(1), $this->equalTo(0))
			->willReturn([$row,]);

		$table = new TikiDb_Table($mock, 'users_users');

		$this->assertEquals($row, $table->fetchFullRow(['userId' => 42]));
	}

	public function testFetchMap()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `user`, `email` FROM `users_users` WHERE 1=1 AND `userId` > ? ORDER BY `user` DESC';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(-1), $this->equalTo(-1))
			->willReturn(
				[['user' => 'hello', 'email' => 'hello@example.com'], ['user' => 'world', 'email' => 'world@example.com'],]
			);

		$table = new TikiDb_Table($mock, 'users_users');

		$expect = [
				'hello' => 'hello@example.com',
				'world' => 'world@example.com',
				];
		$this->assertEquals($expect, $table->fetchMap('user', 'email', ['userId' => $table->greaterThan(42)], -1, -1, ['user' => 'DESC']));
	}

	public function testAliasField()
	{
		$mock = $this->createMock('TikiDb');

		$query = 'SELECT `user`, `email` AS `address` FROM `users_users` WHERE 1=1 AND `userId` > ? ORDER BY `user` DESC';

		$mock->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo($query), $this->equalTo([42]), $this->equalTo(-1), $this->equalTo(-1))
			->willReturn(
				[
					['user' => 'hello', 'address' => 'hello@example.com'],
					['user' => 'world', 'address' => 'world@example.com'],
				]
			);

		$table = new TikiDb_Table($mock, 'users_users');

		$expect = [
				['user' => 'hello', 'address' => 'hello@example.com'],
				['user' => 'world', 'address' => 'world@example.com'],
				];
		$this->assertEquals($expect, $table->fetchAll(['user', 'address' => 'email'], ['userId' => $table->greaterThan(42)], -1, -1, ['user' => 'DESC']));
	}

	public function testIncrement()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ + ?', [1]), $table->increment(1));
	}

	public function testDecrement()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ - ?', [1]), $table->decrement(1));
	}

	public function testNot()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ <> ?', [1]), $table->not(1));
	}

	public function testGreaterThan()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ > ?', [1]), $table->greaterThan(1));
	}

	public function testLesserThan()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ < ?', [1]), $table->lesserThan(1));
	}

	public function testLike()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ LIKE ?', ['foo%']), $table->like('foo%'));
	}

	public function testInWithEmptyArray()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('1=0', []), $table->in([]));
	}

	public function testInWithData()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('$$ IN(?, ?, ?)', [1, 2, 3]), $table->in([1, 2, 3]));
	}

	public function testInWithDataNotSensitive()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('BINARY $$ IN(?, ?, ?)', [1, 2, 3]), $table->in([1, 2, 3], true));
	}

	public function testExactMatch()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('BINARY $$ = ?', ['foo%']), $table->exactly('foo%'));
	}

	public function testAllFields()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals([$table->expr('*', [])], $table->all());
	}

	public function testCountAll()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('COUNT(*)', []), $table->count());
	}

	public function testSumField()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('SUM(`hits`)', []), $table->sum('hits'));
	}

	public function testMaxField()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('MAX(`hits`)', []), $table->max('hits'));
	}

	public function testFindIn()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');

		$this->assertEquals($table->expr('(`a` LIKE ? OR `b` LIKE ? OR `c` LIKE ?)', ["%X%", "%X%", "%X%"]), $table->findIn('X', ['a', 'b', 'c']));
	}

	public function testEmptyConcat()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');
		$this->assertEquals($table->expr('', []), $table->concatFields([]));
	}

	public function testEmptyConcatWithMultiple()
	{
		$mock = $this->createMock('TikiDb');
		$table = new TikiDb_Table($mock, 'my_table');
		$this->assertEquals($table->expr('CONCAT(`a`, `b`, `c`)', []), $table->concatFields(['a', 'b', 'c']));
	}
}
