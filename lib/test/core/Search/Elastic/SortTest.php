<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_SortTest extends Search_Index_SortTest
{
	private $unified_stopwords;

	function setUp()
	{
		global $prefs;
		$this->unified_stopwords = $prefs['unified_stopwords'];
		$prefs['unified_stopwords'] = '';

		static $count = 0;

		$elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
		$connection = new Search_Elastic_Connection('http://' . $elasticSearchHost . ':9200');

		$status = $connection->getStatus();
		if (! $status->ok) {
			$this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
		}

		$this->index = new Search_Elastic_Index($connection, 'test_index');
		$this->index->destroy();

		$this->populate($this->index);
	}

	function tearDown()
	{
		global $prefs;
		$prefs['unified_stopwords'] = $this->unified_stopwords;

		if ($this->index) {
			$this->index->destroy();
		}
	}
}
