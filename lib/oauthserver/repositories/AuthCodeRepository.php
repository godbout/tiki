<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once TIKI_PATH . '/lib/auth/tokens.php';
include dirname(__DIR__) . '/entities/AuthCodeEntity.php';

use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
	public function getNewAuthCode()
	{
		return new AuthCodeEntity();
	}

	public function isAuthCodeRevoked($codeId)
	{
		return false;
	}

	public function persistNewAuthCode(AuthCodeEntityInterface $code)
	{
	}

	public function revokeAuthCode($codeId)
	{
	}
}
