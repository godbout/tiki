<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_QueryTest extends PHPUnit\Framework\TestCase
{
    public function testQueryGlobalText()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query('hello');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Token('hello', 'plaintext', 'contents'),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals(['hello'], $query->getTerms());
    }

    public function testCompositeQuery()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query('hello world');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_ImplicitPhrase(
                    [
                        new Search_Expr_Token('hello', 'plaintext', 'contents'),
                        new Search_Expr_Token('world', 'plaintext', 'contents'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals(['hello', 'world'], $query->getTerms());
    }

    public function testFilterType()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query('hello');
        $query->filterType('wiki page');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Token('hello', 'plaintext', 'contents'),
                new Search_Expr_Token('wiki page', 'identifier', 'object_type'),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals(['hello'], $query->getTerms());
    }

    public function testFilterCategory()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterCategory('1 and 2');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_And(
                    [
                        new Search_Expr_Token('1', 'multivalue', 'categories'),
                        new Search_Expr_Token('2', 'multivalue', 'categories'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals([], $query->getTerms());
    }

    public function testDeepFilterCategory()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterCategory('1 and 2', true);

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_And(
                    [
                        new Search_Expr_Token('1', 'multivalue', 'deep_categories'),
                        new Search_Expr_Token('2', 'multivalue', 'deep_categories'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testFilterLanguage()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterLanguage('en or fr');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('en', 'identifier', 'language'),
                        new Search_Expr_Token('fr', 'identifier', 'language'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals([], $query->getTerms());
    }

    public function testDefaultSearchOrder()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;

        $query->search($index);

        $this->assertEquals(Search_Query_Order::searchResult(), $index->getLastOrder());
    }

    public function testSpecifiedOrder()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;

        $query->setOrder(Search_Query_Order::recentChanges());

        $query->search($index);

        $this->assertEquals(Search_Query_Order::recentChanges(), $index->getLastOrder());
    }

    public function testOrderFromString()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;

        $query->setOrder('title_asc');

        $query->search($index);

        $this->assertEquals(new Search_Query_Order('title', 'text', 'asc'), $index->getLastOrder());
    }

    public function testFilterBasedOnPermissions()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterPermissions(['Registered', 'Editor', 'Project Lead ABC']);

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('Registered', 'multivalue', 'allowed_groups'),
                        new Search_Expr_Token('Editor', 'multivalue', 'allowed_groups'),
                        new Search_Expr_Token('Project Lead ABC', 'multivalue', 'allowed_groups'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testDefaultPagination()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;

        $query->search($index);

        $this->assertEquals(0, $index->getLastStart());
        $this->assertEquals(50, $index->getLastCount());
    }

    public function testSpecifiedPaginationRange()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->setRange(60, 30);

        $query->search($index);

        $this->assertEquals(60, $index->getLastStart());
        $this->assertEquals(30, $index->getLastCount());
    }

    public function testWithQueryRange()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterRange(1000, 2000);

        $query->search($index);

        $expr = new Search_Expr_And(
            [new Search_Expr_Range(1000, 2000, 'timestamp', 'modification_date')]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testFilterTags()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterTags('1 and 2');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_And(
                    [
                        new Search_Expr_Token('1', 'multivalue', 'freetags'),
                        new Search_Expr_Token('2', 'multivalue', 'freetags'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testFilterContentSpanMultipleFields()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->filterContent('hello world', ['contents', 'title']);

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Or(
                    [
                        new Search_Expr_ImplicitPhrase(
                            [
                                new Search_Expr_Token('hello', 'plaintext', 'contents'),
                                new Search_Expr_Token('world', 'plaintext', 'contents'),
                            ]
                        ),
                        new Search_Expr_ImplicitPhrase(
                            [
                                new Search_Expr_Token('hello', 'plaintext', 'title'),
                                new Search_Expr_Token('world', 'plaintext', 'title'),
                            ]
                        ),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testApplyWeight()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->setWeightCalculator(
            new Search_Query_WeightCalculator_Field(
                [
                    'title' => 5.5,
                    'allowed_groups' => 0.0001,
                ]
            )
        );
        $query->filterContent('hello', ['contents', 'title']);
        $query->filterPermissions(['Anonymous']);

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('hello', 'plaintext', 'contents', 1.0),
                        new Search_Expr_Token('hello', 'plaintext', 'title', 5.5),
                    ]
                ),
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('Anonymous', 'multivalue', 'allowed_groups', 0.0001),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
    }

    public function testEmptySubQueryIsMainQuery()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->getSubQuery(null)
            ->filterContent('hello');

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Token('hello', 'plaintext', 'contents'),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals(['hello'], $query->getTerms());
    }

    public function testSubQueryCreatesOrStatement()
    {
        $index = new Search_Index_Memory;
        $query = new Search_Query;
        $query->getSubQuery('abc')
            ->filterContent('hello');
        $query->getSubQuery('abc')
            ->filterCategory('1 and 2');
        $query->filterPermissions(['Registered']);

        $query->search($index);

        $expr = new Search_Expr_And(
            [
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('hello', 'plaintext', 'contents'),
                        new Search_Expr_And(
                            [
                                new Search_Expr_Token('1', 'multivalue', 'categories'),
                                new Search_Expr_Token('2', 'multivalue', 'categories'),
                            ]
                        ),
                    ]
                ),
                new Search_Expr_Or(
                    [
                        new Search_Expr_Token('Registered', 'multivalue', 'allowed_groups'),
                    ]
                ),
            ]
        );

        $this->assertEquals($expr, $index->getLastQuery());
        $this->assertEquals(['hello'], $query->getTerms());
    }

    public function testQueryCloning()
    {
        $query = new Search_Query('Hello World');
        $clone = clone $query;

        $query->filterCategory('1 OR 2');

        $this->assertNotEquals($query, $clone);
    }
}
