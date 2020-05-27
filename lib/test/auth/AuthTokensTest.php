<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group integration
 */

require_once __DIR__ . '/../../auth/tokens.php';

class AuthTokensTest extends TikiDatabaseTestCase
{
	private $db;

	private $dt;

	private $table;

	private $obj;

	public function getDataSet()
	{
		return $this->createMySQLXMLDataSet(__DIR__ . '/fixtures/auth_tokens_dataset.xml');
	}

	public function setUp() : void
	{
		$this->db = TikiDb::get();

		$this->dt = new DateTime;
		$this->dt->setTimezone(new DateTimeZone('UTC'));
		// 2012-02-03 15:25:07
		$this->dt->setTimestamp('1328282707');

		$this->table = $this->db->table('tiki_auth_tokens');

		$this->obj = new AuthTokens($this->db, [], $this->dt);

		parent::setUp();
	}

	public function testNoTokensIsDenied(): void
	{
		$params = [];
		$groups = $this->obj->getGroups('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'tiki-index.php', $params);
		$this->assertNull($groups);
	}

	public function testCreateToken(): void
	{
		$expectedTable = $this->createMySQLXmlDataSet(__DIR__ . '/fixtures/auth_tokens_dataset_create.xml')
			->getTable('tiki_auth_tokens');

		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered'], ['timeout' => 5]);
		$this->db->query("UPDATE tiki_auth_tokens SET creation = '2012-02-03 15:25:07', token = '0ae3b4b86286ab68f5a66fb8c49da163' WHERE token = '$token'");

		$queryTable = $this->getConnection()->createQueryTable('tiki_auth_tokens', 'SELECT * FROM tiki_auth_tokens');

		self::assertTablesEqual($expectedTable, $queryTable);
	}

	public function testTokenMatchesCompleteHash(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);

		$row = $this->db->query('SELECT tokenId, creation, timeout, entry, parameters, groups FROM tiki_auth_tokens ORDER BY creation desc')->fetchRow();

		$this->assertEquals(md5(implode('', $row)), $token);
	}

