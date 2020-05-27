<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
abstract class Search_Index_BaseTest extends PHPUnit\Framework\TestCase
{
	public const DOCUMENT_DATE = 1234567890;
	protected $index;

	protected function populate($index)
	{
		$typeFactory = $index->getTypeFactory();
		$index->addDocument(
			[
				'object_type' => $typeFactory->identifier('wiki page'),
				'object_id' => $typeFactory->identifier('HomePage'),
				'title' => $typeFactory->sortable('HomePage'),
				'language' => $typeFactory->identifier('en'),
				'modification_date' => $typeFactory->timestamp(self::DOCUMENT_DATE),
				'description' => $typeFactory->sortable('a description for the page'),
				'categories' => $typeFactory->multivalue([1, 2, 5, 6]),
				'allowed_groups' => $typeFactory->multivalue(['Project Lead', 'Editor', 'Admins']),
				'contents' => $typeFactory->plaintext('a description for the page Bonjour world!'),
				'number' => $typeFactory->numeric(123),
				'relations' => $typeFactory->multivalue(
					[
						Search_Query_Relation::token('tiki.content.link', 'wiki page', 'About'),
						Search_Query_Relation::token('tiki.content.link', 'wiki page', 'Contact'),
						Search_Query_Relation::token('tiki.content.link.invert', 'wiki page', 'Product'),
						Search_Query_Relation::token('tiki.user.favorite.invert', 'user', 'bob'),
					]
				),
			]
		);
	}

	public function testBasicSearch()
	{
		$positive = new Search_Query('Bonjour');
		$negative = new Search_Query('NotInDocument');

		$this->assertContains(['object_type' => 'wiki page', 'object_id' => 'HomePage'], $this->stripExtra($positive->search($this->index)));
		$this->assertNotContains(['object_type' => 'wiki page', 'object_id' => 'HomePage'], $this->stripExtra($negative->search($this->index)));
	}

	private function stripExtra($list)
	{
		$out = [];

		foreach ($list as $entry) {
			$out[] = array_intersect_key($entry, ['object_type' => '', 'object_id' => '']);
		}

		return $out;
	}

	public function testFieldSpecificSearch()
	{
		$off = new Search_Query;
		$off->filterContent('description', 'title');
		$found = new Search_Query;
		$found->filterContent('description', 'description');

		$this->assertGreaterThan(0, count($found->search($this->index)));
		$this->assertCount(0, $off->search($this->index));
	}

	public function testWithOrCondition()
	{
		$positive = new Search_Query('foobar or bonjour');
		$negative = new Search_Query('foobar or baz');

		$this->assertGreaterThan(0, count($positive->search($this->index)));
		$this->assertCount(0, $negative->search($this->index));
	}

	public function testWithNotCondition()
	{
		$negative = new Search_Query('not world and bonjour');
		$positive = new Search_Query('not foobar and bonjour');

		$this->assertCount(0, $negative->search($this->index));
		$this->assertGreaterThan(0, count($positive->search($this->index)));
	}

	public function testFilterType()
	{
		$this->assertResultCount(1, 'filterType', 'wiki page');
		$this->assertResultCount(0, 'filterType', 'wiki');
		$this->assertResultCount(0, 'filterType', 'blog post');
	}

	public function testFilterCategories()
	{
		$this->assertResultCount(0, 'filterCategory', '3');
		$this->assertResultCount(1, 'filterCategory', '1 and 2');
		$this->assertResultCount(0, 'filterCategory', '1 and not 2');
		$this->assertResultCount(1, 'filterCategory', '1 and (2 or 3)');
	}

	public function testLanguageFilter()
	{
		$this->assertResultCount(1, 'filterLanguage', 'en');
		$this->assertResultCount(1, 'filterLanguage', 'en or fr');
		$this->assertResultCount(0, 'filterLanguage', 'en and fr');
		$this->assertResultCount(0, 'filterLanguage', 'fr');
		$this->assertResultCount(0, 'filterLanguage', 'NOT en');
		$this->assertResultCount(0, 'filterLanguage', 'NOT en');
	}

	public function testFilterPermissions()
	{
		$this->assertResultCount(0, 'filterPermissions', ['Anonymous']);
		$this->assertResultCount(0, 'filterPermissions', ['Registered']);
		$this->assertResultCount(1, 'filterPermissions', ['Registered', 'Editor']);
		$this->assertResultCount(1, 'filterPermissions', ['Project Lead']);
		$this->assertResultCount(0, 'filterPermissions', ['Project']);
	}

