<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

use Sabre\CardDAV;
use Sabre\VObject;

use Tiki\SabreDav\PrincipalBackend;
use TikiLib;

class Webmail implements AddressBookTypeInterface
{
	private $user;

	public function __construct($user, $addressBookId = null) {
		$this->user = $user;
	}

	public function isEnabled() {
		global $prefs;
		return $prefs['feature_webmail'] === 'y';
	}

	public function isReadOnly() {
		return false;
	}

	public function getAddressBooks() {
		return [[
			'id' => "webmail.{$this->user}",
			'uri' => 'webmail',
			'principaluri' => PrincipalBackend::mapUserToUri($this->user),
			'{DAV:}displayname' => 'Webmail Contacts',
			'{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'Webmail contacts managed by Tiki.',
		]];
	}

	public function getCards($uris = null) {
		if (is_array($uris)) {
			$uris = array_map(function($uri){
				return str_replace('.vcf', '', $uri);
			}, $uris);
			$contacts = TikiLib::lib('contact')->list_contacts($this->user, -1, -1, 'contactId_asc', null, false, '', 'email', $uris);
		} else {
			$contacts = TikiLib::lib('contact')->list_contacts($this->user);
		}
		return array_map(function($row) use ($uris) {
			$data = $this->constructCardData($row);
			$result = [
				'id' => $row['contactId'],
				'uri' => $row['contactId'].'.vcf',
				'lastmodified' => time(),
				'etag' => '"'.md5($data).'"',
				'size' => strlen($data),
			];
			if (is_array($uris)) {
				$result['carddata'] = $data;
			}
			return $result;
		}, $contacts);
	}

	public function createCard($cardUri, $cardData) {
		$this->updateCard($cardUri, $cardData);
	}

	public function updateCard($cardUri, $cardData) {
		$vcard = VObject\Reader::read($cardData, VObject\Reader::OPTION_FORGIVING);
		$name = explode(' ', (string)$vcard->FN, 2);
		$row = TikiLib::lib('contact')->get_contact_by_uri($cardUri, $this->user);
		if ($row) {
			$contactId = $row['contactId'];
		} else {
			$contactId = 0;
		}
		TikiLib::lib('contact')->replace_contact(
			$contactId,
			$name[0],
			@$name[1],
			(string)$vcard->EMAIL,
			(string)$vcard->NICKNAME,
			$this->user,
			null,
			[],
			true
		);
		return '"'.md5($cardData).'"';
	}

	public function deleteCard($cardUri) {
		$row = TikiLib::lib('contact')->get_contact_by_uri($cardUri, $this->user);
		if ($row) {
			return TikiLib::lib('contact')->remove_contact($row['contactId'], $this->user);
		}
		return false;
	}

	private function constructCardData($contact) {
		global $url_host;
		$vcard = new VObject\Component\VCard([
			'UID'      => "tiki-{$url_host}-webmail-".$contact['contactId'],
			'FN'       => $contact['firstName'].' '.$contact['lastName'],
			'EMAIL'    => $contact['email'],
			'N'        => [$contact['lastName'], $contact['firstName'], '', '', ''],
			'NICKNAME' => $contact['nickname'],
		]);
		return $vcard->serialize();
	}
}
