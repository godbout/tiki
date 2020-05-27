<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class UserWatchesTest extends TikiTestCase
{

	private $lib;

	protected function setUp() : void
	{
		$this->lib = TikiLib::lib('tiki');
		$this->userWatches = $this->lib->table('tiki_user_watches');
		$this->userWatches->insert([
		'user' => 'tester',
		'event' => 'thread_comment_replied',
		'object' => 1
		]);
		$this->userWatches->insert([
		'user' => 'tester',
		'event' => 'thread_comment_replied',
		'object' => 2
		]);
	}

	protected function tearDown() : void
	{
		$this->userWatches->deleteMultiple(['user' => 'tester']);
	}

	public function testGetUserEventWatches(): void
	{
		$set1 = $this->lib->get_user_event_watches('tester', 'thread_comment_replied', 1);
		$set2 = $this->lib->get_user_event_watches('tester', 'thread_comment_replied', [1, 2]);
		$set3 = $this->lib->get_user_event_watches('tester', 'thread_comment_replied', 33);
		$this->assertCount(1, $set1);
		$this->assertCount(2, $set2);
		$this->assertCount(0, $set3);
	}

	public function testGetEventWatches(): void
	{
		$watches = $this->lib->get_event_watches('thread_comment_replied', 1);
		$this->assertCount(1, $watches);
		$this->assertEquals('tester', $watches[0]['user']);
		$watches = $this->lib->get_event_watches('wiki_comment_changes', 'Test Page');
		$this->assertCount(0, $watches);
	}
}
