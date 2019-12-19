<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

class AddressBookLib extends TikiLib
{
	function list_address_books($user, $offset = -1, $maxRecords = -1) {
		$query = "select * from `tiki_address_books` where `user` = ? order by `name`";
		$bindvars = [$user];
		return $this->fetchAll($query, $bindvars, $maxRecords, $offset);
	}

	function update_address_book($addressBookId, $data) {
		if ($addressBookId) {
			$update = [];
			$bindvars = [];
			foreach ($data as $key => $val) {
				$update[] = "`$key` = ?";
				$bindvars[] = $val;
			}
			$query = "update `tiki_address_books` set ".implode(', ', $update)." where addressBookId = ?";
			$bindvars[] = $addressBookId;
			$this->query($query, $bindvars);
		} else {
			$update = [];
			$bindvars = [];
			foreach ($data as $key => $val) {
				$update[] = "`$key` = ?";
				$bindvars[] = $val;
			}
			$query = "insert into `tiki_address_books` set ".implode(', ', $update);
			$this->query($query, $bindvars);
			return $this->lastInsertId();
		}
	}

	function delete_address_book($addressBookId) {
		$this->query("delete from `tiki_address_books` where `addressBookId` = ?", [$addressBookId]);
		$this->query("delete from `tiki_address_cards` where `addressBookId` = ?", [$addressBookId]);
	}

	function get_address_book($addressBookId) {
		$result = $this->query("select * from `tiki_address_books` where `addressBookId` = ?", $addressBookId);
		return $result->fetchRow();
	}

	function list_cards($addressBookId, $offset = -1, $maxRecords = -1, $cardUris = []) {
		$query = "select * from `tiki_address_cards` where `addressBookId` = ?";
		$bindvars = [$addressBookId];
		if ($cardUris) {
			$query .= " and `uri` in (".implode(',', array_fill(0, count($cardUris), '?')).")";
			$bindvars = array_merge($bindvars, $cardUris);
		}
		$query .= " order by `addressCardId`";
		return $this->fetchAll($query, $bindvars, $maxRecords, $offset);
	}

	function create_card($data) {
		$update = [];
		$bindvars = [];
		foreach ($data as $field => $value) {
			if (!in_array($field, ['carddata', 'uri', 'addressBookId', 'lastmodified', 'size', 'etag'])) {
				continue;
			}
			$update[] = "`$field` = ?";
			$bindvars[] = $value;
		}
		$query = "insert into `tiki_address_cards` set ".implode(', ', $update);
		$this->query($query, $bindvars);
		return $this->lastInsertId();
	}

	function update_card($addressBookId, $uri, $data) {
		$update = [];
		$bindvars = [];
		foreach ($data as $field => $value) {
			if (!in_array($field, ['carddata', 'uri', 'addressBookId', 'lastmodified', 'size', 'etag'])) {
				continue;
			}
			$update[] = "`$field` = ?";
			$bindvars[] = $value;
		}
		$query = "update `tiki_address_cards` set ".implode(', ', $update)." where `addressBookId` = ? and `uri` = ?";
		$bindvars[] = $addressBookId;
		$bindvars[] = $uri;
		return $this->query($query, $bindvars);
	}

	function delete_card($addressBookId, $uri) {
		$this->query("delete from `tiki_address_cards` where `addressBookId` = ? and `uri` = ?", [$addressBookId, $uri]);
	}
}
