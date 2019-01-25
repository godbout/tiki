<?php
include dirname(dirname(__FILE__)) . '/entities/ClientEntity.php';
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
	const TABLE = 'tiki_oauthserver_clients';
	private $database;
 
	public function __construct($database)
	{
		$this->database = $database;
	}

	public function list()
	{
		$result = array();
		$sql = $this->database->query('SELECT * FROM ' . self::TABLE);

		if($sql && $sql->result) {
			$result = array_map([ClientEntity, 'build'], $sql->result);
		}

		return $result;
	}

	public function get($value, $key='client_id')
	{
		$result = null;
		$sql = 'SELECT * FROM `%s` WHERE %s=?';
		$sql = sprintf($sql, self::TABLE, $key);

		$query = $this->database->query($sql, [$value]);
		if($query && $query->result) {
			$result = new ClientEntity($query->result[0]);
		}

		return $result;
	}
	
	public function update($entity)
	{
		if ( !empty($this->validate($entity)) ) {
			throw new Exception(tra('Cannot save invalid client'));
		}

		$sql = 'UPDATE `%s` SET name=?, client_id=?, client_secret=?, redirect_uri=? WHERE identifier=?';
		$sql = sprintf($sql, self::TABLE);

		$query = $this->database->query($sql, [
			$entity->getName(),
			$entity->getClientId(),
			$entity->getClientSecret(),
			$entity->getRedirectUri(),
			$entity->getIdentifier()
		]);

		return $query;
	}

	public function create($entity)
	{
		if ( !empty($this->validate($entity)) ) {
			throw new Exception(tra('Cannot save invalid client'));
		}

		$sql = 'INSERT INTO `%s`(name, client_id, client_secret, redirect_uri) VALUES(?, ?, ?, ?)';
		$sql = sprintf($sql, self::TABLE);

		$query = $this->database->query($sql, [
			$entity->getName(),
			$entity->getClientId(),
			$entity->getClientSecret(),
			$entity->getRedirectUri()
		]);

		$identifier = (int) $this->database->lastInsertId();
		$entity->setIdentifier($identifier);

		return $query;
	}

	public function save($entity)
	{
		if($entity->getIdentifier()) {
			return $entity->update();
		}
		return $entity->create();
	}

	public function delete($entity)
	{
		$params = [];
		$sql = sprintf('DELETE FROM `%s` WHERE ', self::TABLE);

		if($entity->getIdentifier()) {
			$sql .= 'identifier=?';
			$params[] = $entity->getIdentifier();
		}
		elseif ($entity->getClientId()) {
			$sql .= 'client_id=?';
			$params[] = $entity->getClientId();
		}
		$sql .= ';';

		if (empty($params)) {
			return false;
		}
	
		return $this->database->query($sql, $params);
	}

	public function exists($entity)
	{
		$params = [];
		$sql = sprintf('SELECT COUNT(1) AS count FROM `%s` WHERE ', self::TABLE);

		if($entity->getIdentifier()) {
			$sql .= 'identifier=?';
			$params[] = $entity->getIdentifier();
		}
		elseif ($entity->getClientId()) {
			$sql .= 'client_id=?';
			$params[] = $entity->getClientId();
		}

		$sql .= ';';
		if (empty($params)) {
			return false;
		}

		$result = $this->database->getOne($sql, $params);
		$result = intval($result, 10);
		return $result > 0;
	}

	public function validate($entity)
	{
		$errors = [];

		if (empty($entity->getName())) {
			$errors['name'] = tra('Name cannot be empty');
		}

		if (empty($entity->getClientId())) {
			$errors['client_id'] = tra('Client Id cannot be empty');
		}

		if (empty($entity->getClientSecret())) {
			$errors['client_secret'] = tra('Client Secret cannot be empty');
		}

		if (empty($entity->getRedirectUri())) {
			$errors['redirect_uri'] = tra('Redirect URI cannot be empty');

		} else if (!filter_var($entity->getRedirectUri(),FILTER_VALIDATE_URL)) {
			$errors['redirect_uri'] = tra('Invalid URL for redirect URI');
		}

		return $errors;
	}

	public function getClientEntity($clientId, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
	{
		$client = $this->get($clientId);
		if (is_null($client)) {
			return false;
		}
		if ($mustValidateSecret === true && $client->getClientSecret() !== $clientSecret) {
			return false;
		}
		return $client;
	}
}
