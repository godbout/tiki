<?php
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RSASHA256;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HMACSHA256;

use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
	use AccessTokenTrait, TokenEntityTrait, EntityTrait;

	public function convertToJWT(CryptKey $privateKey = null)
	{
		$token = (new Builder())
			->setAudience($this->getClient()->getIdentifier())
			->setId($this->getIdentifier(), true)
			->setIssuedAt(time())
			->setNotBefore(time())
			->setExpiration($this->getExpiryDateTime()->getTimestamp())
			->setSubject($this->getUserIdentifier())
			->set('scopes', $this->getScopes());

		if (is_null($privateKey)) {
			$token->sign(new HMACSHA256(), $this->getClient()->getClientSecret());
		} else {
			$token->sign(new RSASHA256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()));
		}

		return $token->getToken();
	}
}
