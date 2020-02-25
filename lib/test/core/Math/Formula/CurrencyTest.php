<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_CurrencyTest extends TikiTestCase
{
	private $currency1;
	private $currency2;
	private $runner;
	private $rates = [
		'USD' => 1,
		'CAD' => 1.308020
	];

	function setUp()
	{
		$this->currency1 = new Math_Formula_Currency(100, 'USD', $this->rates);
		$this->currency2 = new Math_Formula_Currency(100, 'CAD', $this->rates);
		$this->runner = new Math_Formula_Runner(
			[
				'Math_Formula_Function_' => null,
				'Math_Formula_DummyFunction_' => null,
			]
		);
		$this->runner->setVariables([
			'currency1' => $this->currency1,
			'currency2' => $this->currency2,
		]);
	}

	function testStringRepresentation()
	{
		$this->assertEquals('100CAD', (string)$this->currency2);
	}

	function testAdd()
	{
		$this->runner->setFormula('(add currency1 currency2)');
		$this->assertEquals((100+100/1.308020).'USD', (string)$this->runner->evaluate());
	}

	function testAddNumber()
	{
		$this->runner->setFormula('(add currency1 10)');
		$this->assertEquals('110USD', (string)$this->runner->evaluate());
	}

	function testSub()
	{
		$this->runner->setFormula('(sub currency1 currency2)');
		$this->assertEquals((100-100/1.308020).'USD', (string)$this->runner->evaluate());
	}

	function testMul()
	{
		$this->runner->setFormula('(mul currency1 currency2)');
		$this->assertEquals((100*100/1.308020).'USD', (string)$this->runner->evaluate());
	}

	function testDiv()
	{
		$this->runner->setFormula('(div currency1 currency2)');
		$this->assertEquals((100/(100/1.308020)).'USD', (string)$this->runner->evaluate());
	}

	function testComplex()
	{
		$this->runner->setFormula('(round (avg currency1 currency2) 2)');
		$this->assertEquals(round((100+100/1.308020)/2,2).'USD', (string)$this->runner->evaluate());
	}

	function testMax()
	{
		$this->runner->setFormula('(max currency1 currency2)');
		$this->assertEquals('100USD', (string)$this->runner->evaluate());
	}

	function testMin()
	{
		$this->runner->setFormula('(min currency1 currency2)');
		$this->assertEquals('100CAD', (string)$this->runner->evaluate());
	}

	function testLessThan()
	{
		$this->runner->setFormula('(less-than currency1 currency2)');
		$this->assertEquals(0, $this->runner->evaluate());
		$this->runner->setFormula('(less-than currency2 currency1)');
		$this->assertEquals(1, $this->runner->evaluate());
	}

	function testMoreThan()
	{
		$this->runner->setFormula('(more-than currency1 currency2)');
		$this->assertEquals(1, $this->runner->evaluate());
		$this->runner->setFormula('(more-than currency2 currency1)');
		$this->assertEquals(0, $this->runner->evaluate());
	}
}
