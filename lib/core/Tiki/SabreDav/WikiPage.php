<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use TikiLib;
use Perms;

class WikiPage extends DAV\File {

	private $page;

	function __construct($path) {
		$name = preg_replace('#^/#', '', $path);
		$this->page = TikiLib::lib('tiki')->get_page_info($name);
		if (!$this->page) {
			throw new DAV\Exception\NotFound(tr('The wiki page with name: ' . $name . ' could not be found'));
		}
	}

	function getName() {
		return $this->page['pageName'];
	}

	function get() {
		return $this->page['data'];
	}

	function getSize() {
		return $this->page['page_size'];
	}

	function getETag() {
		$md5 = md5($this->page['pageName'] . $this->page['lastModif']);
		return '"' . $md5 . '-' . crc32($md5) . '"';
	}

	function getContentType() {
		return 'application/octet-stream';
	}

	function getLastModified() {
		return $this->page['lastModif'];
	}

	function put($data) {
		global $user;

		$perms = Perms::get(['type' => 'wiki page', 'id' => $this->page['page_id']]);
		if (! $perms->edit) {
			throw new DAV\Exception\Forbidden(tr('Permission denied.'));
		}

		$info = Utilities::parseContents($this->page['pageName'], $data);

		$tikilib = TikiLib::lib('tiki');
		$tikilib->update_page($this->page['pageName'], $info['content'], "Updated from WebDAV", $user, $tikilib->get_ip_address());
	}

	function setName($name) {
		$perms = Perms::get(['type' => 'wiki page', 'id' => $this->page['page_id']]);
		if (! $perms->rename) {
			throw new DAV\Exception\Forbidden(tr('Permission denied.'));
		}

		TikiLib::lib('wiki')->wiki_rename_page($this->page['pageName'], $name);
	}

	function delete() {
		$perms = Perms::get(['type' => 'wiki page', 'id' => $this->page['page_id']]);
		if (! $perms->remove) {
			throw new DAV\Exception\Forbidden(tr('Permission denied.'));
		}

		TikiLib::lib('tiki')->remove_all_versions($this->page['pageName'], "Removed from WebDav");
	}
}
