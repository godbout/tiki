<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_TokenizerTest extends TikiTestCase
{
    public function testSimpleToken()
    {
        $tokenizer = new Math_Formula_Tokenizer;

        $this->assertEquals(['test'], $tokenizer->getTokens('test'));
    }

    public function testWithParenthesis()
    {
        $tokenizer = new Math_Formula_Tokenizer;

        $this->assertEquals(['test', ')'], $tokenizer->getTokens('test)'));
    }

    public function testWithMultipleParenthesis()
    {
        $tokenizer = new Math_Formula_Tokenizer;

        $this->assertEquals(['(', 'test', ')'], $tokenizer->getTokens('(test)'));
    }

    public function testIgnoreSpaces()
    {
        $tokenizer = new Math_Formula_Tokenizer;

        $this->assertEquals(['(', 'test', ')'], $tokenizer->getTokens(" (test\n\t\r) "));
    }

    public function testWithMultipleWords()
    {
        $tokenizer = new Math_Formula_Tokenizer;
        $this->assertEquals(['hello', 'world', 'foo-bar'], $tokenizer->getTokens('hello world foo-bar'));
    }

    public function testWordsAfterParenthesis()
    {
        $tokenizer = new Math_Formula_Tokenizer;
        $this->assertEquals(['hello', '(', 'world', ')', 'foo-bar'], $tokenizer->getTokens('hello (world) foo-bar'));
    }

    public function testQuotesAroundArguments()
    {
        $tokenizer = new Math_Formula_Tokenizer;
        $this->assertEquals(['hello', '(', 'world', '"test hello"', '"foo bar baz"', ')', 'foo-bar'], $tokenizer->getTokens('hello (world "test hello" "foo bar baz") foo-bar'));
    }

    public function testUnterminatedString()
    {
        $tokenizer = new Math_Formula_Tokenizer;
        $this->assertEquals(['hello', '(', 'world', '"test hello) foo-bar'], $tokenizer->getTokens('hello (world "test hello) foo-bar'));
    }

    public function testEndWithString()
    {
        $tokenizer = new Math_Formula_Tokenizer;
        $this->assertEquals(['hello', '(', 'world', '"(test hello)"'], $tokenizer->getTokens('hello (world "(test hello)"'));
    }
}
