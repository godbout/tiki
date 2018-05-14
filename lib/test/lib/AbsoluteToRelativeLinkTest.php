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

		$dataResult = '{HTML()}<span class="button"><a href="tiki-index.php">Save changes</a></span> with my custom buttom to the page PluginHTML';
		$dataResult .= '<a href="tiki-index.php">tiki-index.php</a>';
		$dataResult .= $baseUrl . 'tiki-index.php';
		$dataResult .= '{HTML}';

		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testTextLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$data = $baseUrl . 'tiki-index.php';
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$dataResult = '[tiki-index.php]';
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

		$tikilib = TikiLib::lib('tiki');
		$data = $baseUrl . 'tiki-index.php';
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$dataResult = '[tiki-index.php]';
		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testWikiLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$data = '((HomePage|#' . $baseUrl . 'tiki-index.php))';
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$dataResult = '((HomePage|#tiki-index.php))';
		$this->assertEquals($dataResult, $dataConverted);
	}

	/**
	 * @dataProvider urlBases
	 */
	public function testExternalLink($baseUrl)
	{
		$tikilib = TikiLib::lib('tiki');
		$data = '[' . $baseUrl . 'tiki-index.php|' . $baseUrl . 'tiki-index.php]';
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$dataResult = '[tiki-index.php|tiki-index.php]';
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
		$data = '[' . $baseUrl . 'tiki-index.php|' . $baseUrl . 'tiki-index.php]';
		$dataConverted = $tikilib->convertAbsoluteLinksToRelative($data);
		$this->assertEquals($data, $dataConverted);
	}

	public function urlBases()
	{
		return [
			[self::BASE_URL],
			[self::BASE_URL_HTTP], // This tests tiki internal links with different schema protocol
		];
	}
}
