<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_ProtocolTest extends PHPUnit_Framework_TestCase
{
	function testObtainStatus()
	{
		$elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
		$connection = new Search_Elastic_Connection('http://' . $elasticSearchHost . ':9200');
		$status = $connection->getStatus();

		if (! $status->ok) {
			$this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
		}

		$this->assertEquals(200, $status->status);
	}

	function testObtainVersion()
	{
		$elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
		$connection = new Search_Elastic_Connection('http://' . $elasticSearchHost . ':9200');
		$status = $connection->getStatus();

		if (! $status->ok) {
			$this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
		}

		$version = $connection->getVersion();

		$this->assertGreaterThan(0, $version);
	}
}
