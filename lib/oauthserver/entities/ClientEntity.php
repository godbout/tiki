<?php 

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * This class represents a Client Application that uses
 * Tiki as an Authorization Server.
 */
class ClientEntity implements ClientEntityInterface
{
	use EntityTrait, ClientTrait;
	const TABLE = 'tiki_oauthserver_clients';

	public function __construct($data=null)
	{
		if(is_array($data))
		{
			!empty($data['identifier']) && $this->setIdentifier($data['identifier']);
			!empty($data['name']) && $this->setName($data['name']);
			!empty($data['client_id']) && $this->setClientId($data['client_id']);
			!empty($data['client_secret']) && $this->setClientSecret($data['client_secret']);
			!empty($data['redirect_uri']) && $this->setRedirectUri($data['redirect_uri']);
		}
		else {
			$this->setIdentifier(0);
			$this->setName('');
			$this->setClientId('');
			$this->setClientSecret('');
			$this->setRedirectUri('');
		}
	}

	public static function build($data)
	{
		return new self($data);
	}

	public function setIdentifier($identifier)
	{
		$this->identifier = (int) $identifier;
		return $this;
	}

	public function getIdentifier(){
		return $this->identifier;
	}

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName(){
		return $this->name;
	}

	public function setClientId($client_id)
	{
		$this->client_id = $client_id;
		return $this;
	}

	public function getClientId(){
		return $this->client_id;
	}

	public function setClientSecret($client_secret)
	{
		$this->client_secret = $client_secret;
		return $this;
	}

	public function getClientSecret(){
		return $this->client_secret;
	}

	public function setRedirectUri($redirect_uri)
	{
		$this->redirect_uri = $redirect_uri;
		return $this;
	}

	public function getRedirectUri()
	{
		return $this->redirect_uri;
	}

	public function toArray()
	{
		return array(
			'identifier'    => $this->getIdentifier(),
			'name'          => $this->getName(),
			'client_id'     => $this->getClientId(),
			'client_secret' => $this->getClientSecret(),
			'redirect_uri'  => $this->getRedirectUri(),
		);
	}

	public function toJson()
	{
		return json_encode(
			$this->toArray(),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}
}