	public function testRetrieveGroupsForToken(): void
	{
		$this->dt->setTimestamp(time());
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->assertEquals(['Registered'], $this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testAccessExpiredToken(): void
	{
		$this->assertNull($this->obj->getGroups("946fc2fa0a5e1cecd54440ce733b8fb4", 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testAlteredDataCancels(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->db->query('UPDATE tiki_auth_tokens SET groups = \'["Admins"]\'');
		$this->assertNull($this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testExtraDataCancels(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->assertNull($this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage', 'hello' => 'world']));
	}

	public function testMissingDataCancels(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage', 'foobar' => 'baz'], ['Registered']);
		$this->assertNull($this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testDifferingEntryCancels(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->assertNull($this->obj->getGroups($token, 'tiki-print.php', ['page' => 'HomePage']));
	}

	public function testDifferingValueCancels(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->assertNull($this->obj->getGroups($token, 'tiki-index.php', ['page' => 'Home']));
	}

	public function testNoParamerers(): void
	{
		$this->dt->setTimestamp(time());
		$token = $this->obj->createToken('tiki-index.php', [], ['Registered']);
		$this->assertEquals(['Registered'], $this->obj->getGroups($token, 'tiki-index.php', []));
	}

	public function testMaximumTimeout(): void
	{
		$lib = new AuthTokens(
			$this->db,
			[
				'maxTimeout' => 10,
			]
		);
		$lib->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered'], ['timeout' => 3600]);

		$this->assertEquals(10, $this->db->getOne('SELECT timeout FROM tiki_auth_tokens ORDER BY creation desc'));
	}

	public function testSameTokenTwice(): void
	{
		$token = $this->obj->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered']);
		$this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']);

		$this->assertNull($this->obj->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testAllowMultipleHits(): void
	{
		$lib = new AuthTokens($this->db, ['maxHits' => 100]);
		$token = $lib->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered'], ['hits' => 3]);
		$lib->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']);
		$lib->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']);

		$this->assertEquals(['Registered'], $lib->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
		$this->assertNull($lib->getGroups($token, 'tiki-index.php', ['page' => 'HomePage']));
	}

	public function testLimitOnAccessCount(): void
	{
		$lib = new AuthTokens(
			$this->db,
			[
				'maxHits' => 10,
			]
		);
		$lib->createToken('tiki-index.php', ['page' => 'HomePage'], ['Registered'], ['hits' => 3600]);

		$this->assertEquals(10, $this->db->getOne('SELECT hits FROM tiki_auth_tokens WHERE tokenId = 1'));
	}

	public function testIncludeToken(): void
	{
		$url = 'http://example.com/tiki/tiki-index.php?page=SomePage';
		$new = $this->obj->includeToken($url);

		$this->assertRegExp('/TOKEN=[a-z0-9]{32}/i', $new);
		$this->assertStringContainsString('http://example.com/tiki/tiki-index.php', $new);
		$this->assertStringContainsString('page=SomePage', $new);
	}

	public function testIncludeTokenNoPath(): void
	{
		$url = 'http://example.com/tiki-index.php';
		$new = $this->obj->includeToken($url);

		$this->assertRegExp('/TOKEN=[a-z0-9]{32}/i', $new);
		$this->assertStringContainsString('http://example.com/tiki-index.php', $new);
	}

	public function testWithFragment(): void
	{
		$url = 'http://example.com/tiki-index.php#Test';
		$new = $this->obj->includeToken($url);

		$this->assertRegExp('/TOKEN=[a-z0-9]{32}#Test/i', $new);
	}

	public function testGetTokens_shouldReturnEmptyArrayIfNoToken(): void
	{
		$this->db->query('TRUNCATE tiki_auth_tokens');
		$this->assertEquals([], $this->obj->getTokens());
	}

	public function testGetTokens_shouldReturnAllTokens(): void
	{
		$token1 = '91bba2f998b48fce0146016809886127';
		$token2 = '823bde97a717c55b2cfbf9fbd6c81816';
		$token3 = 'e2990f7983b7b6c46b3987536aa38d32';

		$tokens = $this->obj->getTokens();

		$this->assertCount(3, $tokens);
		$this->assertEquals($token1, $tokens[0]['token']);
		$this->assertEquals($token2, $tokens[1]['token']);
		$this->assertEquals($token3, $tokens[2]['token']);
	}

	public function testDeleteToken(): void
	{
		$this->obj->createToken('tiki-user_send_reports.php', [], ['Registered']);
		$tokenId = $this->db->getOne('SELECT tokenId FROM tiki_auth_tokens ORDER BY creation desc');

		$this->obj->deleteToken($tokenId);

		$this->assertEmpty($this->table->fetchRow(['entry'], ['tokenId' => $tokenId]));
	}

	public function testGetGroups_shouldDeleteExpiredTokens(): void
	{
		$expectedTable = $this->createMySQLXmlDataSet(__DIR__ . '/fixtures/auth_tokens_dataset_delete_timeout.xml')
			->getTable('tiki_auth_tokens');

		$this->obj->getGroups('91bba2f998b48fce0146016809886127', 'tiki-index.php', []);

		$queryTable = $this->getConnection()->createQueryTable('tiki_auth_tokens', 'SELECT * FROM tiki_auth_tokens');

		self::assertTablesEqual($expectedTable, $queryTable);
	}

	public function testGetGroups_shouldDeleteTokensWithoutHitsLeft(): void
	{
		// 2012-02-01 13:25:07
		$this->dt->setTimestamp('1328109907');

		$this->db->query('UPDATE tiki_auth_tokens set maxHits = -1, hits = -1 WHERE tokenId = 1');
		$this->db->query('UPDATE tiki_auth_tokens set maxHits = 10, hits = 0 WHERE tokenId = 2');

		$expectedTable = $this->createMySQLXmlDataSet(__DIR__ . '/fixtures/auth_tokens_dataset_delete_hits.xml')
			->getTable('tiki_auth_tokens');

		$this->obj->getGroups('91bba2f998b48fce0146016809886127', 'tiki-index.php', []);

		$queryTable = $this->getConnection()->createQueryTable('tiki_auth_tokens', 'SELECT * FROM tiki_auth_tokens');

		self::assertTablesEqual($expectedTable, $queryTable);
	}

	public function testGetGroups_shouldDecrementHits(): void
	{
		$this->obj->getGroups('e2990f7983b7b6c46b3987536aa38d32', 'tiki-index.php', []);

		$this->assertEquals('9', $this->db->getOne('SELECT hits FROM tiki_auth_tokens WHERE tokenId = 3'));
	}

	public function testGetGroups_shouldDecrementIfUnlimitedHits(): void
	{
		$this->db->query('UPDATE tiki_auth_tokens set maxHits = -1, hits = -1 WHERE tokenId = 3');

		$this->obj->getGroups('e2990f7983b7b6c46b3987536aa38d32', 'tiki-index.php', []);

		$this->assertEquals('-1', $this->db->getOne('SELECT hits FROM tiki_auth_tokens WHERE tokenId = 3'));
	}
}
