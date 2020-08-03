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
class Perms_BaseTest extends TikiTestCase
{
    public function testWithoutConfiguration()
    {
        Perms::set(new Perms);
        $accessor = Perms::get();

        $expect = new Perms_Accessor;

        $this->assertEquals($expect, $accessor);
    }

    public function testGroupsPropagateToAccessor()
    {
        $perms = new Perms;
        $perms->setGroups(['Registered', 'Administrator']);
        Perms::set($perms);

        $expect = new Perms_Accessor;
        $expect->setGroups(['Registered', 'Administrator']);

        $this->assertEquals($expect, Perms::get());
    }

    public function testContextPropagatesToAccessor()
    {
        $accessor = Perms::get(['context']);

        $this->assertEquals(['context'], $accessor->getContext());
    }

    public function testWithoutArrayContext()
    {
        $expect = Perms::get(['type' => 'wiki page', 'object' => 'HomePage', 'parentId' => null]);
        $accessor = Perms::get('wiki page', 'HomePage');

        $this->assertEquals($expect, $accessor);
    }

    /**
     * @dataProvider resolverMatches
     * @param $context
     * @param $expectedResolver
     */
    public function testResolverFactoryChaining($context, $expectedResolver)
    {
        $perms = new Perms;

        $perms->setResolverFactories(
            [
                new Perms_ResolverFactory_TestFactory(
                    ['object'],
                    [
                        'test:a' => $rA = new Perms_Resolver_Default(true),
                        'test:b' => $rB = new Perms_Resolver_Default(true),
                    ]
                ),
                new Perms_ResolverFactory_TestFactory(
                    ['category'],
                    [
                        'test:1' => $r1 = new Perms_Resolver_Default(true),
                        'test:2' => $r2 = new Perms_Resolver_Default(true),
                    ]
                ),
                new Perms_ResolverFactory_TestFactory(
                    [],
                    ['test:' => $rG = new Perms_Resolver_Default(true), ]
                ),
            ]
        );
        Perms::set($perms);

        $this->assertSame($$expectedResolver, Perms::get($context)->getResolver());
    }

    public function resolverMatches()
    {
        return [
            'testObjectA' => [['object' => 'a'], 'rA'],
            'testObjectB' => [['object' => 'b'], 'rB'],
            'testCategoryIgnoredWhenObjectMatches' => [['object' => 'b', 'category' => '1'], 'rB'],
            'testCategoryObtainOnObjectMiss' => [['object' => 'c', 'category' => '1'], 'r1'],
            'testCategoryOnly' => [['category' => '2'], 'r2'],
            'testObjectAndCategoryMiss' => [['object' => 'd', 'category' => '3'], 'rG'],
            'testNoContext' => [[], 'rG'],
        ];
    }

    public function testResolverNotCalledTwiceWhenFound()
    {
        $mock = $this->createMock('Perms_ResolverFactory');

        $mock->expects($this->exactly(2))
            ->method('getHash')
            ->willReturn('123');

        $mock->expects($this->once())
            ->method('getResolver')
            ->willReturn(new Perms_Resolver_Default(true));

        $perms = new Perms;
        $perms->setResolverFactories([$mock, ]);
        Perms::set($perms);

        Perms::get();
        Perms::get();
    }

    public function testResolverNotCalledTwiceWhenNotFound()
    {
        $mock = $this->createMock('Perms_ResolverFactory');

        $mock->expects($this->exactly(2))
            ->method('getHash')
            ->willReturn('123');

        $mock->expects($this->once())
            ->method('getResolver')
            ->willReturn(false);

        $perms = new Perms;
        $perms->setResolverFactories([$mock, ]);
        Perms::set($perms);

        Perms::get();
        Perms::get();
    }

    public function testResolverCacheValidValues()
    {
        $defaultResolver = new Perms_Resolver_Default(true);

        $mock1 = $this->createMock('Perms_ResolverFactory');
        $mock2 = $this->createMock('Perms_ResolverFactory');

        $mock1->expects($this->exactly(2))
            ->method('getHash')
            ->willReturn('123');

        $mock2->expects($this->exactly(2))
            ->method('getHash')
            ->willReturn('456');

        $mock1->expects($this->once())
            ->method('getResolver')
            ->willReturn(false);
        $mock2->expects($this->once())
            ->method('getResolver')
            ->willReturn($defaultResolver);

        $perms = new Perms;
        $perms->setResolverFactories([$mock1, $mock2]);
        Perms::set($perms);

        // call 2 times to check hash cache contains valid values
        $perms = Perms::get();
        $this->assertSame($defaultResolver, $perms->getResolver());
        $perms = Perms::get();
        $this->assertSame($defaultResolver, $perms->getResolver());
    }

