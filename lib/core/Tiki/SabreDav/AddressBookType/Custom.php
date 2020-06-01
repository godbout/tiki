<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

use Sabre\CardDAV;

use Tiki\SabreDav\PrincipalBackend;
use TikiLib;

class Custom implements AddressBookTypeInterface
{
	private $user;
	private $addressBookId;

	public function __construct($user, $addressBookId = null) {
		$this->user = $user;
		$this->addressBookId = $addressBookId;
	}

	public function isEnabled() {
		return true;
	}

	public function isReadOnly() {
		return false;
	}

	public function getAddressBooks() {
		$result = [];
		$address_books = TikiLib::lib('addressbook')->list_address_books($this->user);
		foreach ($address_books as $row) {
			$result[] = [
				'id' => $row['addressBookId'],
				'uri' => $row['uri'],
				'principaluri' => PrincipalBackend::mapUserToUri($this->user),
				'{DAV:}displayname' => $row['name'],
				'{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => $row['description'],
			];
		}
		return $result;
	}

	public function getCards($uris = null) {
		if (is_array($uris)) {
			$cards = TikiLib::lib('addressbook')->list_cards($this->addressBookId, -1, -1, $uris);
		} else {
			$cards = TikiLib::lib('addressbook')->list_cards($this->addressBookId);
		}
		return array_map(function($card) use ($uris) {
			$result = [
				'id' => $card['addressCardId'],
				'uri' => $card['uri'],
				'lastmodified' => $card['lastmodified'],
				'etag' => '"'.$card['etag'].'"',
				'size' => $card['size'],
			];
			if (is_array($uris)) {
				$result['carddata'] = $card['carddata'];
			}
			return $result;
		}, $cards);
	}

	public function createCard($cardUri, $cardData) {
		$data = [
			'carddata' => $cardData,
			'uri' => $cardUri,
			'addressBookId' => $this->addressBookId,
			'lastmodified' => time(),
			'size' => strlen($cardData),
			'etag' => md5($cardData)
		];
		TikiLib::lib('addressbook')->create_card($data);
		return '"'.$data['etag'].'"';
	}

	public function updateCard($cardUri, $cardData) {
		$data = [
			'carddata' => $cardData,
			'lastmodified' => time(),
			'size' => strlen($cardData),
			'etag' => md5($cardData)
		];
		TikiLib::lib('addressbook')->update_card($this->addressBookId, $cardUri, $data);
		return '"'.$data['etag'].'"';
	}

	public function deleteCard($cardUri) {
		return TikiLib::lib('addressbook')->delete_card($this->addressBookId, $cardUri);
	}
}
