<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../../language/Exception.php');
require_once(__DIR__ . '/../../../language/WriteFile/Factory.php');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

class Language_WriteFile_FactoryTest extends TikiTestCase
{
    protected $obj;

    protected function setUp() : void
    {
        // setup a mock filesystem
        $lang = vfsStream::setup('lang');
        $this->langFile = new vfsStreamFile('language.php');
        $lang->addChild($this->langFile);

        $this->filePath = vfsStream::url('lang/language.php');

        $this->obj = new Language_WriteFile_Factory;
    }

    public function testFactory_shouldReturnWriteFileObject()
    {
        $writeFile = $this->obj->factory($this->filePath);
        $this->assertInstanceOf(Language_WriteFile::class, $writeFile);
    }
}
