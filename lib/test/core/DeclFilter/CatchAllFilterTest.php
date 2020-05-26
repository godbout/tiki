<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */

class DeclFilter_CatchAllFilterTest extends TikiTestCase
{
	function testMatch()
	{
		$rule = new DeclFilter_CatchAllFilterRule('digits');

		$this->assertTrue($rule->match('hello'));
	}

	function testApply()
	{
		$rule = new DeclFilter_CatchAllFilterRule('digits');

		$data = [
			'hello' => '123abc',
		];

		$rule->apply($data, 'hello');

		$this->assertEquals('123', $data['hello']);
	}

	function testApplyRecursive()
	{
		$rule = new DeclFilter_CatchAllFilterRule('digits');
		$rule->applyOnElements();

		$data = [
			'hello' => [
				'abc123',
				'abc456',
			],
		];

		$rule->apply($data, 'hello');

		$this->assertEquals('123', $data['hello'][0]);
		$this->assertEquals('456', $data['hello'][1]);
	}
}
