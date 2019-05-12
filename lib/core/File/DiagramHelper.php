<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use Tiki\FileGallery\File as TikiFile;
use Tiki\Package\VendorHelper;

class DiagramHelper
{
	/**
	 * Check if file is a diagram
	 *
	 * @param $fileId
	 * @return bool
	 */
	public static function isDiagram($fileId)
	{
		$file = TikiFile::id($fileId);
		$type = $file->getParam('filetype');
		$data = trim($file->getContents());

		if ($type == 'text/plain' && (strpos($data, '<mx') === 0)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if needed core files exist in order to enable Diagrams
	 * @return bool
	 */
	public static function isPackageInstalled()
	{
		return VendorHelper::getAvailableVendorPath('mxgraph', '/xorti/mxgraph-editor/mxClient.min.js') !== false;
	}

	/**
	 * Parse diagram raw data
	 * @param $data
	 * @return string
	 */
	public static function parseData($data)
	{
		return preg_replace('/\s+/', ' ', $data);
	}
}
