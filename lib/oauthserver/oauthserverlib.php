<?php

include dirname(__FILE__) . '/repositories/ClientRepository.php';
include dirname(__FILE__) . '/repositories/AccessTokenRepository.php';
include dirname(__FILE__) . '/repositories/ScopeRepository.php';
include dirname(__FILE__) . '/repositories/RefreshTokenRepository.php';
include dirname(__FILE__) . '/repositories/AuthCodeRepository.php';
include dirname(__FILE__) . '/entities/UserEntity.php';

use \League\OAuth2\Server\AuthorizationServer;
use \League\OAuth2\Server\Grant\AuthCodeGrant;
use \League\OAuth2\Server\Grant\ClientCredentialsGrant;
use \League\OAuth2\Server\Grant\ImplicitGrant;

class OAuthServerLib extends TikiLib
{
	private $server;

	public function getClientRepository()
	{
		$database = TikiLib::lib('db');
		return new ClientRepository($database);
	}

	public function getEncryptionKey()
	{
		return file_get_contents(TIKI_PATH . '/db/cert/oauthserver-encryption.key');
	}

	public function getPrivateKey()
	{
		return TIKI_PATH . '/db/cert/oauthserver-private.key';
	}

	public function getServer()
	{
		if(empty($this->server)) {
			$this->server = new AuthorizationServer(
				$this->getClientRepository(),
				new AccessTokenRepository(),
				new ScopeRepository(),
				$this->getPrivateKey(),
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

		if (!empty($user)) {
			$server->enableGrantType(
				new ImplicitGrant(new \DateInterval('PT1H'))
			);
		}

		$server->enableGrantType(
			new ClientCredentialsGrant(),
			new \DateInterval('PT1H')
		);

		return $this;
	}

}