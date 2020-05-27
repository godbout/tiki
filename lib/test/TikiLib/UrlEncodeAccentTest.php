<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group integration
 */

class TikiLib_UrlEncodeAccentTest extends PHPUnit\Framework\TestCase
{
	protected function setUp() : void
	{
		$this->tikilib = TikiLib::lib('tiki');
	}

	public function testUrlEncodeAccent_shouldNotChangeValidUrlString(): void
	{
		$str = 'SomeString';
		$this->assertEquals($str, $this->tikilib->urlencode_accent($str));
	}

	public function testUrlEncodeAccent_shouldChangeStringWithInvalidCharactersForUrl(): void
	{
		$str = 'http://tiki.org/Página en español';
		$modifedString = 'http://tiki.org/P%C3%A1gina%20en%20espa%C3%B1ol';
		$this->assertEquals($modifedString, $this->tikilib->urlencode_accent($str));
	}
}
