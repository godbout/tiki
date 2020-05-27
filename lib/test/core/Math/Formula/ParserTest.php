<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_ParserTest extends TikiTestCase
{
	public function testEmptyElement()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element('score');

		$this->assertEquals($element, $parser->parse('(score)'));
	}

	public function testWithArguments()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element('object', ['input-type', 'input-object-id']);
		$this->assertEquals($element, $parser->parse('(object input-type input-object-id)'));
	}

	public function testNesting()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element(
			'score',
			[new Math_Formula_Element('object', ['type', 'object'])]
		);

		$this->assertEquals($element, $parser->parse('(score (object type object))'));
	}

	public function testMultipleElements()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element(
			'score',
			[
				new Math_Formula_Element('object', ['type', 'object']),
				new Math_Formula_Element('range', [3600]),
			]
		);

		$this->assertEquals($element, $parser->parse('(score (object type object) (range 3600))'));
	}

	public function testMultipleNesting()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element(
			'score',
			[
				new Math_Formula_Element('object', ['type', 'object']),
				new Math_Formula_Element(
					'range',
					[new Math_Formula_Element('mul', [3600, 60]),]
				),
			]
		);

		$this->assertEquals($element, $parser->parse('(score (object type object) (range (mul 3600 60)))'));
	}

	public function testSkipComments()
	{
		$equivalent = '(score (object type object) (range (mul 3600 60)))';
		$documented = <<<DOC
(score
	(comment calculate for a 60 hour range)
	(object type object)
	(range (mul
		(comment 3600 is an hour, times 60)
		3600 60))
)
DOC;

		$parser = new Math_Formula_Parser;
		$this->assertEquals($parser->parse($equivalent), $parser->parse($documented));
	}

	public function testWithString()
	{
		$parser = new Math_Formula_Parser;

		$element = new Math_Formula_Element(
			'score',
			[
				new Math_Formula_Element('object', [
					new Math_Formula_InternalString('wiki page'),
					new Math_Formula_InternalString('HomePage'),
				]),
				new Math_Formula_Element(
					'range',
					[new Math_Formula_Element('mul', [3600, 60]),]
				),
			]
		);

		$this->assertEquals($element, $parser->parse('(score (object "wiki page" "HomePage") (range (mul 3600 60)))'));
	}

	public function testWithZero()
	{
		$parser = new Math_Formula_Parser;
		$element = new Math_Formula_Element(
			'add',
			[
				new Math_Formula_Element('default', [0]),
				new Math_Formula_Element('attribute'),
			]
		);

		$this->assertEquals(
			$element,
			$parser->parse(
				'(add
				(default 0)
				(attribute)
				)'
			)
		);
	}

	public function badStrings()
	{
		return [
			'noOpening' => ['test'],
			'noToken' => ['()'],
			'doubles' => ['((test))'],
			'trail' => ['(test) foo'],
			'unfinished' => ['(score (object type object) (range 3600)'],
			'unfinishedString' => ['(score (object "wiki page object) (range 3600))'],
		];
	}

	/**
	 * @dataProvider badStrings
	 * @param $string
	 * @throws Math_Formula_Parser_Exception
	 */
	public function testBadParse($string)
	{
		$this->expectException(Math_Formula_Parser_Exception::class);
		$parser = new Math_Formula_Parser;

		$parser->parse($string);
	}
}