	public function testRangeFilter()
	{
		$this->assertResultCount(1, 'filterRange', self::DOCUMENT_DATE - 1000, self::DOCUMENT_DATE + 1000);
		$this->assertResultCount(0, 'filterRange', self::DOCUMENT_DATE - 1000, self::DOCUMENT_DATE - 500);
		$this->assertResultCount(1, 'filterRange', 2, 2000000000); // Check lexicography
		$this->assertResultCount(1, 'filterTextRange', 'Home', 'Page');
		$this->assertResultCount(0, 'filterTextRange', 'Homezzz', 'Page');
		$this->assertResultCount(1, 'filterNumericRange', 100, 200, 'number');
		$this->assertResultCount(0, 'filterNumericRange', 200, 300, 'number');
	}

	public function testRangeFilterBounds()
	{
		$this->assertResultCount(1, 'filterNumericRange', 123, 200, 'number');
		$this->assertResultCount(1, 'filterNumericRange', 100, 123, 'number');
	}

	public function testIndexProvidesHighlightHelper()
	{
		$query = new Search_Query('foobar or Bonjour');
		$resultSet = $query->search($this->index);

		// Manually adding the content to avoid initializing the entire formatter
		foreach ($resultSet as & $entry) {
			$entry['content'] = 'Bonjour World';
		}

		$plugin = new Search_Formatter_Plugin_WikiTemplate('{display name=highlight}');
		$formatter = new Search_Formatter($plugin);
		$output = $formatter->format($resultSet);

		$this->assertStringContainsString($this->highlight('Bonjour'), $output);
		$this->assertStringNotContainsString('<body>', $output);
	}

	public function testInvalidQueries()
	{
		$this->assertResultCount(0, 'filterContent', 'in*lid');
		$this->assertResultCount(0, 'filterContent', 'i?lid');
	}

	public function testMatchInitial()
	{
		$this->assertResultCount(1, 'filterInitial', 'a description for', 'description');
		$this->assertResultCount(0, 'filterInitial', 'a description about', 'description');

		$this->assertResultCount(1, 'filterInitial', 'HomePage');
		$this->assertResultCount(1, 'filterInitial', 'Home');
		$this->assertResultCount(0, 'filterInitial', 'Fuzzy');
		$this->assertResultCount(0, 'filterInitial', 'Ham');
		$this->assertResultCount(0, 'filterInitial', 'HomePagd');
		$this->assertResultCount(0, 'filterInitial', 'Home Page');
	}

	public function testNotMatchInitial()
	{
		$this->assertResultCount(0, 'filterNotInitial', 'a description for', 'description');
		$this->assertResultCount(1, 'filterNotInitial', 'a description about', 'description');

		$this->assertResultCount(0, 'filterNotInitial', 'HomePage');
		$this->assertResultCount(0, 'filterNotInitial', 'Home');
		$this->assertResultCount(1, 'filterNotInitial', 'Fuzzy');
		$this->assertResultCount(1, 'filterNotInitial', 'Ham');
		$this->assertResultCount(1, 'filterNotInitial', 'HomePagd');
		$this->assertResultCount(1, 'filterNotInitial', 'Home Page');
	}

	public function testFilterRelations()
	{
		$about = new Search_Query_Relation('tiki.content.link', 'wiki page', 'About');
		$contact = new Search_Query_Relation('tiki.content.link', 'wiki page', 'Contact');
		$product = new Search_Query_Relation('tiki.content.link.invert', 'wiki page', 'Product');
		$user = new Search_Query_Relation('tiki.user.favorite.invert', 'user', 'bob');
		$nothing = new Search_Query_Relation('foo.bar.baz', 'trackeritem', '2');

		$invert_product = new Search_Query_Relation('tiki.content.link', 'wiki page', 'Product');
		$invert_user = new Search_Query_Relation('tiki.user.favorite', 'user', 'bob');

		$this->assertResultCount(0, 'filterRelation', "$nothing");
		$this->assertResultCount(1, 'filterRelation', "$about");
		$this->assertResultCount(1, 'filterRelation', "$about or $product");
		$this->assertResultCount(1, 'filterRelation', "$user and not $nothing");
		$this->assertResultCount(0, 'filterRelation', "$user and not $about");

		$this->assertResultCount(1, 'filterRelation', "$invert_product and $invert_user", ['tiki.content.link', 'tiki.user.favorite']);
	}

	private function assertResultCount($count, $filterMethod, $argument)
	{
		$arguments = func_get_args();
		$arguments = array_slice($arguments, 2);

		$query = new Search_Query;
		// add something positive  to search as Lucene negative only search returns no results
		if ($filterMethod === 'filterNotInitial') {
			$query->filterContent('description');
		}
		call_user_func_array([$query, $filterMethod], $arguments);

		$this->assertCount($count, $query->search($this->index));
	}

	protected function highlight($word)
	{
		return '<b class="highlight_word highlight_word_1">' . $word . '</b>';
	}
}
