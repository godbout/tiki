<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use TikiLib;
use Tiki\FileGallery\File as TikiFile;

class File extends DAV\File {

	private $file;

	function __construct($path_or_id) {
		if (intval($path_or_id) == 0) {
			$result = TikiLib::lib('filegal')->get_objectid_from_virtual_path($path);
			if (! $result || $result['type'] != 'file') {
				throw new DAV\Exception\NotFound(tr('The file with path: ' . $path_or_id . ' could not be found'));
			}
			$path_or_id = $result['id'];
		}
		$this->file = TikiFile::id($path_or_id);
	}

	function getName() {
		return $this->file->filename;
	}

	function get() {
		return $this->file->getContents();
	}

	function getSize() {
		return $this->file->filesize;
	}

	function getETag() {
		$md5 = md5($this->file->hash . $this->file->lastModif);
		return '"' . $md5 . '-' . crc32($md5) . '"';
	}

	function getContentType() {
		return $this->file->filetype;
	}

	function getLastModified() {
		return $this->file->lastModif;
	}

	function put($data) {
		Utilities::checkUploadPermission($this->file->galleryDefinition());

		$info = Utilities::parseContents($this->file->name, $data);

		TikiLib::lib('filegal')->replace_file(
			$this->file->fileId,
			$this->file->name,
			$this->file->description,
			$this->file->filename,
			$info['content'],
			$info['filesize'],
			$info['mime'],
			$this->file->author,
			$this->file->path,
			'',
			$this->file->galleryDefinition()->getInfo(),
			true,
			$this->file->author,
			$this->file->created
		);
	}

	function setName($name) {
		Utilities::checkUploadPermission($this->file->galleryDefinition());

		TikiLib::lib('filegal')->replace_file(
			$this->file->fileId,
			$name,
			$this->file->description,
			$this->file->filename,
			$this->file->data,
			$this->file->filesize,
			$this->file->filetype,
			$this->file->author,
			$this->file->path,
			'',
			$this->file->galleryDefinition()->getInfo(),
			true,
			$this->file->author,
			$this->file->created
		);
	}

	function delete() {
		Utilities::checkDeleteFilePermission($this->file->galleryDefinition());

		$this->file->delete();
	}
}
