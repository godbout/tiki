<?php
include dirname(__DIR__) . '/entities/ClientEntity.php';
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
	const TABLE = 'tiki_oauthserver_clients';
	private $database;

	public function __construct($database)
	{
		$this->database = $database;
	}

	public static function build($data)
	{
		return new ClientEntity($data);
	}

	public function list()
	{
		$result = array();
		$sql = $this->database->query('SELECT * FROM ' . self::TABLE);

		if ($sql && $sql->result) {
			$result = array_map([ClientEntity, 'build'], $sql->result);
		}

		return $result;
	}

	public function get($value, $key = 'client_id')
	{
		$result = null;
		$sql = 'SELECT * FROM `%s` WHERE %s=?';
		$sql = sprintf($sql, self::TABLE, $key);

		$query = $this->database->query($sql, [$value]);
		if ($query && $query->result) {
			$result = new ClientEntity($query->result[0]);
		}

		return $result;
	}

	public function update($entity)
	{
		if (! empty($this->validate($entity))) {
			throw new Exception(tra('Cannot save invalid client'));
		}

		$sql = 'UPDATE `%s` SET name=?, client_id=?, client_secret=?, redirect_uri=? WHERE id=?';
		$sql = sprintf($sql, self::TABLE);

		$query = $this->database->query($sql, [
			$entity->getName(),
			$entity->getClientId(),
			$entity->getClientSecret(),
			$entity->getRedirectUri(),
			$entity->getId()
		]);

		return $query;
	}

	public function create($entity)
	{
		if (! empty($this->validate($entity))) {
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

		$id = (int) $this->database->lastInsertId();
		$entity->setId($id);

		return $entity;
	}

	public function save($entity)
	{
		if ($entity->getId()) {
			return $entity->update();
		}
		return $entity->create();
	}

	public function delete($entity)
	{
		$params = [];
		$sql = sprintf('DELETE FROM `%s` WHERE ', self::TABLE);

		if ($entity->getId()) {
			$sql .= 'id=?';
			$params[] = $entity->getId();
		} elseif ($entity->getClientId()) {
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

		if ($entity->getId()) {
			$sql .= 'id=?';
			$params[] = $entity->getId();
		} elseif ($entity->getClientId()) {
			$sql .= 'client_id=?';
			$params[] = $entity->getClientId();
		}

		$sql .= ';';
		if (empty($params)) {
			return false;
		}

		$result = $this->database->getOne($sql, $params);
		$result = (int)$result;
		return $result > 0;
	}

	public function validate($entity)
	{
		$errors = [];

		if (empty($entity->getName())) {
			$errors['name'] = tra('Name cannot be empty');
		}

		if (empty($entity->getRedirectUri())) {
			$errors['redirect_uri'] = tra('Redirect URI cannot be empty');
		} else if (! filter_var($entity->getRedirectUri(), FILTER_VALIDATE_URL)) {
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

	public static function generateSecret($length = 32)
	{
		$random = \phpseclib\Crypt\Random::string(ceil($length / 2));
		$random = bin2hex($random);
		$random = substr($random, 0, $length);
		return $random;
	}
}
