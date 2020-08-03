<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
abstract class Search_Index_SortTest extends PHPUnit\Framework\TestCase
{
    protected $index;

    protected function populate($index)
    {
        $this->add($index, 'A', '1', 'Hello', 'Hello Foobar');
        $this->add($index, 'B', '10', 'foobar', 'Hello World Foobar');
        $this->add($index, 'C', '2', 'Baz', 'Baz');
    }

    public function sortCases()
    {
        return [
            ['numeric_field_nasc', 'ACB'],
            ['numeric_field_ndesc', 'BCA'],
            ['numeric_field_asc', 'ABC'],
            ['text_field_asc', 'CBA'],
            ['text_field_desc', 'ABC'],
            ['other_field_asc', 'CAB'],
            ['other_field_desc', 'BAC'],
            ['object_id_asc', 'ABC'],
            ['object_id_desc', 'CBA'],
        ];
    }

    /**
     * @dataProvider sortCases
     *
     * @param $mode
     * @param $expected
     */
    public function testOrdering($mode, $expected)
    {
        $query = new Search_Query;
        $query->filterType('wiki page');
        $query->setOrder($mode);

        $results = $query->search($this->index);

        $this->assertOrderIs($expected, $results);
    }

    public function testWeightImpact()
    {
        $query = new Search_Query;
        $query->setWeightCalculator(
            new Search_Query_WeightCalculator_Field(
                [
                    'text_field' => 100,
                    'other_field' => 0.0001,
                ]
            )
        );
        $query->filterContent('foobar', ['text_field', 'other_field']);

        $results = $query->search($this->index);

        $this->assertOrderIs('BA', $results);
    }

    public function returnOnlyCases()
    {
        return [
            [[1, 2, 3, 4, 5, 6], 'ACB'],
            [[1, 2, 3], 'ACB'],
            [[], 'ACB'],
            [[1, 3], 'AB'],
            [[3, 2, 1], 'BCA'],
            [[3], 'B'],
            [[3, 1], 'BA'],
            [[4, 5, 6], ''],
        ];
    }

    /**
     * @dataProvider returnOnlyCases
     *
     * @param array $returnOnlyValue
     * @param string $expected
     */
    public function testReturnOnly($returnOnlyValue, $expected)
    {
        $query = new Search_Query;
        $query->filterType('wiki page');
        $query->setOrder('numeric_field_nasc');
        $query->setReturnOnlyResultList($returnOnlyValue);

        $results = $query->search($this->index);

        $this->assertOrderIs($expected, $results);
    }

    private function assertOrderIs($expected, $results)
    {
        $str = '';
        foreach ($results as $row) {
            $str .= $row['object_id'];
        }

        $this->assertEquals($expected, $str);
    }

    private function add($index, $page, $numeric, $text, $text2)
    {
        $typeFactory = $index->getTypeFactory();

        $index->addDocument(
            [
                'object_type' => $typeFactory->identifier('wiki page'),
                'object_id' => $typeFactory->identifier($page),
                'numeric_field' => $typeFactory->sortable($numeric),
                'text_field' => $typeFactory->sortable($text),
                'other_field' => $typeFactory->sortable($text2),
            ]
        );
    }
}
