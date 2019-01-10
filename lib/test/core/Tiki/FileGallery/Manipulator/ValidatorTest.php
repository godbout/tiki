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
  function setUp() {
    global $prefs;
    $this->oldPrefs = $prefs;
    parent::setUp();
  }

  function tearDown() {
    global $prefs;
    $prefs = $this->oldPrefs;
  }

  function testFilenameValidByDefault()
  {
    $file = new File(['filename' => 'test.zip']);
    $this->assertTrue((new Validator($file))->run());
  }

  function testFilenameInvalidByRegexp()
  {
    global $prefs;

    $prefs['fgal_match_regex'] = ".*\.png$";

    $file = new File(['filename' => 'test.zip']);
    $this->assertFalse((new Validator($file))->run());
  }

  function testFilenameValidByRegexp()
  {
    global $prefs;

    $prefs['fgal_match_regex'] = ".*\.zip$";

    $file = new File(['filename' => 'test.zip']);
    $this->assertTrue((new Validator($file))->run());
  }

  function testDuplicateChecksum()
  {
    global $prefs;

    $prefs['fgal_allow_duplicates'] = 'n';
    $filesTable = TikiLib::lib('filegal')->table('tiki_files');
    $filesTable->insert(['hash' => '123456789']);

    $file = new File(['filename' => 'test.zip', 'hash' => '123456789']);
    $this->assertFalse((new Validator($file))->run());
  }
}
