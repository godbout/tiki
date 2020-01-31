<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Test\TestHelpers;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client as GuzzleClient;

class WebClientHelper
{
	/**
	 * @var bool $followRedirects if the client should automatically follow redirects
	 *
	 * @return \Goutte\Client
	 */
	public static function createTestClient($followRedirects = true)
	{
		$client = new GoutteClient();
		$client->setClient(
			new GuzzleClient([
				'allow_redirects' => false,
				'cookies'         => true,
				'curl' => [
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => false,
				]
			])
		);
		$client->followRedirects($followRedirects);
		$client->getCookieJar()->clear();

		return $client;
	}
}
