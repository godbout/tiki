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

    public function setName($name)
    {
        $this->name = $name;
    }
    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }
}