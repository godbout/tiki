<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests;

use PHPUnit_Framework_TestCase;
use TikiLib;

class AbsoluteToRelativeLinkTest extends PHPUnit_Framework_TestCase
{

	const BASE_URL = 'https://tiki.org/';
	const BASE_URL_HTTP = 'http://tiki.org/';

	const DEMO_TEXT = 'Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh,' .
	' ut fermentum massa justo sit amet risus ##### Fermentum Fringilla Dapibus.';

	public function setUp()
	{
		global $base_url, $base_url_http, $base_url_https, $prefs, $page_regex;

		$base_url = self::BASE_URL;
		$base_url_http = self::BASE_URL_HTTP;
		$base_url_https = self::BASE_URL;
		$prefs['feature_absolute_to_relative_links'] = 'y';
		$page_regex = '([^\n|\(\)])((?!(\)\)|\||\n)).)*?';
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testPluginHtml($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$data = '{HTML()}<span class="button"><a href="' . $baseUrl . 'tiki-index.php">Save changes</a></span> with my custom buttom to the page PluginHTML';
		$data .= '<a href="' . $baseUrl . 'tiki-index.php">' . $baseUrl . 'tiki-index.php</a>';
		$data .= $baseUrl . 'tiki-index.php';
		$data .= '{HTML}';

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$this->assertEquals($data, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testTextLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$link = $baseUrl . 'tiki-index.php';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '[tiki-index.php|tiki-index.php]';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testTextLinkSubFolder($baseUrl)
	{
		global $base_url, $base_url_http, $base_url_https;
		$base_url .= "xxxx/";
		$base_url_http .= "xxxx/";
		$base_url_https .= "xxxx/";
		$baseUrl .= "xxxx/";

		$this->testPluginHtml($baseUrl);
		$this->testTextLink($baseUrl);
		$this->testWikiLink($baseUrl);
		$this->testExternalLink($baseUrl);
		$this->testReplaceInsidePlugins($baseUrl);
		$this->testOtherMarkups($baseUrl);
		$this->testMixMultipleLinks($baseUrl);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testWikiLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$link = '((' . $baseUrl . 'tiki-index.php|Homepage))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((tiki-index.php|Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . $baseUrl . '|Homepage))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((Homepage|Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . rtrim($baseUrl, '/') . '|Homepage))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((Homepage|Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . $baseUrl . 'tiki-index.php))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((tiki-index.php))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . $baseUrl . '))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . rtrim($baseUrl, '/') . '))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . rtrim($baseUrl, '/') . '|Homepage))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((Homepage|Homepage))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . $baseUrl . 'tiki-index.php?page=Dummy|Dummy))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((tiki-index.php?page=Dummy|Dummy))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$link = '((' . $baseUrl . 'tiki-index.php?page=Dummy))';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '((tiki-index.php?page=Dummy))';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testExternalLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$link = '[https://doc.tiki.org/Documentation|Tiki Documentation]';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$this->assertEquals($data, $dataConverted);

		$link = '[' . $baseUrl . 'Documentation|Tiki Documentation]';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$expectedLink = '[Documentation|Tiki Documentation]';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testPreferenceDisabled($baseUrl)
	{
		global $prefs;
		$tikilib = TikiLib::lib('tiki');
		$prefs['feature_absolute_to_relative_links'] = 'n';
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1' .
			' Praesent commodo cursus magna, vel scelerisque nisl consectetur et. #2' .
			' Aenean lacinia bibendum nulla sed consectetur. #3' .
			' Dapibus Tellus Nibh Tortor Porta #4';

		$link1 = '[' . $baseUrl . 'tiki-index.php|' . $baseUrl . 'tiki-index.php]';
		$link2 = '[' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink|' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink]';
		$link3 = '((Homepage|Homepage))';
		$link4 = '[tiki-pagehistory.php?page=193&newver=12&oldver=11|tiki-pagehistory.php?page=193&newver=12&oldver=11]';

		$data = str_replace('#1', $link1, $text);
		$data = str_replace('#2', $link2, $data);
		$data = str_replace('#3', $link3, $data);
		$data = str_replace('#4', $link4, $data);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '[' . $baseUrl . 'tiki-index.php]';
		$expectedLink2 = '[' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink]';
		$expectedLink3 = '((Homepage))';
		$expectedLink4 = '[tiki-pagehistory.php?page=193&newver=12&oldver=11]';
		$dataResult = str_replace('#1', $expectedLink1, $text);
		$dataResult = str_replace('#2', $expectedLink2, $dataResult);
		$dataResult = str_replace('#3', $expectedLink3, $dataResult);
		$dataResult = str_replace('#4', $expectedLink4, $dataResult);

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testReplaceInsidePlugins($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$link = '{CODE()}' . $baseUrl . 'HomePage{CODE}';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testOtherMarkups($baseUrl)
	{

		$tikilib = TikiLib::lib('tiki');
		$link = '-+' . $baseUrl . 'HomePage+-';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '[' . $baseUrl . 'HomePage|Home Page]';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink = '[HomePage|Home Page]';
		$dataResult = str_replace('#####', $expectedLink, self::DEMO_TEXT);
		$this->assertEquals($dataResult, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '~np~' . $baseUrl . 'HomePage~/np~';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '-+' . $baseUrl . 'HomePage+-';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);
		$tikilib = TikiLib::lib('tiki');
		$link = '~pp~' . $baseUrl . 'HomePage~/pp~';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '~pre~' . $baseUrl . 'HomePage~/pre~';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '-=' . $baseUrl . 'HomePage=-';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);

		$tikilib = TikiLib::lib('tiki');
		$link = '{webdocviewer url="' . $baseUrl . 'dl178" width="750" height="780"}';
		$data = str_replace('#####', $link, self::DEMO_TEXT);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		// Should not replace
		$this->assertEquals($data, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testMixMultipleLinks($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1' .
			' Praesent commodo cursus magna, vel scelerisque nisl consectetur et. #2' .
			' Aenean lacinia bibendum nulla sed consectetur. #3';

		$link1 = '((' . $baseUrl . 'tiki-index.php|Homepage))';
		$link2 = '[' . $baseUrl . 'tiki-index.php|Homepage]';
		$link3 = '[https://doc.tiki.org/Documentation]';
		$data = str_replace('#1', $link1, $text);
		$data = str_replace('#2', $link2, $data);
		$data = str_replace('#3', $link3, $data);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '((tiki-index.php|Homepage))';
		$expectedLink2 = '[tiki-index.php|Homepage]';
		$expectedLink3 = '[https://doc.tiki.org/Documentation]';
		$dataResult = str_replace('#1', $expectedLink1, $text);
		$dataResult = str_replace('#2', $expectedLink2, $dataResult);
		$dataResult = str_replace('#3', $expectedLink3, $dataResult);

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testLinkInsidePlugin($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = "{CODE()}\n* #1\n* #2\n* #3\n{CODE}";

		$link1 = '((' . $baseUrl . 'tiki-index.php|Homepage))';
		$link2 = '[' . $baseUrl . 'tiki-index.php|Homepage]';
		$link3 = '[https://doc.tiki.org/Documentation]';
		$data = str_replace('#1', $link1, $text);
		$data = str_replace('#2', $link2, $data);
		$data = str_replace('#3', $link3, $data);
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$this->assertEquals($data, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testLinkOutsidePlugin($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1' .
			' Praesent commodo cursus magna, vel scelerisque nisl consectetur et.' .
			'~np~' . $baseUrl . 'example~/np~';

		$link1 = $baseUrl . 'tiki-index.php';

		$data = str_replace('#1', $link1, $text);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '[tiki-index.php|tiki-index.php]';

		$dataResult = str_replace('#1', $expectedLink1, $text);

		$this->assertEquals($dataResult, $dataConverted);
	}

	public function testInternalSameTitleAndLink()
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1';

		$link1 = '((HomePage|HomePage))';
		$data = str_replace('#1', $link1, $text);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '((HomePage))';
		$dataResult = str_replace('#1', $expectedLink1, $text);

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testExternalSameTitleAndLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1';

		$link1 = '[' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink|' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink]';
		$data = str_replace('#1', $link1, $text);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '[tiki-pagehistory.php?page=sametitleandlink]';
		$dataResult = str_replace('#1', $expectedLink1, $text);

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testMultipleSameTitleAndLinks($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'Nullam quis risus eget urna mollis ornare vel eu leo. #1' .
			' Praesent commodo cursus magna, vel scelerisque #2 nisl consectetur et.';

		$link1 = '[' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink|' . $baseUrl . 'tiki-pagehistory.php?page=sametitleandlink]';
		$link2 = '((HomePage|HomePage))';
		$data = str_replace('#1', $link1, $text);
		$data = str_replace('#2', $link2, $text);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '[tiki-pagehistory.php?page=sametitleandlink]';
		$expectedLink2 = '((HomePage))';
		$dataResult = str_replace('#1', $expectedLink1, $text);
		$dataResult = str_replace('#2', $expectedLink2, $text);

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testWikiMarkerInUrlShouldBeIgnored($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$text = 'https://dev.tiki.org/Online+Publishing+House+-+Output+formats ' . PHP_EOL .
				'[#1]' . PHP_EOL .
				'-+#2+-';

		$link1 = '[' . $baseUrl . 'HomePage]';
		$data = str_replace('#1', $link1, $text);
		$data = str_replace('#2', $link1, $text);

		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);

		$expectedLink1 = '[Homepage]';
		$dataResult = str_replace('#1', $expectedLink1, $text);
		$dataResult = str_replace('#2', $link1, $text);

		$this->assertEquals($dataResult, $dataConverted);
	}

	public function urlBases()
	{
		return [
			[self::BASE_URL],
			[self::BASE_URL_HTTP], // This tests tiki internal links with different schema protocol
		];
	}
}
