<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Handler;

use Tiki\FileGallery\FileWrapper\PreloadedContent;

class System implements HandlerInterface
{
	private $real;

	function __construct()
	{
		global $prefs;

		if ($prefs['fgal_use_db'] == 'n') {
			$this->real = new FileSystem($prefs['fgal_use_dir']);
		} else {
			$this->real = new Preloaded;
		}
	}

	function getFileWrapper($file)
	{
		return $this->real->getFileWrapper($file);
	}

	function delete($file)
	{
		return $this->real->delete($file);
	}

	function uniquePath($file)
	{
		return $this->real->uniquePath($file);
	}

	function isWritable() {
		return $this->real->isWritable();
	}
}
