<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
use Search_MySql_QueryBuilder as QueryBuilder;
use Search_Expr_Token as Token;
use Search_Expr_And as AndX;
use Search_Expr_Or as OrX;
use Search_Expr_Not as NotX;
use Search_Expr_Range as Range;
use Search_Expr_Initial as Initial;
use Search_Expr_MoreLikeThis as MoreLikeThis;

class Search_MySql_QueryBuilderTest extends PHPUnit\Framework\TestCase
{
	private $builder;

	protected function setUp() : void
	{
		$this->builder = new QueryBuilder(TikiDb::get());
	}

	public function testSimpleQuery()
	{
		$expr = new Token('Hello', 'plaintext', 'contents', 1.5);

		$this->assertEquals("MATCH (`contents`) AGAINST ('Hello' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testSimplePhrase()
	{
		$expr = new Token('Hello World', 'plaintext', 'contents', 1.5);

		$this->assertEquals("MATCH (`contents`) AGAINST ('\\\"Hello World\\\"' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testQueryWithSinglePart()
	{
		$expr = new AndX(
			[
				new Token('Hello', 'plaintext', 'contents', 1.5),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('Hello' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],	// seems it returns the weight of the AndX, not the Token?
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testBuildOrQuery()
	{
		$expr = new OrX(
			[
				new Token('Hello', 'plaintext', 'contents', 1.5),
				new Token('World', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('(Hello World)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testAndQuery()
	{
		$expr = new AndX(
			[
				new Token('Hello', 'plaintext', 'contents', 1.5),
				new Token('World', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('(+Hello +World)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testNotBuild()
	{
		$expr = new NotX(
			new Token('Hello', 'plaintext', 'contents', 1.5)
		);

		$this->assertEquals("NOT (MATCH (`contents`) AGAINST ('Hello' IN BOOLEAN MODE))", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testFlattenNot()
	{
		$expr = new AndX(
			[
				new NotX(new Token('Hello', 'plaintext', 'contents', 1.5)),
				new NotX(new Token('World', 'plaintext', 'contents', 1.5)),
				new Token('Test', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('(-Hello -World +Test)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testBuildOrQueryDifferentField()
	{
		$expr = new OrX(
			[
				new Token('Hello', 'plaintext', 'foobar', 1.5),
				new Token('World', 'plaintext', 'baz', 1.0),
			]
		);

		$this->assertEquals("(MATCH (`foobar`) AGAINST ('Hello' IN BOOLEAN MODE) OR MATCH (`baz`) AGAINST ('World' IN BOOLEAN MODE))", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'foobar', 'type' => 'fulltext', 'weight' => 1.5],
				['field' => 'baz', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testAndQueryDifferentField()
	{
		$expr = new AndX(
			[
				new Token('Hello', 'plaintext', 'foobar', 1.5),
				new Token('World', 'plaintext', 'baz', 1.0),
			]
		);

		$this->assertEquals("(MATCH (`foobar`) AGAINST ('Hello' IN BOOLEAN MODE) AND MATCH (`baz`) AGAINST ('World' IN BOOLEAN MODE))", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'foobar', 'type' => 'fulltext', 'weight' => 1.5],
				['field' => 'baz', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testNotBuildNotIdentifier()
	{
		$expr = new NotX(
			new Token('Hello', 'identifier', 'object_id', 1.5)
		);

		$this->assertEquals("NOT (`object_id` = 'Hello')", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'object_id', 'type' => 'index', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testFlattenNotDifferentField()
	{
		$expr = new AndX(
			[
				new NotX(new Token('Hello', 'plaintext', 'contents', 1.5)),
				new NotX(new Token('World', 'plaintext', 'contents', 1.5)),
				new Token('Test', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('(-Hello -World +Test)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testFilterWithIdentifier()
	{
		$expr = new Token('Some entry', 'identifier', 'username', 1.5);

		$this->assertEquals("`username` = 'Some entry'", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'username', 'type' => 'index', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testRangeFilter()
	{
		$expr = new Range('Hello', 'World', 'plaintext', 'title', 1.5);

		$this->assertEquals("`title` BETWEEN 'Hello' AND 'World'", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'title', 'type' => 'index', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testInitialMatchFilter()
	{
		$expr = new Initial('Hello', 'plaintext', 'title', 1.5);

		$this->assertEquals("`title` LIKE 'Hello%'", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'title', 'type' => 'index', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testNestedOr()
	{
		$expr = new OrX(
			[
				new OrX(
					[
						new Token('Hello', 'plaintext', 'contents', 1.5),
						new Token('World', 'plaintext', 'contents', 1.0),
					]
				),
				new Token('Test', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('((Hello World) Test)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testNestedAnd()
	{
		$expr = new AndX(
			[
				new OrX(
					[
						new Token('Hello', 'plaintext', 'contents', 1.5),
						new Token('World', 'plaintext', 'contents', 1.0),
					]
				),
				new AndX(
					[
						new Token('Hello', 'plaintext', 'contents', 1.5),
						new Token('World', 'plaintext', 'contents', 1.0),
					]
				),
				new Token('Test', 'plaintext', 'contents', 1.0),
			]
		);

		$this->assertEquals("MATCH (`contents`) AGAINST ('(+(Hello World) +(+Hello +World) +Test)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
					['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testInvertNotOnlyMatchStatements()
	{
		$expr = new AndX(
			[
				new NotX(new Token('Hello', 'plaintext', 'contents', 1.5)),
				new NotX(new Token('World', 'plaintext', 'contents', 1.5)),
			]
		);

		$this->assertEquals("NOT MATCH (`contents`) AGAINST ('(Hello World)' IN BOOLEAN MODE)", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.0],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testOrNot()
	{
		$expr = new OrX(
			[
				new NotX(new Token('Hello', 'plaintext', 'contents', 1.5)),
				new Token('World', 'plaintext', 'contents', 1.5),
			]
		);

		$this->assertEquals("(NOT (MATCH (`contents`) AGAINST ('Hello' IN BOOLEAN MODE)) OR MATCH (`contents`) AGAINST ('World' IN BOOLEAN MODE))", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'contents', 'type' => 'fulltext', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testEmptyAnd()
	{
		$expr = new AndX(
			[]
		);

		$this->assertEquals("", $this->builder->build($expr));
		$this->assertEquals(
			[
			],
			$this->builder->getRequiredIndexes()
		);
	}

	public function testEmptyAndPart()
	{
		$expr = new AndX(
			[
				new Token('Hello', 'identifier', 'object_id', 1.5),
				new AndX([]),
			]
		);

		$this->assertEquals("(`object_id` = 'Hello')", $this->builder->build($expr));
		$this->assertEquals(
			[
				['field' => 'object_id', 'type' => 'index', 'weight' => 1.5],
			],
			$this->builder->getRequiredIndexes()
		);
	}
}
