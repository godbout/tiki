<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_PaginationTest extends Search_Index_PaginationTest
{
    protected function setUp() : void
    {
        static $count = 0;

        $elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
        $connection = new Search_Elastic_Connection('http://' . $elasticSearchHost . ':9200');
        $connection->startBulk(100);

        $status = $connection->getStatus();
        if (! $status->ok) {
            $this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
        }

        $this->index = new Search_Elastic_Index($connection, 'test_index');
        $this->index->destroy();
    }

    protected function tearDown() : void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }
}
