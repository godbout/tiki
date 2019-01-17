<?php
include dirname(dirname(__FILE__)) . '/entities/ClientEntity.php';
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
	public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
	{
		$prefslib = TikiLib::lib('prefs');

		// Data schema
		// -----------
		$clients = [
		    'myawesomeapp' => [
		        'secret'          => 'donaldduck',
		        'name'            => 'My Awesome App',
		        'redirect_uri'    => 'http://foo/bar',
		        'is_confidential' => true,
		    ],
		];

		// Check if client is registered
		if (empty($clients) || array_key_exists($clientIdentifier, $clients) === false) {
			return false;
		}

		if (
			$mustValidateSecret === true
			&& $clients[$clientIdentifier]['is_confidential'] === true
			&& $clients[$clientIdentifier]['secret'] !== $clientSecret
		) {
			return;
		}

		$client = new ClientEntity();
		$client->setIdentifier($clientIdentifier);
		$client->setName($clients[$clientIdentifier]['name']);
		$client->setRedirectUri($clients[$clientIdentifier]['redirect_uri']);
		return $client;
	}
}
