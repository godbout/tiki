<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */

class Perms_ResolverFactory_ObjectFactoryTest extends PHPUnit_Framework_TestCase
{
	private $tableData = [];

	private function backupTable($name)
	{
		$this->tableData[$name] = [];

		$db = TikiDb::get();

		$result = $db->query('SELECT * FROM ' . $name);
		while ($row = $result->fetchRow()) {
			$this->tableData[$name][] = $row;
		}

		$db->query('DELETE FROM ' . $name);
	}

	private function restoreTable($name)
	{
		$db = TikiDb::get();

		$db->query('DELETE FROM ' . $name);

		foreach ($this->tableData[$name] as $row) {
			$db->query('INSERT INTO ' . $name . ' VALUES(?' . str_repeat(',?', count($row) - 1) . ')', array_values($row));
		}
	}

	function setUp()
	{
		$this->backupTable('users_objectpermissions');
		$this->backupTable('tiki_tracker_items');
		$this->backupTable('tiki_files');
		$this->backupTable('tiki_articles');
		$this->backupTable('tiki_blog_posts');
		$this->backupTable('tiki_comments');
		$this->backupTable('tiki_calendar_items');
	}

	function tearDown()
	{
		$this->restoreTable('users_objectpermissions');
		$this->restoreTable('tiki_tracker_items');
		$this->restoreTable('tiki_files');
		$this->restoreTable('tiki_articles');
		$this->restoreTable('tiki_blog_posts');
		$this->restoreTable('tiki_comments');
		$this->restoreTable('tiki_calendar_items');
	}

	function testHash()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;

