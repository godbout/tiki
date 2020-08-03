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
use Tiki\FileGallery\Manipulator\MetadataExtractor;

class Tiki_FileGallery_Manipulator_MetadataExtractorTest extends TikiTestCase
{
    protected function setUp() : void
    {
        global $prefs;
        $this->oldPrefs = $prefs;
        $prefs['fgal_use_db'] = 'y';

        $path = __DIR__ . '/../../../../filegals/testdata.png';
        $data = file_get_contents($path);
        $this->file = new File(['filename' => 'testdata.png', 'filetype' => 'image/png', 'data' => $data]);

        parent::setUp();
    }

    protected function tearDown() : void
    {
        global $prefs;
        $prefs = $this->oldPrefs;
    }

    public function testMetadata()
    {
        global $prefs;

        (new MetadataExtractor($this->file))->run();

        $this->assertNotEmpty($this->file->metadata);
        $meta = json_decode($this->file->metadata, true);
        $this->assertEquals(strlen($this->file->data), $meta['Basic Information']['File Data']['size']['newval']);
    }

    public function testNameSanitizing()
    {
        $this->file->setParam('name', ' <b>testdata.png</b>');
        (new MetadataExtractor($this->file))->run();
        $this->assertEquals('testdata.png', $this->file->filename);
    }

    public function testDescriptionSanitizing()
    {
        $this->file->setParam('description', '<p>HTML formatted <strong>text</strong>.</p>');
        (new MetadataExtractor($this->file))->run();
        $this->assertEquals('HTML formatted text.', $this->file->description);
    }

    public function testNameExtractionFromFilename()
    {
        $this->file->setParam('name', 'test-data.png');
        $this->file->setParam('filename', 'test-data.png');
        (new MetadataExtractor($this->file))->run();
        $this->assertEquals('Test Data', $this->file->name);
    }

    public function testCreatedSoon()
    {
        (new MetadataExtractor($this->file))->run();
        $this->assertEqualsWithDelta(time(), $this->file->created, 3600); // this is a 1 hour delta from beginning of test suite...
    }

    public function testCreatedUnchangedForExistingFiles()
    {
        $this->file->setParam('created', 123);
        (new MetadataExtractor($this->file))->run();
        $this->assertEquals(123, $this->file->created);
    }

    public function testLastModifSoon()
    {
        (new MetadataExtractor($this->file))->run();
        $this->assertEqualsWithDelta(time(), $this->file->lastModif, 3600); // this is a 1 hour delta from beginning of test suite...
    }

    public function testFiletypeFix()
    {
        global $prefs;
        $prefs['fgal_fix_mime_type'] = 'y';

        $this->file->setParam('filetype', 'application/octet-stream');
        (new MetadataExtractor($this->file))->run();
        $this->assertEquals('image/png', $this->file->filetype);
    }
}
