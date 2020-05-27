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

class EditLib_ParseToWysiwyg_CharacterTest extends TikiTestCase
{
	private $el; // the EditLib

	protected function setUp() : void
	{
		TikiLib::lib('edit');
		$_SERVER['HTTP_HOST'] = ''; // editlib expects that HTTP_HOST is defined

		$this->el = new EditLib();
	}


	protected function tearDown() : void
	{
	}


	/**
	 * @group marked-as-incomplete
	 */
	public function testFontFamily(): void
	{
		$this->markTestIncomplete('Work in progress.');

		$el = new Editlib();

		$inData = '{FONT(type="span", font-family="tahoma")}text{FONT}';
		$exp = '<span style="font-family:tahoma;">text<span>';
		$out = $el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}


	/**
	 * @group marked-as-incomplete
	 */
	public function testFontSize(): void
	{
		$this->markTestIncomplete('Work in progress.');

		$el = new Editlib();

		$inData = '{FONT(type="span", font-size="12px")}text{FONT}';
		$exp = '<span style="font-size:12px;">text<span>';
		$out = $el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}


	public function testBold(): void
	{
		$inData = '__bold__';
		$exp = '<strong>bold</strong>'; // like CKE
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);
	}


	public function testItalic(): void
	{
		$inData = '\'\'italic\'\'';
		$exp = '<em>italic</em>'; // like CKE
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);
	}


	public function testUnderlined(): void
	{
		$inData = '===underlined===';
		$exp = '<u>underlined</u>'; // like CKE
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);
	}


	public function testStrike(): void
	{
		$inData = '--strike through--';
		$exp = '<strike>strike through</strike>'; // like CKE
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);
	}


	/**
	 * @group marked-as-incomplete
	 */
	public function testSubscript(): void
	{
		$this->markTestIncomplete('Work in progress.');
		$inData = '{SUB()}subscript{SUB}';
		$exp = '<sub>subscript</sub>';
		$out = $this->el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}


	/**
	 * @group marked-as-incomplete
	 */
	public function testSuperscript(): void
	{
		$this->markTestIncomplete('Work in progress.');

		$el = new EditLib();

		$inData = '{SUP()}superscript{SUP}';
		$exp = '<sup>superscript</sup>';
		$out = $el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testMonospaced(): void
	{
		$this->markTestIncomplete('Work in progress.');

		$el = new EditLib();

		$inData = '-+monospaced+-';
		$exp = '<code>monospaced</code>';
		$out = $el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}

	/**
	 * @group marked-as-incomplete
	 */
	public function testTeletype(): void
	{
		$this->markTestIncomplete('Work in progress.');

		$el = new EditLib();

		$inData = '{DIV(type="tt")}teletype{DIV}';
		$exp = '<tt>teletype</tt>';
		$out = $el->parseToWysiwyg($inData);
		$this->assertStringContainsString($exp, $out);
	}


	public function testColor(): void
	{
		$inData = '~~#112233:text~~';
		$exp = '<span style="color:#112233">text</span>';
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);

		$inData = '~~ ,#112233:text~~';
		$exp = '<span style="background-color:#112233">text</span>';
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);

		$inData = '~~#AABBCC,#112233:text~~';
		$exp = '<span style="color:#AABBCC; background-color:#112233">text</span>';
		$out = trim($this->el->parseToWysiwyg($inData));
		$this->assertStringContainsString($exp, $out);
	}
}
