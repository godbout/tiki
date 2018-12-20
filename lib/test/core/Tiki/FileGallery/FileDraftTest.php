<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */

use Tiki\FileGallery\File;
use Tiki\FileGallery\FileDraft;

class Tiki_FileGallery_FileDraftTest extends TikiTestCase
{
	function testFromFile()
	{
		$file = new File(['filename' => 'test.zip', 'name' => 'test']);
		$draft = FileDraft::fromFile($file);
		$this->assertEquals('test.zip', $draft->filename);
		$params = $draft->getParams();
		$this->assertFalse(isset($params['name']));
	}

	function testFromFileDraft()
	{
		$draft = FileDraft::fromFileDraft(['filename' => 'test.zip']);
		$this->assertEquals('test.zip', $draft->filename);
	}
}
