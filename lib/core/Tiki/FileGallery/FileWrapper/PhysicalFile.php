<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\FileWrapper;

class PhysicalFile implements WrapperInterface
{
	private $path;
	private $basePath;

	function __construct($basePath, $path)
	{
		$this->basePath = rtrim($basePath, '/\\');
		$this->path = $path;
	}

	function getReadableFile()
	{
		return $this->fullPath();
	}

	function getContents()
	{
		$tmpfname = $this->fullPath();

		return \file_get_contents($tmpfname);
	}

	function getChecksum()
	{
		$tmpfname = $this->fullPath();
		if (filesize($tmpfname) > 0) {
			return md5_file($tmpfname);
		} else {
			return md5(time());
		}
	}

	function getSize() {
		return filesize($this->fullPath());
	}

	function isFileLocal() {
		return true;
	}

	function replaceContents($data) {
		$dest = $this->fullPath();
		if(is_writable($this->basePath) && (! file_exists($dest) || is_writable($dest))) {
			$result = file_put_contents($dest, $data);
		} else {
			$result = false;
		}
		if ($result === false) {
			throw new WriteException(tr("Unable to write to destination path: %0", $dest));
		}
	}

	function getStorableContent() {
		return [
			'data' => null,
			'path' => $this->path,
			'filesize' => $this->getSize(),
			'hash' => $this->getChecksum(),
		];
	}

	private function fullPath() {
		return $this->basePath . '/' . $this->path;
	}
}
