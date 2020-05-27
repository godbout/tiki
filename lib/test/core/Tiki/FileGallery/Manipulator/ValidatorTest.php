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
use Tiki\FileGallery\Manipulator\Validator;

class Tiki_FileGallery_Manipulator_ValidatorTest extends TikiTestCase
{
	protected function setUp() : void
	{
		global $prefs;
		$this->oldPrefs = $prefs;
		parent::setUp();
	}

	protected function tearDown() : void
	{
		global $prefs;
		$prefs = $this->oldPrefs;
	}

	public function testFilenameValidByDefault()
	{
		$file = new File(['filename' => 'test.zip']);
		$this->assertTrue((new Validator($file))->run());
	}

	public function testFilenameInvalidByRegexp()
	{
		global $prefs;

		$prefs['fgal_match_regex'] = ".*\.png$";

		$file = new File(['filename' => 'test.zip']);
		$this->assertFalse((new Validator($file))->run());
	}

	public function testFilenameValidByRegexp()
	{
		global $prefs;

		$prefs['fgal_match_regex'] = ".*\.zip$";

		$file = new File(['filename' => 'test.zip']);
		$this->assertTrue((new Validator($file))->run());
	}

	public function testDuplicateChecksum()
	{
		global $prefs;

		$prefs['fgal_allow_duplicates'] = 'n';
		$filesTable = TikiLib::lib('filegal')->table('tiki_files');
		$filesTable->insert(['hash' => '123456789']);

		$file = new File(['filename' => 'test.zip', 'hash' => '123456789']);
		$this->assertFalse((new Validator($file))->run());
	}
}
