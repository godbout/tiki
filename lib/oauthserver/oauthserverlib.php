<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

include __DIR__ . '/entities/UserEntity.php';
include __DIR__ . '/repositories/AccessTokenRepository.php';
include __DIR__ . '/repositories/AuthCodeRepository.php';
include __DIR__ . '/repositories/ClientRepository.php';
include __DIR__ . '/repositories/RefreshTokenRepository.php';
include __DIR__ . '/repositories/ScopeRepository.php';
include __DIR__ . '/responsetypes/BearerTokenResponse.php';
include __DIR__ . '/server/AuthorizationServer.php';

use \League\OAuth2\Server\Grant\AuthCodeGrant;
use \League\OAuth2\Server\Grant\ImplicitGrant;

class OAuthServerLib extends \TikiLib
{
	private $server;

	public function getEncryptionKey()
	{
		global $prefs;
		$tikilib = TikiLib::lib('tiki');

		if (empty($prefs['oauthserver_encryption_key'])) {
			$encryptionKey = $tikilib->generate_unique_sequence(32, true);
			$tikilib->set_preference('oauthserver_encryption_key', $encryptionKey);
		}

		return $prefs['oauthserver_encryption_key'];
	}

	public function getClientRepository()
	{
		$database = TikiLib::lib('db');
		return new ClientRepository($database);
	}

	public function getAccessTokenRepository()
	{
		$database = TikiLib::lib('db');
		return new AccessTokenRepository($database);
	}

	public function getServer()
	{
		if (empty($this->server)) {
			$this->server = new AuthorizationServer(
				$this->getClientRepository(),
				new AccessTokenRepository(),
				new ScopeRepository(),
				new BearerTokenResponse(),
				$this->getEncryptionKey()
			);
		}
		return $this->server;
	}

	public function getUserEntity()
	{
		global $user;
		$entity = new UserEntity();
		$entity->setIdentifier($user);
		return $entity;
	}

	public function determineServerGrant()
	{
		global $user;
		$server = $this->getServer();

		if (! empty($user)) {
			$grant = new ImplicitGrant(new \DateInterval('PT1H'), '?');
			$server->enableGrantType($grant);
		}

		$grant = new AuthCodeGrant(
			new AuthCodeRepository(),
			new RefreshTokenRepository(),
			new \DateInterval('PT10M')
		);
		$grant->setRefreshTokenTTL(new \DateInterval('P1M'));
		$server->enableGrantType($grant);

		return $this;
	}

	public function getClient($client_id)
	{
		return $this->getClientRepository()->get($client_id);
	}

	public function createClient($data)
	{
		$repo = $this->getClientRepository();

		if (empty($data['client_id'])) {
			$data['client_id'] = $repo::generateSecret(32);
		}

		if (empty($data['client_secret'])) {
			$data['client_secret'] = $repo::generateSecret(64);
		}

		$entity = ClientRepository::build($data);
		return $repo->create($entity);
	}
}
