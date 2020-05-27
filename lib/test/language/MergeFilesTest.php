<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../language/MergeFiles.php');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

class Language_MergeFilesTest extends TikiTestCase
{
	protected $obj;

	protected function setUp() : void
	{
		$root = vfsStream::setup('root');
		$this->sourceFile = new vfsStreamFile('language_source.php');
		$this->targetFile = new vfsStreamFile('language_target.php');
		$this->targetFile->setContent(file_get_contents(__DIR__ . '/fixtures/language_merge_files_original.php'));
		$root->addChild($this->sourceFile);
		$root->addChild($this->targetFile);

		$sourceFilePath = vfsStream::url('root/language_source.php');
		$targetFilePath = vfsStream::url('root/language_target.php');
		$this->sourceFileObj = $this->getMockBuilder('Language_File')
									->onlyMethods(['parse'])
									->setConstructorArgs([$sourceFilePath])
									->getMock();

		$this->targetFileObj = $this->getMockBuilder('Language_File')
									->onlyMethods(['parse'])
									->setConstructorArgs([$targetFilePath])
									->getMock();

		$this->obj = new Language_MergeFiles($this->sourceFileObj, $this->targetFileObj);
	}

	public function testMerge_shouldUpdateTargetFileWithTranslationsFromSourceFile(): void
	{
		$sourceFileData = [
			'Bytecode Cache' => ['key' => 'Bytecode Cache', 'translated' => false],
			'Used' => ['key' => 'Used', 'translation' => "Usado", 'translated' => true],
			 'Available' => ['key' => 'Available', 'translation' => 'Disponível', 'translated' => true],
			'Memory' => ['key' => 'Memory', 'translation' => 'Memória', 'translated' => true],
			'%0 enabled' => ['key' => '%0 enabled', 'translation' => '%0 habilitado', 'translated' => true],
			'Features' => ['key' => 'Features', 'translation' => 'Recursos', 'translated' => true],
			'Wiki Config' => ['key' => 'Wiki Config', 'translations' => 'Configuração Wiki', 'translated' => true],
		];

		$targetFileData = [
			'Bytecode Cache' => ['key' => 'Bytecode Cache', 'translated' => false],
			'Used' => ['key' => 'Used', 'translation' => "Usado", 'translated' => true],
			 'Available' => ['key' => 'Available', 'translated' => false],
			'Memory' => ['key' => 'Memory', 'translated' => false],
			'%0 enabled' => ['key' => '%0 enabled', 'translation' => '%0 habilitado', 'translated' => true],
			'Features' => ['key' => 'Features', 'translation' => 'Recursos antigos', 'translated' => true],
			'Tiki Admin' => ['key' => 'Tiki Admin', 'translation' => 'Administração do Tiki', 'translated' => true],
		];

		$this->sourceFileObj->expects($this->once())->method('parse')->willReturn($sourceFileData);
		$this->targetFileObj->expects($this->once())->method('parse')->willReturn($targetFileData);

		$this->obj->merge();

		$this->assertFileEquals(__DIR__ . '/fixtures/language_merge_files_result.php', vfsStream::url('root/language_target.php'));
	}
}
