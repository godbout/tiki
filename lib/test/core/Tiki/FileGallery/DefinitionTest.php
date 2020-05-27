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
use Tiki\FileGallery\Definition;
use Tiki\FileGallery\File;

class Tiki_FileGallery_DefinitionTest extends TikiTestCase
{
	protected function setUp() : void
	{
		global $prefs;
		$this->oldPrefs = $prefs;
		parent::setUp();
		TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
	}

	protected function tearDown() : void
	{
		global $prefs;
		$prefs = $this->oldPrefs;
		TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
	}

	public function testInstantiation()
	{
		$definition = new Definition(['type' => 'system']);
		$info = $definition->getInfo();

		$this->assertEquals('system', $info['type']);
	}

	public function testDeleteFile()
	{
		global $prefs;

		$prefs['fgal_use_db'] = 'n';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

		$file = new File(['data' => '', 'path' => 'abcdtest']);
		file_put_contents($prefs['fgal_use_dir'] . '/' . $file->path, 'test contents');

		$file->galleryDefinition()->delete($file);

		$this->assertFileNotExists($prefs['fgal_use_dir'] . '/abcdtest');
	}

	public function testFixFileLocationDbToDb()
	{
		global $prefs;

		$prefs['fgal_use_db'] = 'y';
		$file = new File(['data' => 'test contents', 'path' => '']);

		$file->galleryDefinition()->fixFileLocation($file);

		$this->assertEquals('test contents', $file->data);
		$this->assertEquals('', $file->path);
	}

	public function testFixFileLocationDbToDisk()
	{
		global $prefs;

		$prefs['fgal_use_db'] = 'n';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

		$file = new File(['data' => 'test contents', 'path' => '']);

		$file->galleryDefinition()->fixFileLocation($file);

		$this->assertEquals('', $file->data);
		$this->assertNotEmpty($file->path);

		$this->assertEquals('test contents', file_get_contents($prefs['fgal_use_dir'] . '/' . $file->path));
	}

	public function testFixFileLocationDiskToDb()
	{
		global $prefs;

		$prefs['fgal_use_db'] = 'y';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

		$file = new File(['data' => '', 'path' => 'abcdtest']);
		file_put_contents($prefs['fgal_use_dir'] . '/' . $file->path, 'test contents');

		$file->galleryDefinition()->fixFileLocation($file);

		$this->assertEquals('test contents', $file->data);
		$this->assertEquals('', $file->path);

		$this->assertFileNotExists($prefs['fgal_use_dir'] . '/abcdtest');
	}

	public function testFixFileLocationDiskToDisk()
	{
		global $prefs;

		$prefs['fgal_use_db'] = 'n';
		$prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

		$file = new File(['data' => '', 'path' => 'abcdtest']);
		file_put_contents($prefs['fgal_use_dir'] . '/' . $file->path, 'test contents');

		$file->galleryDefinition()->fixFileLocation($file);

		$this->assertEquals('', $file->data);
		$this->assertNotEmpty($file->path);

		$this->assertEquals('test contents', file_get_contents($prefs['fgal_use_dir'] . '/' . $file->path));
	}
}
