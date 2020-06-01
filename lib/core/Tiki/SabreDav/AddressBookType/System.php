<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\VObject;

use Tiki\SabreDav\PrincipalBackend;
use TikiLib;

class System implements AddressBookTypeInterface
{
	private $user;

	public function __construct($user, $addressBookId = null) {
		$this->user = $user;
	}

	public function isEnabled() {
		$users = TikiLib::lib('user')->list_all_users();
		return !empty($users);
	}

	public function isReadOnly() {
		return true;
	}

	public function getAddressBooks() {
		return [[
			'id' => "system.{$this->user}",
			'uri' => 'system',
			'principaluri' => PrincipalBackend::mapUserToUri($this->user),
			'{DAV:}displayname' => 'System Users',
			'{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'Tiki system users.',
		]];
	}

	public function getCards($uris = null) {
		if (is_array($uris)) {
			$uris = array_map(function($uri){
				return str_replace('.vcf', '', $uri);
			}, $uris);
			$users = TikiLib::lib('user')->get_users();
			$users['data'] = array_filter($users['data'], function($userInfo) use ($uris) {
				return in_array($userInfo['login'], $uris);
			});
		} else {
			$users = TikiLib::lib('user')->get_users();
		}
		return array_map(function($userInfo) use ($uris) {
			$data = $this->constructCardData($userInfo);
			$result = [
					'id' => $userInfo['login'],
					'uri' => $userInfo['login'].'.vcf',
					'lastmodified' => time(),
					'etag' => '"'.md5($data).'"',
					'size' => strlen($data),
			];
			if (is_array($uris)) {
				$result['carddata'] = $data;
			}
			return $result;
		}, $users['data']);
	}

	public function createCard($cardUri, $cardData) {
		throw new DAV\Exception\Forbidden("Address book is read-only.");
	}

	public function updateCard($cardUri, $cardData) {
		throw new DAV\Exception\Forbidden("Address book is read-only.");
	}

	public function deleteCard($cardUri) {
		throw new DAV\Exception\Forbidden("Address book is read-only.");
	}

	private function constructCardData($userInfo) {
		global $url_host;
		$tikilib = TikiLib::lib('tiki');
		$user = $userInfo['login'];
		$realName = $tikilib->get_user_preference($user, 'realName', '');
		$nameParts = explode(' ', $realName, 2);
		$email = TikiLib::lib('user')->get_user_email($user);
		$vcard = new VObject\Component\VCard([
			'UID'   => "tiki-$url_host-user-$user",
			'FN'    => $realName,
			'EMAIL' => $email,
			'N'     => [$nameParts[1], $nameParts[0], '', '', ''],
		]);
		$gender = $tikilib->get_user_preference($user, 'gender', '');
		if ($gender) {
			$vcard->GENDER = $gender;
		}
		$lang = $tikilib->get_language($user);
		if ($lang) {
			$vcard->LANG = $lang;
		}
		$country = $tikilib->get_user_preference($user, 'country', 'Other');
		if ($country && $country != 'Other') {
			$vcard->ADR = ['', '', '', '', '', '', $country];
			$lat = $tikilib->get_user_preference($user, 'lat', '');
			$lon = $tikilib->get_user_preference($user, 'lon', '');
			if ($lat && $lon) {
				$vcard->ADR['GEO'] = "$lat,$lon";
			}
		}
		$homePage = $tikilib->get_user_preference($user, 'homePage', '');
		if ($homePage) {
			$vcard->URL = $homePage;
		}
		$avatar = $tikilib->get_user_avatar_inline($user);
		if ($avatar) {
			$vcard->PHOTO = $avatar;
		}
		return $vcard->serialize();
	}
}
