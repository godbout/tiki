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

class EditLib_ParseToWiki_CharacterTest extends TikiTestCase
{

	private $el; // the EditLib


	protected function setUp() : void
	{
		TikiLib::lib('edit');
		$this->el = new EditLib();
	}


	protected function tearDown() : void
	{
	}


	/**
	 * Font Family and Font Size
	 *
	 * => {FONT(family="tahoma", size="12pt")}text{FONT}
	 * - 'font-family'
	 * - 'font-size'
	 *
	 * @group marked-as-incomplete
	 */
	public function testFontFamily(): void
	{
		$this->markTestIncomplete('Work in progress.');

		/*
		 * family
		 */
		$ex = '{FONT(family="tahoma")}text{FONT}';
		$inData = '<span style="font-family:tahoma;">text<span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);


		/*
		 * size
		 */
		$ex = '{FONT(size="12px")}text{FONT}';
		$inData = '<span style="font-size:12px;">text<span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$ex = '{FONT(size="12pt")}text{FONT}';
		$inData = '<span style="font-size:12pt;">text<span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$ex = '{FONT(size="1.2em")}text{FONT}';
		$inData = '<span style="font-size:1.2em;">text<span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);


		/*
		 * family and size
		 */
		$ex = '{FONT(family="tahoma", size="12pt")}';
		$inData = '<span style="font-family=tahoma";font-size:1.2pt;">text<span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);
	}


	/**
	 * Bold
	 *
	 * => __
	 * - <b>
	 * - <strong>
	 * - 'font-weight:bold'
	 */
	public function testBold(): void
	{

		// simple
		$ex = '__bold__';

		$inData = '<b>bold</b>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<strong>bold</strong>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="font-weight:bold;">bold</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		// line break
		$ex = '__bold__\n__BOLD__regular';

		$inData = '<b>bold<br />BOLD</b>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<strong>bold<br />BOLD</strong>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<span style="font-weight:bold;">bold<br />BOLD</span>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);
	}


	/**
	 * Italic
	 *
	 * => ''
	 * - <em>
	 * - <i>
	 * - 'font-style:italic'
	 */
	public function testItalic(): void
	{

		$ex = '\'\'italic\'\'';

		$inData = '<em>italic</em>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<i>italic</i>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="font-style:italic;">italic</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		// line break
		$ex = '\'\'italic\'\'\n\'\'ITALIC\'\'regular';

		$inData = '<em>italic<br />ITALIC</em>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<i>italic<br />ITALIC</i>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<span style="font-style:italic;">italic<br />ITALIC</span>regular';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);
	}


	/**
	 * Underlined
	 *
	 * => ===
	 * - <u>
	 * - 'text-decoration:underline'
	 */
	public function testUnderlined(): void
	{

		$ex = '===underlined===';

		$inData = '<u>underlined</u>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="text-decoration:underline;">underlined</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		// line break
		$ex = '===underlined===\n===UNDERLINED===';

		$inData = '<u>underlined<br />UNDERLINED</u>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<span style="text-decoration:underline;">underlined<br />UNDERLINED</span>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);
	}


	/**
	 * Strikethrough
	 *
	 * => --
	 * - <strike>
	 * - <del>
	 * - <s>
	 * - 'text-decoration:line-through'
	 */
	public function testStrikethrough(): void
	{

		$ex = '--strikethrough--';

		$inData = '<strike>strikethrough</strike>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<del>strikethrough</del>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<s>strikethrough</s>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="text-decoration:line-through;">strikethrough</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		// line break
		$ex = '--strikethrough--\n--STRIKETHROUGH--';

		$inData = '<strike>strikethrough<br />STRIKETHROUGH</strike>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<del>strikethrough<br />STRIKETHROUGH</del>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<s>strikethrough<br />STRIKETHROUGH</s>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);

		$inData = '<strike>strikethrough<br />STRIKETHROUGH</strike>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);
	}



	/**
	 * Subscript
	 *
	 * => {SUB()}
	 * - <sub>
	 */
	public function testSubscript(): void
	{

		$ex = '{SUB()}subscript{SUB}';

		$inData = '<sub>subscript</sub>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);
	}


	/**
	 * Superscript
	 *
	 * => {SUP()}
	 * - <sup>
	 */
	public function testSuperscript(): void
	{

		$ex = '{SUP()}subscript{SUP}';

		$inData = '<sup>subscript</sup>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);
	}


	/**
	 * Monospaced
	 *
	 * => -+
	 * - <code>
	 *
	 * @group marked-as-incomplete
	 */
	public function testMonospace(): void
	{

		$ex = '-+monospaced+-';

		$inData = '<code>monospaced</code>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		// line break
		$this->markTestIncomplete('Work in progress.');
		$ex = '-+monospaced+-\n-+MONOSPACED+-';
		$inData = '<code>monospaced<br />MONOSPACED</code>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);
	}


	/**
	 * Teletype
	 *
	 * => {DIV(type="tt")}
	 * - <tt>
	 */
	public function testTeletype(): void
	{

		$ex = '{DIV(type="tt")}typewriter{DIV}';

		$inData = '<tt>typewriter</tt>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);
	}


	/**
	 * Text and background color
	 *
	 * => ~~
	 * - 'background'
	 * - 'background-color'
	 */
	public function testColor(): void
	{

		/*
		 * text only
		 */
		$ex = '~~#FF0000:color~~';

		$inData = '<span style="color:#FF0000;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);


		/*
		 * background only
		 */
		$ex = '~~ ,#FFFF00:color~~';

		$inData = '<span style="background:#FFFF00;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="background-color:#FFFF00;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);


		/*
		 * text and background
		 */
		$ex = '~~#FF0000,#0000FF:color~~';

		$inData = '<span style="color:rgb(255, 0, 0);background-color:rgb(0, 0, 255);">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="color:#FF0000;background-color:#0000FF;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="color:#FF0000;background:#0000FF;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="background-color:#0000FF;color:#FF0000;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);

		$inData = '<span style="background:#0000FF;color:#FF0000;">color</span>';
		$out = $this->el->parseToWiki($inData);
		$this->assertEquals($ex, $out);


		/*
		 * line break
		 */
		$ex = '~~#FF0000,#0000FF:color text 1~~\n~~#FF0000,#0000FF:color text 2~~';

		$inData = '<span style="color:rgb(255, 0, 0);background-color:rgb(0, 0, 255);">color text 1<br />color text 2</span>';
		$out = $this->el->parseToWiki($inData);
		$out = preg_replace('/\n/', '\n', $out); // fix LF encoding for comparison
		$this->assertEquals($ex, $out);
	}
}
