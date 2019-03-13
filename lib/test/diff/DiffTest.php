<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\diff;

use TikiTestCase;

require_once __DIR__ . '/../../diff/difflib.php';

class DiffTest extends TikiTestCase
{
	/**
	 * @dataProvider providerDiffByType
	 *
	 * @param $type
	 * @param $page1
	 * @param $page2
	 * @param $expected
	 */
	public function testDiffByType($type, $page1, $page2, $expected)
	{
		$result = diff2($page1, $page2, $type);

		$this->assertEquals($expected, $result);
	}

	public function providerDiffByType()
	{
		return [
			[
				'htmldiff',
				$this->loadAndParseFile(__DIR__ . '/fixtures/page1.wiki'),
				$this->loadAndParseFile(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/htmldiff.html'),
			],
			[
				'sidediff',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/sidediff.html'),
			],
			[
				'sidediff-char',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/sidediff-char.html'),
			],
			[
				'inlinediff',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/inlinediff.html'),
			],
			[
				'inlinediff-char',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/inlinediff-char.html'),
			],
			[
				'sidediff-full',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/sidediff-full.html'),
			],
			[
				'sidediff-full-char',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/sidediff-full-char.html'),
			],
			[
				'inlinediff-full',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/inlinediff-full.html'),
			],
			[
				'inlinediff-full-char',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				file_get_contents(__DIR__ . '/fixtures/inlinediff-full-char.html'),
			],
			[
				'unidiff',
				file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
				file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
				include __DIR__ . '/fixtures/unidiff.php',
			],
		];
	}

	protected function loadAndParseFile($file)
	{
		$parserLib = \TikiLib::lib('parser');

		$data = file_get_contents($file);

		$parse_options = ['is_html' => false, 'noheadinc' => true, 'suppress_icons' => true, 'noparseplugins' => true];
		$html = $parserLib->parse_data($data, $parse_options);

		return $html;
	}
}
