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

use org\bovigo\vfs\vfsStream;
use Tiki\FileGallery\File;
use Tiki\FileGallery\FileWrapper;

class Tiki_FileGallery_FileTest extends TikiTestCase
{
	function setUp() {
		global $prefs;
		$this->oldPrefs = $prefs;
		parent::setUp();
		\TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
	}

	function tearDown() {
		global $prefs;
		$prefs = $this->oldPrefs;
		\TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
	}

	function testInstantiation()
	{
		$file = new File();
		$this->assertEquals(0, $file->fileId);
		$this->assertFalse($file->exists());
	}

	function testInitialization()
	{
		$file = new File(['filename' => 'test.zip']);
		$this->assertEquals('test.zip', $file->filename);
	}

	function testLoading()
	{
		$file = new File(['filename' => 'test.zip']);
		$filesTable = TikiLib::lib('filegal')->table('tiki_files');
		$fileId = $filesTable->insert($file->getParamsForDb());

		$file = File::id($fileId);
		$this->assertEquals('test.zip', $file->filename);

		$filesTable->delete(['fileId' => $fileId]);
	}

	function testCorrectWrapper()
	{
		global $prefs;

		$file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
		$prefs['fgal_use_db'] = 'y';

		$this->assertInstanceOf(FileWrapper\PreloadedContent::class, $file->getWrapper());

		TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

		$file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => 'abcdtest']);
		$prefs['fgal_use_db'] = 'n';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

		$this->assertInstanceOf(FileWrapper\PhysicalFile::class, $file->getWrapper());
		$this->assertEmpty($file->data);
	}

	function testReplaceContents()
	{
		global $prefs;

		$file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
		$prefs['fgal_use_db'] = 'y';

		$file->replaceContents('updated content');
		$this->assertEquals('updated content', $file->data);
		$this->assertEmpty($file->path);

		TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

		$file = new File(['filename' => 'test.txt', 'data' => '', 'path' => 'abcdtest']);
		$prefs['fgal_use_db'] = 'n';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
		file_put_contents($prefs['fgal_use_dir'].'/'.$file->path, 'test content');

		$file->replaceContents('updated content');
		$this->assertEquals('updated content', file_get_contents($prefs['fgal_use_dir'].'/'.$file->path));
		$this->assertEmpty($file->data);
	}
}
