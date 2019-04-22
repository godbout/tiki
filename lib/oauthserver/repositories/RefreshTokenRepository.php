<?php

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
	public function getNewRefreshToken()
	{
	}

	public function isRefreshTokenRevoked($tokenId)
	{
	}

	public function persistNewRefreshToken(RefreshTokenEntityInterface $code)
	{
	}

	public function revokeRefreshToken($tokenId)
	{
	}
}
