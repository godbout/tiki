<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Fabiang\Xmpp\Options;
use Fabiang\Xmpp\Client;
use Fabiang\Xmpp\Protocol\Roster;
use Fabiang\Xmpp\Protocol\Presence;
use Fabiang\Xmpp\Protocol\Invitation;
use Fabiang\Xmpp\Protocol\Message;
use Fabiang\Xmpp\Protocol\ProtocolImplementationInterface;
use Fabiang\Xmpp\Util\XML;


class TikiXmppChat
{
	public $client;

	public function __construct($params=array())
	{
		$default = array(
			"scheme" => "tcp",
			"host" => "",
			"port" => 5222,
			"user" => "",
			"pass" => "",
		);
		$params = array_merge($default, $params);
		$address = "{$params['scheme']}://{$params['host']}:{$params['port']}";

		$options = new Options($address);
		$options->setUsername($params['user']);
		$options->setPassword($params['pass']);

		$this->setClient(new Client($options));
	}

	public function connect()
	{
		$this->getClient()->getConnection()->connect();
		return $this;
	}

	public function disconnect()
	{
		$this->getClient()->getConnection()->disconnect();
		return $this;
	}

	public function getClient()
	{
		return $this->client;
	} 	

	public function setClient($client)
	{
		$this->client = $client;
		return $this;
	}

	public function getJid()
	{
		return $this->getClient()->getOptions()->getJid();
	}

	public function getResource()
	{
		$jid = $this->getJid();
		return substr($jid, 1 + strrpos($jid, '/'));
	}

	public function getUsername()
	{
		return $this->getClient()->getOptions()->getUsername();
	}

	/** User actions */
	public function sendInvitation($room, $guest)
	{
		$this->getClient()->send(new Invitation($this->getJid(), $room, $guest));
		return $this;
	}

	public function sendMessage($message, $to, $type=Message::TYPE_CHAT)
	{
		$this->getClient()->send(new Message($message, $to, $type));
		return $this;
	}

	public function sendPresence($priority = 1, $to = null, $nickname = null)
	{
		$this->getClient()->send(new Presence($priority, $to, $nickname));
		return $this;
	}
}