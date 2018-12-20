<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\FileWrapper;

class PreloadedContent implements WrapperInterface
{
	private $data;

	private $temporaryFile = false;

	function __construct($data)
	{
		$this->data = $data;
	}

	function __destruct()
	{
		if (false !== $this->temporaryFile) {
			\unlink($this->temporaryFile);
		}
	}

	function getReadableFile()
	{
		if (false !== $this->temporaryFile) {
			return $this->temporaryFile;
		}

		$sIniUploadTmpDir = \ini_get('upload_tmp_dir');
		if (! empty($sIniUploadTmpDir)) {
			$sTmpDir = \ini_get('upload_tmp_dir');
		} else {
			$sTmpDir = '/tmp';
		}

		$this->temporaryFile = $tmpfname = \tempnam($sTmpDir, 'wiki_');
		@\file_put_contents($tmpfname, $this->data);
		return $tmpfname;
	}

	function getContents()
	{
		return $this->data;
	}

	function getChecksum()
	{
		return md5($this->data);
	}

	function getSize() {
		return function_exists('mb_strlen') ? mb_strlen($this->data, '8bit') : strlen($this->data);
	}

	function isFileLocal() {
		return false;
	}

	function replaceContents($data) {
		$this->data = $data;
	}

	function getStorableContent() {
		return [
			'data' => $this->data,
			'path' => null,
			'filesize' => $this->getSize(),
			'hash' => $this->getChecksum(),
		];
	}
}
