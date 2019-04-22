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

include dirname(__FILE__) . '/server/AuthorizationServer.php';
include dirname(__FILE__) . '/responsetypes/BearerTokenResponse.php';
include dirname(__FILE__) . '/repositories/ClientRepository.php';
include dirname(__FILE__) . '/repositories/AccessTokenRepository.php';
include dirname(__FILE__) . '/repositories/ScopeRepository.php';
include dirname(__FILE__) . '/repositories/RefreshTokenRepository.php';
include dirname(__FILE__) . '/repositories/AuthCodeRepository.php';
include dirname(__FILE__) . '/entities/UserEntity.php';

use \League\OAuth2\Server\Grant\AuthCodeGrant;
use \League\OAuth2\Server\Grant\ClientCredentialsGrant;
use \League\OAuth2\Server\Grant\ImplicitGrant;

class OAuthServerLib extends \TikiLib
{
	private $server;

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
				new BearerTokenResponse()
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
			$server->enableGrantType(
				new ImplicitGrant(new \DateInterval('PT1H'), '?')
			);
		}

		$server->enableGrantType(
			new ClientCredentialsGrant(),
			new \DateInterval('PT1H')
		);

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
