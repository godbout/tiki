<?php
require_once 'lib/auth/tokens.php';
include dirname(dirname(__FILE__)) . '/entities/AccessTokenEntity.php';

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
	/**
	 * {@inheritdoc}
	 */
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
	/**
	 * {@inheritdoc}
	 */
	public function revokeAccessToken($tokenId)
	{
		$lib = new AuthTokens();
		$lib->deleteToken($tokenId);
		return $this;
	}
	/**
	 * {@inheritdoc}
	 */
	public function isAccessTokenRevoked($tokenId)
	{
		return false; // Access token hasn't been revoked
	}

	/**
	 * {@inheritdoc}
	 */
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