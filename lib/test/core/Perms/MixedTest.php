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

class Perms_MixedTest extends TikiTestCase
{
	public function testFilterMixed()
	{
		$perms = new Perms;
		$perms->setResolverFactories(
			[
				$resolver = $this->createMock('Perms_ResolverFactory'),
				new Perms_ResolverFactory_StaticFactory('global', new Perms_Resolver_Default(true)),
			]
		);
		Perms::set($perms);

		$resolver
			->method('getResolver')
			->willReturn(null);
		$resolver->expects($this->exactly(3))
			->method('bulk')
			->willReturn([]);
		$resolver->expects($this->at(0))
			->method('bulk')
			->willReturn([])
			->with(
				$this->equalTo(['type' => 'wiki page']),
				$this->equalTo('object'),
				$this->equalTo(['A', 'B'])
			);
		$resolver->expects($this->at(1))
			->method('bulk')
			->willReturn([])
			->with(
				$this->equalTo(['type' => 'category']),
				$this->equalTo('object'),
				$this->equalTo([10])
			);

		$data = [
			['type' => 'wiki page', 'object' => 'A', 'creator' => 'abc'],
			['type' => 'wiki page', 'object' => 'B', 'creator' => 'abc'],
			['type' => 'category', 'object' => 10],
			['type' => 'forumPost', 'object' => 12, 'author' => 'author'],
		];

		$out = Perms::mixedFilter(
			[],
			'type',
			'object',
			$data,
			[
				'wiki page' => ['object' => 'object', 'type' => 'type', 'creator' => 'creator'],
				'category' => ['object' => 'object', 'type' => 'type'],
				'forumPost' => ['object' => 'object', 'type' => 'type', 'creator' => 'author'],
			],
			[
				'wiki page' => 'view',
				'category' => 'view_categories',
				'forumPost' => 'forum_post',
			]
		);

		$this->assertEquals($data, $out);
	}
}
