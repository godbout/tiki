<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Expr_TokenizerTest extends PHPUnit\Framework\TestCase
{
	private $tokenizer;

	protected function setUp() : void
	{
		$this->tokenizer = new Search_Expr_Tokenizer;
	}

	public function testSingleWord()
	{
		$this->assertEquals(['hello'], $this->tokenizer->tokenize('hello'));
	}

	public function testMultipleWords()
	{
		$this->assertEquals(['hello', 'world', 'who', 'listens'], $this->tokenizer->tokenize('hello world who listens'));
	}

	public function testWithQuotedText()
	{
		$this->assertEquals(['hello world', 'who listens'], $this->tokenizer->tokenize('"hello world" "who listens"'));
	}

	public function testWithParenthesis()
	{
		$this->assertEquals(
			[
				'hello world (who?)',
				'(',
				'who',
				')',
				'(',
				'test',
				'listens',
				')'
			],
			$this->tokenizer->tokenize('"hello world (who?)" (who) (test listens)')
		);
	}
}
