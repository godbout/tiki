<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
class Rating_AggregationTest extends TikiTestCase
{
	protected $ratingDefaultOptions;
	protected $ratingAllowMultipleVotes;

	protected function setUp() : void
	{
		global $user, $testhelpers, $prefs;

		$user = null;

		$tikilib = $this->createMock('TikiLib');
		$tikilib->method('get_ip_address')->willReturn('127.0.0.1');

		$testableTikiLib = new TestableTikiLib;
		$testableTikiLib->overrideLibs(['tiki' => $tikilib]);

		parent::setUp();
		TikiDb::get()->query('DELETE FROM `tiki_user_votings` WHERE `id` LIKE ?', ['test.%']);

		$testhelpers = new TestHelpers();

		$this->ratingDefaultOptions = $prefs['rating_default_options'];
		$prefs['rating_default_options'] = '0,1,2,3,4,5';
		$this->ratingAllowMultipleVotes = $prefs['rating_allow_multi_votes'];
		$prefs['rating_allow_multi_votes'] = 'y';
	}

	protected function tearDown() : void
	{
		global $testhelpers, $user, $prefs;
		$user = null;
		parent::tearDown();
		TikiDb::get()->query('DELETE FROM `tiki_user_votings` WHERE `id` LIKE ?', ['test.%']);

		$testhelpers->reset_all();
		$prefs['rating_default_options'] = $this->ratingDefaultOptions;
		$prefs['rating_allow_multi_votes'] = $this->ratingAllowMultipleVotes;
	}

	public function testGetGlobalSum(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 4, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(9.0, $lib->collect('test', 111, 'sum'));
	}

	public function testGetGlobalSumSingleVote(): void
	{
		global $prefs;
		$prefs['rating_allow_multi_votes'] = '';

		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 4, time() - 3000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(5.0, $lib->collect('test', 111, 'sum'));
	}

	public function testSumWithNoData(): void
	{
		$lib = new RatingLib;

		$this->assertEquals(0.0, $lib->collect('test', 111, 'sum'));
	}

	public function testAverageWithNoData(): void
	{
		$lib = new RatingLib;

		$this->assertEquals(0.0, $lib->collect('test', 111, 'avg'));
	}

	public function testGetGlobalAverage(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEqualsWithDelta(10 / 3, $lib->collect('test', 111, 'avg'), 1 / 1000);
	}

	public function testGetGlobalAverageSingleVote(): void
	{
		global $prefs;
		$prefs['rating_allow_multi_votes'] = '';

		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEqualsWithDelta(5 / 2, $lib->collect('test', 111, 'avg'), 1 / 1000);
	}

	public function testBadAggregateFunction(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertFalse($lib->collect('test', 111, 'foobar'));
	}

	public function testTimeRangeLimiter(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 112, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(5.0, $lib->collect('test', 111, 'sum', ['range' => 2500]));
	}

	public function testIgnoreAnonymous(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(10.0, $lib->collect('test', 111, 'sum', ['ignore' => 'anonymous']));
	}

	public function testIgnoreAnonymousSingleVote(): void
	{
		global $prefs;
		$prefs['rating_allow_multi_votes'] = '';

		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(3.0, $lib->collect('test', 111, 'sum', ['ignore' => 'anonymous']));
	}

	public function testKeepLatest(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1500);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(6.0, $lib->collect('test', 111, 'sum', ['keep' => 'latest']));

		$this->assertEquals(3.0, $lib->collect('test', 111, 'sum', ['keep' => 'latest', 'range' => 1200]));

		$this->assertEquals(0.0, $lib->collect('test', 111, 'sum', ['keep' => 'latest', 'range' => 1200,	'ignore' => 'anonymous']));
	}

	public function testKeepOldest(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000);
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000);
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(8.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest']));

		$this->assertEquals(5.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'range' => 2500]));

		$this->assertEquals(2.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'range' => 2500,	'ignore' => 'anonymous']));
	}

	public function testKeepOldestSingleVote(): void
	{
		global $prefs;
		$prefs['rating_allow_multi_votes'] = '';

		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000);

		$this->assertEquals(6.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest']));

		$this->assertEquals(6.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'range' => 2500]));

		$this->assertEquals(3.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'range' => 2500,	'ignore' => 'anonymous']));
	}

	public function testConsiderPerPeriod(): void
	{
		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000); // kept
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000); // kept
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000);
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000); // kept

		$this->assertEquals(10.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'revote' => 2500]));

		$this->assertEqualsWithDelta(
			10 / 3,
			$lib->collect('test', 111, 'avg', ['keep' => 'oldest', 'revote' => 2500]),
			1 / 1000
		);
	}

	public function testConsiderPerPeriodSingleVote(): void
	{
		global $prefs;
		$prefs['rating_allow_multi_votes'] = '';

		$lib = new RatingLib;
		$lib->record_user_vote('abc', 'test', 111, 5, time() - 3000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 2, time() - 2000); // overridden
		$lib->record_user_vote('abc', 'test', 111, 3, time() - 1000); //kept
		$lib->record_anonymous_vote('deadbeef01234567', 'test', 111, 3, time() - 1000); // kept

		$this->assertEquals(6.0, $lib->collect('test', 111, 'sum', ['keep' => 'oldest', 'revote' => 2500]));

		$this->assertEqualsWithDelta(
			6 / 2,
			$lib->collect('test', 111, 'avg', ['keep' => 'oldest', 'revote' => 2500]),
			1 / 1000
		);
	}
}
