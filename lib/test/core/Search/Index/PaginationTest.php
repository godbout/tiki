<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class Search_Index_PaginationTest extends PHPUnit\Framework\TestCase
{
    protected $index;

    public function testNoPagingRequired()
    {
        $this->assertResultCorrect(15, 0, 25, 1, 15);
    }

    public function testGetSecondPage()
    {
        $this->assertResultCorrect(30, 10, 10, 11, 20);
    }

    private function assertResultCorrect($count, $from, $perPage, $first, $last)
    {
        $this->addDocuments($count);

        $query = new Search_Query;
        $query->setOrder('object_id_nasc');
        $query->filterType('article');
        $query->setRange($from, $perPage);

        $result = $query->search($this->index);

        $this->assertCount($count, $result, 'total count');

        $real = [];
        foreach ($result as $hit) {
            $real[] = $hit;
        }

        $this->assertEquals($first, $real[0]['object_id'], 'first entry');
        $this->assertEquals($last, $real[count($real) - 1]['object_id'], 'last entry');
    }

    private function addDocuments($count)
    {
        $index = $this->index;

        $typeFactory = $index->getTypeFactory();

        for ($i = 0; $count > $i; ++$i) {
            $index->addDocument(
                [
                    'object_type' => $typeFactory->identifier('article'),
                    'object_id' => $typeFactory->identifier($i + 1),
                ]
            );
        }
    }
}
