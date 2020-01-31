<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Test\Lib\Perspective;

use Symfony\Component\DomCrawler\Crawler;
use Tiki\Test\TestHelpers\TikiDbHelper;
use Tiki\Test\TestHelpers\TikiProfileHelper;
use Tiki\Test\TestHelpers\WebClientHelper;
use function foo\func;

/**
 * @group RequiresWebServer
 */
class PerspectivesTest extends PerspectivesNavigationBaseTestCase
{
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		TikiDbHelper::refreshDb();
		TikiProfileHelper::applyTemplateProfile(__DIR__ . '/fixtures', 'testPerspectivesProfile', [], []);
	}

	/**
	 * @dataProvider navigations
	 */
	public function testPerspectives($testName, $cleanCookies, $steps)
	{
		$this->navigateSteps($steps, $cleanCookies);
	}

	public function navigations()
	{
		$host = getenv('TIKI_TEST_HOST');

		// $testName, $cleanCookies
		// $url, $httpCode, $location, $perspective
		return [
			['01 We can navigate every were with the default perspective', false, [
				['http://' . $host . '/tiki-index.php', 200, null, '!perspective'],
				['http://' . $host . '/tiki-index.php?page=A', 200, null, '!perspective'],
				['http://' . $host . '/tiki-index.php?page=B', 200, null, '!perspective'],
				['http://' . $host . '/tiki-index.php?page=A1-and-B1', 200, null, '!perspective'],
				['http://' . $host . '/tiki-view_forum.php?forumId=1', 200, null, '!perspective'],
				['http://' . $host . '/tiki-view_forum.php?forumId=2', 200, null, '!perspective'],
			]],
			['02 We can switch perspectives', false, [
				['http://' . $host . '/tiki-index.php', 200, null, '!perspective'],
				['http://' . $host . '/tiki-switch_perspective.php?perspective=1', 302, 'index.php', null],
				['follow-redirect', 302, 'http://' . $host . '/tiki-index.php', null],
				['follow-redirect', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-switch_perspective.php?perspective=2', 302, 'index.php', null],
				['follow-redirect', 302, 'http://' . $host . '/tiki-index.php', null],
				['follow-redirect', 200, null, 'perspective2'],
				['http://' . $host . '/tiki-switch_perspective.php?perspective=0', 302, 'index.php', null],
				['follow-redirect', 302, 'http://' . $host . '/tiki-index.php', null],
				['follow-redirect', 200, null, '!perspective'],
			]],
			['03 category Jail allows direct link of resources', false, [
				['http://' . $host . '/tiki-index.php', 200, null, '!perspective'],
				['http://' . $host . '/tiki-switch_perspective.php?perspective=1', 302, 'index.php', null],
				['follow-redirect', 302, 'http://' . $host . '/tiki-index.php', null],
				['follow-redirect', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-index.php?page=A', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-index.php?page=B', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-index.php?page=A1-and-B1', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-view_forum.php?forumId=1', 200, null, 'perspective1'],
				['http://' . $host . '/tiki-view_forum.php?forumId=2', 200, null, 'perspective1'],
			]],
		];
	}

	public function testCategoryJail()
	{
		$host = getenv('TIKI_TEST_HOST');

		$client = WebClientHelper::createTestClient(false);
		$client->getCookieJar()->clear();
		$client->followRedirects(true);

		$client->request('GET', 'http://' . $host . '/tiki-index.php');

		$client->request('GET', 'http://' . $host . '/tiki-switch_perspective.php?perspective=1');
		$crawler = $client->request('GET', 'http://' . $host . '/tiki-listpages.php');

		$pages = [];
		$crawler->filter('#listpages1 > tbody > tr')->each(function(Crawler $node) use (&$pages) {
			$pages[] = trim($node->filter('td:nth-child(1)')->text());
		});

		$expected = ['A', 'A1 A2 A3', 'A2 and B2', 'A3 and B3', 'A1 and B1'];
		sort($expected);
		sort($pages);
		$this->assertEquals($expected, $pages);
	}

}
