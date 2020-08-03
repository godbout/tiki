<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../language/CollectFiles.php');
require_once(__DIR__ . '/../../language/WriteFile.php');
require_once(__DIR__ . '/../../language/GetStrings.php');
require_once(__DIR__ . '/../../language/FileType.php');
require_once(__DIR__ . '/../../language/FileType/Php.php');
require_once(__DIR__ . '/../../language/FileType/Tpl.php');
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;

class Language_GetStringsEndToEndTest extends TikiTestCase
{
    protected function setUp() : void
    {
        // setup a mock filesystem with directories and files
        $root = vfsStream::setup('root');
        $dir1 = new vfsStreamDirectory('dir1');
        $dir2 = new vfsStreamDirectory('dir2');
        $this->langDir = new vfsStreamDirectory('lang');
        $this->esDir = new vfsStreamDirectory('es');

        $file1 = new vfsStreamFile('file1.tpl');
        $file1->setContent(file_get_contents(__DIR__ . '/fixtures/test_collecting_strings.tpl'));

        $file2 = new vfsStreamFile('file2.php');
        $file2->setContent(file_get_contents(__DIR__ . '/fixtures/test_collecting_strings.php'));

        $langFile = new vfsStreamFile('language.php');
        $langFile->setContent(file_get_contents(__DIR__ . '/fixtures/language_end_to_end_test_original.php'));

        $dir1->addChild($file1);
        $dir2->addChild($file2);
        $this->langDir->addChild($this->esDir);
        $this->esDir->addChild($langFile);

        $root->addChild($dir1);
        $root->addChild($dir2);
        $root->addChild($this->langDir);
    }

    /**
     * Ignore language file headers
     * @param $fileContents Contents of the language file
     * @return string Part of the language file which defines $lang only
     */
    private static function getLangDefinition($fileContents): string
    {
        $start = strpos($fileContents, '$lang');

        return substr($fileContents, $start);
    }

    public function testGetStrings_endToEnd(): void
    {
        $obj = new Language_GetStrings(
            new Language_CollectFiles,
            new Language_WriteFile_Factory,
            ['baseDir' => vfsStream::url('root')]
        );
        $obj->addFileType(new Language_FileType_Php);
        $obj->addFileType(new Language_FileType_Tpl);
        $obj->run();

        $this->assertEquals(
            self::getLangDefinition(file_get_contents(__DIR__ . '/fixtures/language_end_to_end_test_modified.php')),
            self::getLangDefinition(file_get_contents(vfsStream::url('root/lang/es/language.php')))
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/language_end_to_end_test_original.php',
            vfsStream::url('root/lang/es/language.php.old')
        );
    }

    public function testGetStrings_endToEnd_customLanguageFileName(): void
    {
        $fileName = 'language_r.php';

        $langFile = new vfsStreamFile($fileName);
        $langFile->setContent(file_get_contents(__DIR__ . '/fixtures/language_end_to_end_test_original.php'));
        $this->esDir->addChild($langFile);

        $obj = new Language_GetStrings(
            new Language_CollectFiles,
            new Language_WriteFile_Factory,
            ['baseDir' => vfsStream::url('root'), 'fileName' => 'language_r.php']
        );

        $obj->addFileType(new Language_FileType_Php);
        $obj->addFileType(new Language_FileType_Tpl);
        $obj->run();

        $this->assertEquals(
            self::getLangDefinition(file_get_contents(__DIR__ . '/fixtures/language_end_to_end_test_modified.php')),
            self::getLangDefinition(file_get_contents(vfsStream::url("root/lang/es/$fileName")))
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/language_end_to_end_test_original.php',
            vfsStream::url("root/lang/es/$fileName.old")
        );
    }

    public function testGetStrings_endToEnd_severalLanguageFiles(): void
    {
        $ruDir = new vfsStreamDirectory('ru');
        $faDir = new vfsStreamDirectory('fa');

        $ruFile = new vfsStreamFile('language.php');
        $ruFile->setContent(file_get_contents(__DIR__ . '/fixtures/language_ru_original.php'));
        $ruDir->addChild($ruFile);

        $faFile = new vfsStreamFile('language.php');
        $faFile->setContent(file_get_contents(__DIR__ . '/fixtures/language_fa_original.php'));
        $faDir->addChild($faFile);

        $this->langDir->addChild($ruDir);
        $this->langDir->addChild($faDir);

        $obj = new Language_GetStrings(
            new Language_CollectFiles,
            new Language_WriteFile_Factory,
            ['baseDir' => vfsStream::url('root')]
        );
        $obj->addFileType(new Language_FileType_Php);
        $obj->addFileType(new Language_FileType_Tpl);
        $obj->run();

        $this->assertEquals(
            self::getLangDefinition(file_get_contents(__DIR__ . '/fixtures/language_ru_modified.php')),
            self::getLangDefinition(file_get_contents(vfsStream::url('root/lang/ru/language.php')))
        );

        $this->assertEquals(
            self::getLangDefinition(file_get_contents(__DIR__ . '/fixtures/language_fa_modified.php')),
            self::getLangDefinition(file_get_contents(vfsStream::url('root/lang/fa/language.php')))
        );
    }
}
