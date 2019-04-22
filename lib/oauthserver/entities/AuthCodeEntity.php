<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class AuthCodeEntity implements AuthCodeEntityInterface
{
	protected $identifier;
	protected $scopes = [];
	protected $expiryDateTime;
	protected $userIdentifier;
	protected $client;
	protected $redirectUri;

	public function getIdentifier()
	{
		return $this->identifier;
	}

	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}

	public function addScope(ScopeEntityInterface $scope)
	{
		$this->scopes[$scope->getIdentifier()] = $scope;
	}

	public function getScopes()
	{
		return array_values($this->scopes);
	}

	public function getExpiryDateTime()
	{
		return $this->expiryDateTime;
	}

	public function setExpiryDateTime(DateTime $dateTime)
	{
		$this->expiryDateTime = $dateTime;
	}

	public function setUserIdentifier($identifier)
	{
		$this->userIdentifier = $identifier;
	}

	public function getUserIdentifier()
	{
		return $this->userIdentifier;
	}

	public function getClient()
	{
		return $this->client;
	}

	public function setClient(ClientEntityInterface $client)
	{
		$this->client = $client;
	}

	public function getRedirectUri()
	{
		return $this->redirectUri;
	}

	public function setRedirectUri($uri)
	{
		$this->redirectUri = $uri;
	}
}