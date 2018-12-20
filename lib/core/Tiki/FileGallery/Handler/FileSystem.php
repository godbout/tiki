<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Handler;

use Tiki\FileGallery\FileWrapper\PhysicalFile;

class FileSystem implements HandlerInterface
{
	private $directory;

	function __construct($directory)
	{
		$this->directory = $directory;
		$this->directory = rtrim($directory, '/\\');
	}

	function getFileWrapper($file)
	{
		return new PhysicalFile($this->directory, $file->path);
	}

	function delete($file)
	{
		$full = "{$this->directory}/$file->path";

		if ($file->path && is_writable($full)) {
			unlink($full);
		}
	}

	function uniquePath($file) {
		if (! empty($file->path)) {
			return $file->path;
		}
		$fhash = md5($file->name);
		while (file_exists($this->directory . '/' . $fhash)) {
			$fhash = md5(uniqid($fhash));
		}
		return $fhash;
	}

	function isWritable() {
		return is_writable($this->directory);
	}
}
