<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
include dirname(__DIR__) . '/entities/RefreshTokenEntity.php';

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
	public function getNewRefreshToken()
	{
		return new RefreshTokenEntity();
	}

	public function isRefreshTokenRevoked($tokenId)
	{
		return false;
	}

	public function persistNewRefreshToken(RefreshTokenEntityInterface $code)
	{
	}

	public function revokeRefreshToken($tokenId)
	{
	}
}
