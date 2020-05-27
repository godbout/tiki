<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/tikiimporter_testcase.php');
require_once(__DIR__ . '/../../importer/tikiimporter_blog.php');

/**
 * @group importer
 */
class TikiImporter_Blog_Test extends TikiImporter_TestCase
{

	protected function setUp() : void
	{
		$this->obj = new TikiImporter_Blog();
	}

	public function testImportShouldCallMethodsToStartImportProcess(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['parseData', 'insertData', 'setupTiki'])
			->getMock();
		$obj->expects($this->once())->method('parseData');
		$obj->expects($this->once())->method('insertData');
		$obj->expects($this->once())->method('setupTiki');

		$obj->import();

		$output = ob_get_clean();
		$this->assertEquals("\nImportation completed!\n\n<b><a href=\"tiki-importer.php\">Click here</a> to finish the import process</b>", $output);
	}

	public function testImportShouldSetSessionVariables(): void
	{
		ob_start();

		$expectedImportFeedback = ['importedPages' => 10, 'totalPages' => '13'];
		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['parseData', 'insertData', 'saveAndDisplayLog', 'setupTiki'])
			->getMock();
		$obj->expects($this->once())->method('parseData');
		$obj->expects($this->once())->method('insertData')->willReturn($expectedImportFeedback);
		$obj->expects($this->once())->method('saveAndDisplayLog');
		$obj->expects($this->once())->method('setupTiki');

		$obj->log = 'some log string';
		$obj->import();

		$this->assertEquals($expectedImportFeedback, $_SESSION['tiki_importer_feedback']);
		$this->assertEquals('some log string', $_SESSION['tiki_importer_log']);

		ob_get_clean();
	}

	public function testInsertData_shouldCallInsertItemSixTimes(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertItem', 'createBlog'])
			->getMock();
		$obj->expects($this->once())->method('createBlog');
		$obj->expects($this->exactly(6))->method('insertItem');

				$obj->permalinks = ['not empty'];

				$obj->parsedData = [
			'pages' => [
				['type' => 'page', 'name' => 'Any name'],
				['type' => 'page', 'name' => 'Any name'],
			],
			'posts' => [
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
			],
			'tags' => [],
			'categories' => [],
				];

				$obj->insertData();

				ob_get_clean();
	}

	public function testInsertData_shouldNotCallInsertItem(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertItem'])
			->getMock();
		$obj->expects($this->never())->method('insertItem');
		$obj->parsedData = [
			'pages' => [],
			'posts' => [],
			'tags' => [],
			'categories' => [],
		];
		$obj->insertData();

		ob_get_clean();
	}

	public function testInsertData_shouldReturnCountData(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertItem', 'createBlog'])
			->getMock();
		$obj->expects($this->once())->method('createBlog');
		$obj->expects($this->exactly(6))->method('insertItem')->willReturnOnConsecutiveCalls(true, true, true, true, false, true);

		$obj->permalinks = ['not empty'];

		$obj->parsedData = [
			'pages' => [
				['type' => 'page', 'name' => 'Any name'],
				['type' => 'page', 'name' => 'Any name'],
			],
			'posts' => [
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
				['type' => 'post', 'name' => 'Any name'],
			],
			'tags' => [],
			'categories' => [],
		];

		$countData = $obj->insertData();
		$expectedResult = ['importedPages' => 1, 'importedPosts' => 4, 'importedTags' => 0, 'importedCategories' => 0];

		$this->assertEquals($expectedResult, $countData);

		ob_get_clean();
	}

	public function testInsertData_shouldNotCreateBlogIfNoPosts(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertItem', 'createTags', 'createCategories', 'createBlog'])
			->getMock();
		$obj->expects($this->exactly(0))->method('insertItem');
		$obj->expects($this->exactly(0))->method('createTags');
		$obj->expects($this->exactly(0))->method('createCategories');
		$obj->expects($this->exactly(0))->method('createBlog');

		$obj->parsedData = [
			'pages' => [],
			'posts' => [],
			'tags' => [],
			'categories' => [],
		];

		$countData = $obj->insertData();
		$expectedResult = ['importedPages' => 0, 'importedPosts' => 0, 'importedTags' => 0, 'importedCategories' => 0];

		$this->assertEquals($expectedResult, $countData);

		ob_get_clean();
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertItem_shouldCallInsertCommentsForPage(): void
	{
		$this->markTestSkipped("As of 2013-09-30, this test is broken. Skipping it for now.");
		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertComments', 'insertPage'])
			->getMock();
		$obj->expects($this->once())->method('insertComments')->with('Any name', 'wiki page');
		$obj->expects($this->once())->method('insertPage')->willReturnOnConsecutiveCalls(true);

		$page = ['type' => 'page', 'name' => 'Any name', 'comments' => [1, 2, 3]];

		$obj->insertItem($page);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertItem_shouldCallInsertCommentsForPost(): void
	{
		$this->markTestSkipped("As of 2013-09-30, this test is broken. Skipping it for now.");
		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertComments', 'insertPost'])
			->getMock();
		$obj->expects($this->once())->method('insertComments')->with('Any name', 'blog post');
		$obj->expects($this->once())->method('insertPost')->willReturnOnConsecutiveCalls(true);

				$post = ['type' => 'post', 'name' => 'Any name', 'comments' => [1, 2]];

		$obj->insertItem($post);
	}

	public function testInsertItem_shouldReturnObjId(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertComments', 'insertPost'])
			->getMock();
		$obj->expects($this->once())->method('insertComments')->with(22, 'blog post', [1, 2]);
		$obj->expects($this->once())->method('insertPost')->willReturnOnConsecutiveCalls(22);

				$post = ['type' => 'post', 'name' => 'Any name', 'comments' => [1, 2]];

		$objId = $obj->insertItem($post);
		$this->assertEquals(22, $objId);

		ob_get_clean();
	}

	public function testInsertItem_shoudReturnNull(): void
	{
		ob_start();

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['insertComments', 'insertPost'])
			->getMock();
		$obj->expects($this->exactly(0))->method('insertComments');
		$obj->expects($this->once())->method('insertPost')->willReturnOnConsecutiveCalls(null);

				$post = ['type' => 'post', 'name' => 'Any name', 'comments' => [1, 2]];

		$objId = $obj->insertItem($post);
		$this->assertEquals(null, $objId);

		ob_get_clean();
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertComments(): void
	{
		$this->markTestSkipped("As of 2013-09-30, this test is broken. Skipping it for now.");

		$commentslib = $this->getMockBuilder('Comments')
			->onlyMethods(['post_new_comment'])
			->getMock();
		$commentslib->expects($this->exactly(2))
							->method('post_new_comment')
							->with('wiki page:2', 0, null, '', 'asdf', '', '', 'n', '', '', '', '', 1234, '', '');

		$comments = [
			['data' => 'asdf', 'created' => 1234, 'approved' => 1],
			['data' => 'asdf', 'created' => 1234, 'approved' => 1],
		];

		$this->obj->insertComments(2, 'wiki page', $comments);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertCommentsShouldConsiderIfCommentIsApprovedOrNot(): void
	{
		$this->markTestSkipped("As of 2013-09-30, this test is broken. Skipping it for now.");

		$commentslib = $this->getMockBuilder('Comments')
			->onlyMethods(['post_new_comment', 'approve_comment'])
			->getMock();
		$commentslib->expects($this->exactly(2))
						->method('post_new_comment')
						->with('wiki page:2', 0, null, '', 'asdf', '', '', 'n', '', '', '', '', 1234, '', '')->willReturn(22);
		$commentslib->expects($this->once())->method('approve_comment')->with(22, 'n');

		$comments = [
			['data' => 'asdf', 'created' => 1234, 'approved' => 1],
			['data' => 'asdf', 'created' => 1234, 'approved' => 0],
		];

		$this->obj->insertComments(2, 'wiki page', $comments);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertPage(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$objectlib = $this->getMockBuilder('ObjectLib')
			->onlyMethods(['insert_object'])
			->getMock();
		$objectlib->expects($this->once())->method('insert_object');

		$importerWiki = $this->getMockBuilder('TikiImporter_Wiki')
			->onlyMethods(['insertPage'])
			->getMock();
		$importerWiki->expects($this->once())->method('insertPage')->willReturn('HomePage');

		$obj = $this->getMockBuilder('TikiImporter_Blog')
			->onlyMethods(['instantiateImporterWiki'])
			->getMock();
		$obj->expects($this->once())->method('instantiateImporterWiki');

		$obj->importerWiki = $importerWiki;

		$obj->insertPage([]);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testInsertPost(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$bloglib = $this->getMockBuilder('BlogLib')
			->onlyMethods(['blog_post'])
			->getMock();
		$bloglib->expects($this->once())->method('blog_post')->willReturn(1);

		$objectlib = $this->getMockBuilder('ObjectLib')
			->onlyMethods(['insert_object'])
			->getMock();
		$objectlib->expects($this->once())->method('insert_object');

		$post = ['content' => 'asdf', 'excerpt' => '', 'author' => 'admin', 'name' => 'blog post title', 'created' => 1234];

		$this->obj->insertPost($post);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testCreateTags(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$freetaglib = $this->getMockBuilder('FreetagLib')
			->onlyMethods(['find_or_create_tag'])
			->getMock();
		$freetaglib->expects($this->exactly(4))->method('find_or_create_tag');

		$tags = ['tag1', 'tag2', 'tag3', 'tag4'];

		$this->obj->createTags($tags);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testCreateCategories(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$categlib = $this->getMockBuilder('CategLib')
			->onlyMethods(['add_category', 'get_category_id'])
			->getMock();
		$categlib->expects($this->exactly(3))->method('add_category');
		$categlib->expects($this->once())->method('get_category_id');

		$categories = [
			['parent' => '', 'name' => 'categ1', 'description' => ''],
			['parent' => '', 'name' => 'categ2', 'description' => ''],
			['parent' => 'categ1', 'name' => 'categ3', 'description' => ''],
		];

		$this->obj->createCategories($categories);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testLinkObjectWithTags(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$freetaglib = $this->getMockBuilder('FreetagLib')
			->onlyMethods(['_tag_object_array'])
			->getMock();
		$freetaglib->expects($this->once())->method('_tag_object_array');

		$tags = ['tag1', 'tag2', 'tag3', 'tag4'];

		$this->obj->linkObjectWithTags('HomePage', 'wiki page', $tags);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testLinkObjectWithCategories(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$categlib = $this->getMockBuilder('CategLib')
			->onlyMethods(['get_category_id', 'get_object_id', 'categorize', 'add_categorized_object'])
			->getMock();
		$categlib->expects($this->exactly(4))->method('get_category_id');
		$categlib->expects($this->exactly(4))->method('get_category_id');
		$categlib->expects($this->exactly(4))->method('get_category_id');
		$categlib->expects($this->exactly(4))->method('add_categorized_object');

		$categs = ['categ1', 'categ2', 'categ3', 'categ4'];

		$this->obj->linkObjectWithCategories('HomePage', 'wiki page', $categs);
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testCreateBlog(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$bloglib = $this->getMockBuilder('BlogLib')
			->onlyMethods(['replace_blog'])
			->getMock();
		$bloglib->expects($this->once())->method('replace_blog');

		$this->obj->blogInfo = ['title' => 'Test title', 'desc' => 'Test description', 'lastModif' => 12345];

		$this->obj->createBlog();
	}

	/**
	 * @group marked-as-skipped
	 */
	public function testCreateBlogShouldSetBlogAsHomepage(): void
	{
		$this->markTestSkipped('2016-09-26 Skipped as dependency injection has stopped mock objects working like this.');

		$bloglib = $this->getMockBuilder('BlogLib')
			->onlyMethods(['replace_blog'])
			->getMock();
		$bloglib->expects($this->once())->method('replace_blog');

		$tikilib = $this->getMockBuilder('TikiLib')
			->onlyMethods(['set_preference'])
			->getMock();
		$tikilib->expects($this->exactly(2))->method('set_preference');

		$this->obj->blogInfo = ['title' => 'Test title', 'desc' => 'Test description', 'lastModif' => 12345];

		$_REQUEST['setAsHomePage'] = 'on';

		$this->obj->createBlog();

		unset($_REQUEST['setAsHomePage']);
	}
}
