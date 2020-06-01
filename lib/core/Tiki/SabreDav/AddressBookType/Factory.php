<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

use Sabre\CardDAV;

use TikiLib;

class Factory
{
	public static function all($user) {
		return [
			new Webmail($user),
			new System($user),
			new Custom($user),
		];
	}

	public static function fromId($id, $user) {
		if (preg_match('/^(webmail|system).(.*)$/', $id, $m)) {
			$type = $m[1];
			$principal = $m[2];
			if ($principal && $principal != $user) {
				throw new DAV\Exception\Forbidden("You don't have permission to access or modify this address book.");
			}
		} else {
			$type = 'custom';
			$addressbook = TikiLib::lib('addressbook')->get_address_book($id);
			if (! $addressbook || $addressbook['user'] != $user) {
				throw new DAV\Exception\Forbidden("You don't have permission to access or modify this address book.");
			}
		}
		switch($type) {
			case 'webmail':
				return new Webmail($user);
			case 'system':
				return new System($user);
			case 'custom':
				return new Custom($user, $id);
			default:
				throw new Dav\Exception\NotImplemented("Requested address book not recognized.");
		}
	}
}