    public function testBulkLoading()
    {
        $mockObject = $this->createMock('Perms_ResolverFactory');
        $mockCategory = $this->createMock('Perms_ResolverFactory');
        $mockGlobal = $this->createMock('Perms_ResolverFactory');

        $perms = new Perms;
        $perms->setResolverFactories([$mockObject, $mockCategory, $mockGlobal]);
        Perms::set($perms);

        $mockObject->expects($this->once())
            ->method('bulk')
            ->with($this->equalTo(['type' => 'wiki page']), $this->equalTo('object'), $this->equalTo(['A', 'B', 'C', 'D', 'E']))
            ->willReturn(['A', 'C', 'E']);
        $mockCategory->expects($this->once())
            ->method('bulk')
            ->with($this->equalTo(['type' => 'wiki page']), $this->equalTo('object'), $this->equalTo(['A', 'C', 'E']))
            ->willReturn(['C']);
        $mockGlobal->expects($this->once())
            ->method('bulk')
            ->with($this->equalTo(['type' => 'wiki page']), $this->equalTo('object'), $this->equalTo(['C']))
            ->willreturnArgument(0);

        $data = [
            ['pageId' => 1, 'pageName' => 'A', 'content' => 'Hello World'],
            ['pageId' => 2, 'pageName' => 'B', 'content' => 'Hello World'],
            ['pageId' => 3, 'pageName' => 'C', 'content' => 'Hello World'],
            ['pageId' => 4, 'pageName' => 'D', 'content' => 'Hello World'],
            ['pageId' => 5, 'pageName' => 'E', 'content' => 'Hello World'],
        ];

        Perms::bulk(['type' => 'wiki page'], 'object', $data, 'pageName');
    }

    public function customHash($context)
    {
        return serialize($context);
    }

    public function testFiltering()
    {
        $perms = new Perms;
        $perms->setResolverFactories(
            [
                new Perms_ResolverFactory_TestFactory(
                    ['object'],
                    [
                        'test:A' => new Perms_Resolver_Default(true),
                        'test:B' => new Perms_Resolver_Default(true),
                        'test:C' => new Perms_Resolver_Default(false),
                        'test:D' => new Perms_Resolver_Default(false),
                        'test:E' => new Perms_Resolver_Default(true),
                    ]
                ),
            ]
        );
        Perms::set($perms);

        $data = [
            ['pageId' => 1, 'pageName' => 'A', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 2, 'pageName' => 'B', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 3, 'pageName' => 'C', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 4, 'pageName' => 'D', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 5, 'pageName' => 'E', 'content' => 'Hello World', 'creator' => 'admin'],
        ];

        $out = Perms::filter(
            ['type' => 'wiki page'],
            'object',
            $data,
            ['object' => 'pageName', 'creator' => 'creator'],
            'view'
        );

        $expect = [
            ['pageId' => 1, 'pageName' => 'A', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 2, 'pageName' => 'B', 'content' => 'Hello World', 'creator' => 'admin'],
            ['pageId' => 5, 'pageName' => 'E', 'content' => 'Hello World', 'creator' => 'admin'],
        ];

        $this->assertEquals($expect, $out);
    }

    public function testContextBuilding()
    {
        $perms = new Perms;
        $perms->setResolverFactories(
            [$mock = $this->createMock('Perms_ResolverFactory')]
        );
        Perms::set($perms);

        $mock->expects($this->once())
            ->method('getHash')
            ->with($this->equalTo(['type' => 'wiki page', 'object' => 'Hello World', 'creator' => 'admin']))
            ->willReturn(null);
        $mock->expects($this->once())
            ->method('bulk');

        $data = [
            ['pageId' => 1, 'pageName' => 'Hello World', 'content' => 'Hello World', 'creator' => 'admin'],
        ];

        Perms::filter(['type' => 'wiki page'], 'object', $data, ['object' => 'pageName', 'creator' => 'creator'], 'view');
    }

    public function testSkipBulkOnEmptySet()
    {
        $perms = new Perms;
        $perms->setResolverFactories(
            [$mock = $this->createMock('Perms_ResolverFactory')]
        );
        Perms::set($perms);

        $mock->expects($this->never())
            ->method('bulk');
    }
}
