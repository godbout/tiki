<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class CommentsTest extends TikiTestCase
{
    private $lib;

    protected function setUp() : void
    {
        $this->lib = TikiLib::lib('comments');
    }

    public function testGetHref(): void
    {
        $this->assertEquals('tiki-index.php?page=HomePage&amp;threadId=9&amp;comzone=show#threadId9', $this->lib->getHref('wiki page', 'HomePage', 9));
        $this->assertEquals('tiki-view_blog_post.php?postId=1&amp;threadId=10&amp;comzone=show#threadId10', $this->lib->getHref('blog post', 1, 10));
    }

    public function testGetRootPath(): void
    {
        $comments = $this->lib->table('tiki_comments');
        $parentId = $comments->insert([
            'objectType' => 'trackeritem',
            'object' => 1,
            'parentId' => 0
        ]);
        $childId = $comments->insert([
            'objectType' => 'trackeritem',
            'object' => 1,
            'parentId' => $parentId
        ]);
        $this->assertEquals([], $this->lib->get_root_path($parentId));
        $this->assertEquals([$parentId], $this->lib->get_root_path($childId));
        $comments->delete(['threadId' => $childId]);
        $comments->delete(['threadId' => $parentId]);
    }
}
