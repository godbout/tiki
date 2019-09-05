<?php
require_once TIKI_PATH . '/lib/auth/tokens.php';
include dirname(__DIR__) . '/entities/AccessTokenEntity.php';

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
	{
		$lib = new AuthTokens(TikiDb::get(), array());
		$tokenIdentifier = $lib->createToken(
			'OAuth client ' . $accessTokenEntity->getClient()->getIdentifier(),
			[
				'user'   => $accessTokenEntity->getUserIdentifier(),
				'client' => $accessTokenEntity->getClient()->getIdentifier(),
				'scopes' => $accessTokenEntity->getScopes(),
			],  // parameters
			[], // groups
			[
				'userPrefix' => $accessTokenEntity->getUserIdentifier()
			]
		);

		$accessTokenEntity->setIdentifier($tokenIdentifier);
		return $accessTokenEntity;
	}

	public function revokeAccessToken($tokenId)
	{
		$lib = new AuthTokens(TikiDb::get(), array());
		$lib->deleteToken($tokenId);
		return $this;
	}

	public function isAccessTokenRevoked($tokenId)
	{
		return false; // Access token hasn't been revoked
	}

	public function get($token)
	{
		$lib = new AuthTokens(TikiDb::get(), array());
		$client_repo = new ClientRepository(TikiDb::get());

		$token = $lib->getToken($token);
		if (empty($token)) {
			return null;
		}

		$parameters = json_decode($token['parameters'], true);
		$client = $client_repo->get($parameters['client']);

		if (empty($client)) {
			return null;
		}

		$entity = new AccessTokenEntity();
		$entity->setIdentifier($token['token']);
		$entity->setExpiryDateTime(new \DateTime(
			strtotime($token['token']) + (int)$token['timeout']
		));
		$entity->setUserIdentifier($token['userPrefix']);
		$entity->setClient($client);

		return $entity;
	}

	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
	{
		$accessToken = new AccessTokenEntity();
		$accessToken->setClient($clientEntity);

		foreach ($scopes as $scope) {
			$accessToken->addScope($scope);
		}

		$accessToken->setUserIdentifier($userIdentifier);
		return $this->persistNewAccessToken($accessToken);
	}
}
