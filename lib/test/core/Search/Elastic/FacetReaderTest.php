<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_FacetReaderTest extends PHPUnit\Framework\TestCase
{
	private $reader;

	protected function setUp() : void
	{
		$this->reader = new Search_Elastic_FacetReader(
			(object) [
				'facets' => (object) [
					'categories' => (object) [
						'_type' => "terms",
						'missing' => 0,
						'total' => 7,
						'other' => 0,
						'terms' => [
							(object) [
								'term' => "1",
								'count' => 3,
							],
							(object) [
								'term' => "2",
								'count' => 2,
							],
							(object) [
								'term' => "3",
								'count' => 1,
							],
						],
					],
					'tracker_field_priority' => (object) [
						'_type' => "terms",
						'missing' => 0,
						'total' => 7,
						'other' => 0,
						'terms' => [
							(object) [
								'term' => "",
								'count' => 3,
							],
							(object) [
								'term' => "2",
								'count' => 2,
							],
							(object) [
								'term' => "3",
								'count' => 1,
							],
						],
					],
				],
			]
		);
	}

	public function testReadUnavailable()
	{
		$this->assertNull($this->reader->getFacetFilter(new Search_Query_Facet_Term('foobar')));
	}

	public function testReadAvailable()
	{
		$facet = new Search_Query_Facet_Term('categories');
		$expect = new Search_ResultSet_FacetFilter(
			$facet,
			[
				['value' => "1", 'count' => 3],
				['value' => "2", 'count' => 2],
				['value' => "3", 'count' => 1],
			]
		);

		$this->assertEquals($expect, $this->reader->getFacetFilter($facet));
	}

	public function testIgnoreEmptyValue()
	{
		$facet = new Search_Query_Facet_Term('tracker_field_priority');
		$expect = new Search_ResultSet_FacetFilter(
			$facet,
			[
				['value' => "2", 'count' => 2],
				['value' => "3", 'count' => 1],
			]
		);

		$this->assertEquals($expect, $this->reader->getFacetFilter($facet));
	}
}
