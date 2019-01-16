<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery;

class Definition
{
	private $info;

	function __construct($info)
	{
		$this->info = $info;
		if (isset($this->info['id']) && ! isset($this->info['galleryId'])) {
			$this->info['galleryId'] = $this->info['id'];
		}
		$this->handler = $this->getHandler($info);
	}

	/**
	 * Get file wrapper object responsible for accessing the underlying storage.
	 * @see FileWrapper\WrapperInterface for supported methods.
	 */
	function getFileWrapper($file)
	{
		return $this->handler->getFileWrapper($file);
	}

	/**
	 * @see Handler\HandlerInterface
	 */
	function delete($file)
	{
		$this->handler->delete($file);
	}

	/**
	 * @see Handler\HandlerInterface
	 */
	function uniquePath($file) {
		return $this->handler->uniquePath($file);
	}

	/**
	 * @see Handler\HandlerInterface
	 */
	function isWritable() {
		return $this->handler->isWritable();
	}

	function getInfo()
	{
		return $this->info;
	}

	/**
	 * Updates file contents based on chosen underlying storage.
	 * Currently, we have: db storage or filesystem storage.
	 * This method updates the file contents to be in the right place.
	 */
	function fixFileLocation($file) {
		global $prefs;

		if ($file->path) {
			$handler = new Handler\FileSystem($prefs['fgal_use_dir']);
		} else {
			$handler = new Handler\Preloaded;
		}

		$data = $handler->getFileWrapper($file)->getContents();
		$orig = $file->clone();
		
		if ($file->replaceContents($data)) {
			if ($handler->getFileWrapper($file) != $file->getWrapper()) {
				$handler->delete($orig);
			}
		}
	}

	private function getHandler($info)
	{
		switch ($info['type']) {
			case 'podcast':
			case 'vidcast':
				return new Handler\Podcast();
			case 'system':
			default:
				return new Handler\System();
		}
	}
}
