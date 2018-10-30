<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\Alchemy;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class Guesser implements MimeTypeGuesserInterface
{

	private $fileMimeTypes = [];

	public function add($filePath, $mimeType)
	{
		$this->fileMimeTypes[$filePath] = $mimeType;
	}

	/**
	 * @inheritdoc
	 */
	public function guess($path)
	{
		if (array_key_exists($path, $this->fileMimeTypes)) {
			return $this->fileMimeTypes[$path];
		}

		return null;
	}
}
