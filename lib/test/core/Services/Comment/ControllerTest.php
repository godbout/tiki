<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Comment_ControllerTest extends PHPUnit\Framework\TestCase
{
    protected $subject;

    protected function setUp() : void
    {
        global $prefs, $user;
        $user = '';
        $prefs['feature_absolute_to_relative_links'] = 'n';
        $prefs['feature_article_comments'] = 'y';
        $prefs['feature_antibot'] = 'n';
        $prefs['feature_user_watches'] = 'y';
        $prefs['login_is_email'] = 'y';
        $_SERVER["SERVER_NAME"] = 'test.example.org';

        $perms = new Perms;
        $perms->setResolverFactories(
            [
                new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(true)),
            ]
        );
        Perms::set($perms);

        $this->subject = new Services_Comment_Controller;
    }

    public function testPostSimpleComment()
    {
        $input = new JitFilter(array_merge(
            $this->commentInput(),
            [
                'anonymous_name' => 'tom',
                'anonymous_email' => 'tom@example.org'
            ]
        ));
        $result = $this->subject->action_post($input);
        $this->assertGreaterThan(0, $result['threadId']);
        $this->assertEquals('article', $result['type']);
        TikiLib::lib('tiki')->table('tiki_comments')->delete(['threadId' => $result['threadId']]);
    }

    public function testPostCommentWithAnonymousWatch()
    {
        $input = new JitFilter(array_merge(
            $this->commentInput(),
            [
                'anonymous_name' => 'tom',
                'anonymous_email' => 'tom@example.org',
                'watch' => 'y'
            ]
        ));
        $result = $this->subject->action_post($input);
        $this->assertGreaterThan(0, $result['threadId']);
        $this->assertEquals('article', $result['type']);
        $tiki = TikiLib::lib('tiki');
        $user_watch = $tiki->table('tiki_user_watches')->fetchFullRow([
            'object' => $result['threadId'],
            'event' => 'thread_comment_replied'
        ]);
        $this->assertArrayHasKey('email', $user_watch);
        $this->assertEquals('tom@example.org', $user_watch['email']);
        $tiki->table('tiki_comments')->delete(['threadId' => $result['threadId']]);
        $tiki->table('tiki_user_watches')->delete([
            'object' => $result['threadId'],
            'event' => 'thread_comment_replied'
        ]);
    }

    public function testPostMultipleCommentsAndEnsureSingleWatch()
    {
        global $user, $prefs;
        $user = 'tester@example.org';

        /** @var UsersLib $userLib */
        $userLib = TikiLib::lib('user');
        if (! $userLib->user_exists($user)) {
            $userLib->add_user($user, $user, $user); // ensure user exists
        }

        $input = new JitFilter(array_merge(
            $this->commentInput(),
            [
                'watch' => 'y'
            ]
        ));
        $result = $this->subject->action_post($input);
        $threads = [$result['threadId']];

        $input['title'] = 'This is a reply';
        $input['data'] = 'Test reply ' . uniqid("", true);
        $input['parentId'] = $result['threadId'];
        $result = $this->subject->action_post($input);
        $threads[] = $result['threadId'];

        $tiki = TikiLib::lib('tiki');
        $user_watches = $tiki->get_user_event_watches($user, 'thread_comment_replied', $threads);
        $this->assertCount(1, $user_watches);
        $this->assertEquals('tester@example.org', $user_watches[0]['user']);
        $this->assertEquals($threads[0], $user_watches[0]['object']);

        $tiki->table('tiki_comments')->delete(['threadId' => $threads[0]]);
        $tiki->table('tiki_comments')->delete(['threadId' => $threads[1]]);
        $tiki->table('tiki_user_watches')->deleteMultiple([
            'user' => 'tester@example.org',
            'event' => 'thread_comment_replied'
        ]);
    }

    protected function commentInput()
    {
        return [
            'post' => 1,
            'type' => 'article',
            'objectId' => 1,
            'parentId' => 0,
            'title' => 'Simple comment',
            'data' => 'This is a test ' . uniqid("", true),
        ];
    }
}