		$this->assertEquals('object:wiki page:homepage', $factory->getHash(['type' => 'wiki page', 'object' => 'HomePage']));
	}

	function testHashParent()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$this->assertEquals('object:trackeritemparent:12', $factory->getHash(['type' => 'trackeritem', 'object' => '12']));
	}

	function testHashParentId()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$this->assertEquals('object:tracker:1', $factory->getHash(['type' => 'trackeritem', 'object' => '12', 'parentId' => 1]));
	}

	function testHashMissingType()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;
		$this->assertEquals('', $factory->getHash(['object' => 'HomePage']));
	}

	function testHashMissingObject()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;
		$this->assertEquals('', $factory->getHash(['type' => 'wiki page']));
	}

	function testObtainPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_view', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_edit', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'blog', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'wiki page', md5('wiki pageuserlist')],
			['Admins', 'tiki_p_admin', 'wiki page', md5('wiki pagehomepage')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$factory = new Perms_ResolverFactory_ObjectFactory;

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['admin'],
				'Anonymous' => ['edit', 'view'],
			],
			'object'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'wiki page', 'object' => 'HomePage']));
	}

	function testObtainParentTrackerPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_tracker_view', 'tracker', md5('tracker1')],
			['Anonymous', 'tiki_p_modify_object_categories', 'tracker', md5('tracker1')],
			['Admins', 'tiki_p_tracker_admin', 'tracker', md5('tracker1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_tracker_items (itemId, trackerId) VALUES(2,1), (3,1)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['tracker_admin'],
				'Anonymous' => ['modify_object_categories', 'tracker_view'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'trackeritem', 'object' => 2]));
	}

	function testObtainParentFileGalleryPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_view_file_gallery', 'file gallery', md5('file gallery1')],
			['Registered', 'tiki_p_edit_gallery_file', 'file gallery', md5('file gallery1')],
			['Admins', 'tiki_p_remove_files', 'file gallery', md5('file gallery1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_files (fileId, galleryId) VALUES(2,1), (3,1)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['remove_files'],
				'Registered' => ['edit_gallery_file'],
				'Anonymous' => ['view_file_gallery'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'file', 'object' => 2]));
	}

	function testObtainParentTopicPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_read_article', 'topic', md5('topic1')],
			['Registered', 'tiki_p_edit_article', 'topic', md5('topic1')],
			['Admins', 'tiki_p_remove_article', 'topic', md5('topic1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_articles (articleId, topicId) VALUES(2,1), (3,1)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['remove_article'],
				'Registered' => ['edit_article'],
				'Anonymous' => ['read_article'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'article', 'object' => 2]));
	}

	function testObtainParentBlogPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_read_blog', 'blog', md5('blog1')],
			['Registered', 'tiki_p_blog_post', 'blog', md5('blog1')],
			['Admins', 'tiki_p_blog_admin', 'blog', md5('blog1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_blog_posts (postId, blogId) VALUES(2,1), (3,1)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['blog_admin'],
				'Registered' => ['blog_post'],
				'Anonymous' => ['read_blog'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'blog post', 'object' => 2]));
	}

	function testObtainParentForumPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_forum_read', 'forum', md5('forum1')],
			['Registered', 'tiki_p_forum_post', 'forum', md5('forum1')],
			['Admins', 'tiki_p_admin_forum', 'forum', md5('forum1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_comments (threadId, object, objectType) VALUES(2,1,'forum'), (3,1,'forum')");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['admin_forum'],
				'Registered' => ['forum_post'],
				'Anonymous' => ['forum_read'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'thread', 'object' => 2]));
	}

	function testObtainParentCalendarPermissions()
	{
		$data = [
			['Anonymous', 'tiki_p_view_calendar', 'calendar', md5('calendar1')],
			['Registered', 'tiki_p_view_events', 'calendar', md5('calendar1')],
			['Admins', 'tiki_p_change_events', 'calendar', md5('calendar1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_calendar_items (calitemId, calendarId) VALUES(2,1), (3,1)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$expect = new Perms_Resolver_Static(
			[
				'Admins' => ['change_events'],
				'Registered' => ['view_events'],
				'Anonymous' => ['view_calendar'],
			],
			'parent'
		);

		$this->assertEquals($expect, $factory->getResolver(['type' => 'event', 'object' => 2]));
	}

	function testObtainPermissionsWhenNoneSpecific()
	{
		$data = [
			['Anonymous', 'tiki_p_view', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_edit', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'blog', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'wiki page', md5('wiki pageuserlist')],
			['Admins', 'tiki_p_admin', 'wiki page', md5('wiki pagehomepage')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$factory = new Perms_ResolverFactory_ObjectFactory;

		$this->assertNull($factory->getResolver(['type' => 'blog', 'object' => '234']));
	}

	function testObtainParentPermissionsWhenNoneSpecific()
	{
		$data = [
			['Anonymous', 'tiki_p_tracker_view', 'tracker', md5('tracker1')],
			['Anonymous', 'tiki_p_modify_object_categories', 'tracker', md5('tracker1')],
			['Admins', 'tiki_p_tracker_admin', 'tracker', md5('tracker1')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$db->query("INSERT INTO tiki_tracker_items (itemId, trackerId) VALUES(2,5)");

		$factory = new Perms_ResolverFactory_ObjectFactory('parent');

		$this->assertNull($factory->getResolver(['type' => 'trackeritem', 'object' => 2]));
	}

	function testObtainResolverIncompleteContext()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;

		$this->assertNull($factory->getResolver(['type' => 'wiki page']));
		$this->assertNull($factory->getResolver(['object' => 'HomePage']));
	}

	function testBulkLoading()
	{
		$data = [
			['Anonymous', 'tiki_p_view', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_edit', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'blog', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'wiki page', md5('wiki pageuserlist')],
			['Admins', 'tiki_p_admin', 'wiki page', md5('wiki pagehomepage')],
			['Anonymous', 'tiki_p_admin', 'wiki page', md5('wiki pageuserpagefoobar')],
		];

		$db = TikiDb::get();
		foreach ($data as $row) {
			$db->query('INSERT INTO users_objectpermissions (groupName, permName, objectType, objectId) VALUES(?,?,?,?)', array_values($row));
		}

		$factory = new Perms_ResolverFactory_ObjectFactory;
		$out = $factory->bulk(['type' => 'wiki page'], 'object', ['HomePage', 'UserPageFoobar', 'HelloWorld']);

		$this->assertEquals(['HelloWorld'], $out);
	}

	function testBulkLoadingWithoutObject()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;
		$out = $factory->bulk(['type' => 'wiki page'], 'objectId', ['HomePage', 'UserPageFoobar', 'HelloWorld']);

		$this->assertEquals(['HomePage', 'UserPageFoobar', 'HelloWorld'], $out);
	}

	function testBulkLoadingWithoutType()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory;
		$out = $factory->bulk([], 'object', ['HomePage', 'UserPageFoobar', 'HelloWorld']);

		$this->assertEquals(['HomePage', 'UserPageFoobar', 'HelloWorld'], $out);
	}

	function testBulkLoadingParentWithWrongType()
	{
		$factory = new Perms_ResolverFactory_ObjectFactory('parent');
		$out = $factory->bulk(['type' => 'wiki page'], 'object', ['HomePage', 'UserPageFoobar', 'HelloWorld']);

		$this->assertEquals(['HomePage', 'UserPageFoobar', 'HelloWorld'], $out);
	}
}
