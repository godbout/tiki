<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use TikiLib;

class Directory extends DAV\Collection {

	private $definition;

	function __construct($path_or_id = '/') {
		if ((int)$path_or_id == 0) {
			$result = TikiLib::lib('filegal')->get_objectid_from_virtual_path($path_or_id);
			if (! $result || $result['type'] != 'filegal') {
				throw new DAV\Exception\NotFound(tr('The directory with path: ' . $path_or_id . ' could not be found'));
			}
			$path_or_id = $result['id'];
		}
		$this->definition = TikiLib::lib('filegal')->getGalleryDefinition($path_or_id);
	}

	function getChildren() {
		global $prefs;

		$children = array();
		$info = $this->definition->getInfo();
		if ($info['galleryId'] == $prefs['fgal_root_id']) {
			$children[] = new WikiDirectory();
		}

		$results = $this->galleryChildren();
		foreach($results['data'] as $row) {
			$children[] = $this->getChildFromDB($row);
		}

		return $children;
	}

	function getChildFromDB($row) {
		if ($row['isgal']) {
			return new Directory($row['id']);
		} else {
			return new File($row['id']);
		}
	}

	function getChild($name) {
		$wikiDir = new WikiDirectory();
		if ($name === $wikiDir->getName()) {
			return $wikiDir;
		}
		$results = $this->galleryChildren();
		foreach ($results['data'] as $row) {
			if ($row['filename'] === $name) {
				return $this->getChildFromDB($row);
			}
		}
		// We have to throw a NotFound exception if the file didn't exist
		throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
	}

	function childExists($name) {
		$results = $this->galleryChildren();
		foreach ($results['data'] as $row) {
			if ($row['filename'] === $name) {
				return true;
			}
		}
		return false;
	}

	function getLastModified() {
		$info = $this->definition->getInfo();
		return $info['lastModif'];
	}

	function getName() {
		$info = $this->definition->getInfo();
		return $info['name'];
	}

	function setName($name) {
		$info = $this->definition->getInfo();
		$info['name'] = $name;
		TikiLib::lib('filegal')->replace_file_gallery($info);
	}

	function createFile($name, $data = null) {
		global $user, $prefs;

		Utilities::checkUploadPermission($this->definition);

		$info = Utilities::parseContents($name, $data);

		TikiLib::lib('filegal')->upload_single_file(
			$this->definition->getInfo(),
			$name,
			$info['filesize'],
			$info['mime'],
			$info['content']
		);
	}

	function createDirectory($name) {
		global $user;

		Utilities::checkCreatePermission($this->definition);

		// Get parent filegal info as a base
		$filegalInfo = $this->definition->getInfo();

		$filegalInfo['parentId'] = $filegalInfo['galleryId'];
		$filegalInfo['galleryId'] = -1;
		$filegalInfo['name'] = $name;
		$filegalInfo['description'] = '';
		$filegalInfo['user'] = $user;

		TikiLib::lib('filegal')->replace_file_gallery($filegalInfo);
	}

	function delete() {
		Utilities::checkDeleteGalleryPermission($this->definition);

		$info = $this->definition->getInfo();
		
		TikiLib::lib('filegal')->remove_file_gallery($info['galleryId'], $info['galleryId']);
	}

	function getGalleryId() {
		$info = $this->definition->getInfo();
		return $info['galleryId'];
	}

	private function galleryChildren($find = null) {
		$info = $this->definition->getInfo();
		return TikiLib::lib('filegal')->get_files(0, -1, 'name_desc', $find, $info['galleryId'], false, true);
	}
}
