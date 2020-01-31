<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Test\Lib\Perspective;

use Tiki\Test\TestHelpers\TikiDbHelper;
use Tiki\Test\TestHelpers\TikiProfileHelper;
use Tiki\Test\TestHelpers\WebClientHelper;

/**
 * @group RequiresWebServer
 */
class PerspectivesNavigationBaseTestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * Value used in the fixture files for TIKI_TEST_HOST
	 */
	const FIXTURE_HOST = 'tiki.localdomain';
	/**
	 * Value used in the fixture files for TIKI_TEST_HOST_A
	 */
	const FIXTURE_SITE = 'tiki-a.localdomain';

	public static function setUpBeforeClass()
	{
		if (! getenv('TIKI_TEST_HOST') || ! getenv('TIKI_TEST_HOST_A') || ! getenv('TIKI_TEST_HOST_B')) {
			self::markTestSkipped(
				'To run perspective tests you are expected to have a running webserver with 3 vhosts pointing to it and to setup the env TIKI_TEST_HOST, TIKI_TEST_HOST_A and TIKI_TEST_HOST_B'
			);
		}
	}

	public function navigateSteps($steps, $cleanCookies = false)
	{
		$client = WebClientHelper::createTestClient(false);

		foreach ($steps as $stepIndex => $step) {
			list($url, $httpCode, $location, $perspective) = $step;

			if ($cleanCookies) {
				$client->getCookieJar()->clear();
			}

			if (empty($url) || $url === 'follow-redirect') {
				$crawler = $client->followRedirect();
			} else {
				$crawler = $client->request('GET', $url);
			}

			/** @var \Symfony\Component\BrowserKit\Response $response */
			$response = $client->getResponse();

			$this->assertEquals($httpCode, $response->getStatusCode(), 'Comparing HTTP Code #' . $stepIndex);

			if (! empty($location)) {
				$this->assertEquals(
					$location,
					$response->getHeader('Location'),
					'Comparing Location header returned #' . $stepIndex
				);
			}

			if (! empty($perspective)) {
				if ($perspective[0] == '!') {
					$this->assertNotContains(
						substr($perspective, 1),
						$crawler->filter('body')->attr('class'),
						'Page shows right perspective #' . $stepIndex
					);
				} else {
					$this->assertContains(
						$perspective,
						$crawler->filter('body')->attr('class'),
						'Page shows right perspective #' . $stepIndex
					);
				}
			}
		}
	}
}
