<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\ComposerManager;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class Tiki_Package_ComposerManagerTest extends TikiTestCase
{

	/** @var vfsStreamDirectory */
	protected $root;
	/** @var string */
	protected $rootPath;

	/** @var  ComposerManager */
	protected $composerManager;

	function setUp()
	{
		parent::setUp();

		$this->root = vfsStream::setup(__CLASS__);
		$this->rootPath = vfsStream::url(__CLASS__);

		$this->composerManager = new ComposerManager(
			$this->rootPath,
			null,
			null,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);
	}

	function testGetComposer()
	{
		$this->assertInstanceOf('Tiki\Package\ComposerCli', $this->composerManager->getComposer());
	}

	function testComposerPath()
	{
		$this->assertEquals($this->composerManager->composerPath(), $this->rootPath . '/temp/composer.phar');
	}

	function testBrokenYaml()
	{

		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli->method('getListOfPackagesFromConfig')
			->willReturn([]);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackagesBroken.yml'
		);

		$response = $composerManager->getAvailable();

		$this->assertCount(0, $response);
	}

	function testIfNoPackageIsInstalledAllAreAvailable()
	{

		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli->method('getListOfPackagesFromConfig')
			->willReturn([]);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$response = $composerManager->getAvailable();
		$nameOfPackages = array_column($response, 'name');

		$this->assertContains('jerome-breton/casperjs-installer', $nameOfPackages);
		$this->assertContains('enygma/expose', $nameOfPackages);
	}

	function testPackageNotAvailableIfInstalled()
	{

		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli->method('getListOfPackagesFromConfig')
			->willReturn(
				[
					[
						'name' => 'jerome-breton/casperjs-installer',
						'status' => 'installed',
						'required' => '^1.0.0',
						'installed' => '1.2.3',
					],
				]
			);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$response = $composerManager->getAvailable();
		$nameOfPackages = array_column($response, 'name');

		$this->assertNotContains('jerome-breton/casperjs-installer', $nameOfPackages);
		$this->assertContains('enygma/expose', $nameOfPackages);
	}

	function testAllPackagesAvailableIfNotFiltered()
	{

		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli->method('getListOfPackagesFromConfig')
			->willReturn(
				[
					[
						'name' => 'jerome-breton/casperjs-installer',
						'status' => 'installed',
						'required' => '^1.0.0',
						'installed' => '1.2.3',
					],
				]
			);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$response = $composerManager->getAvailable(false);
		$nameOfPackages = array_column($response, 'name');

		$this->assertContains('jerome-breton/casperjs-installer', $nameOfPackages);
		$this->assertContains('enygma/expose', $nameOfPackages);
	}

	function testInstallNotExistingPackage()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['installPackage'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$this->assertNull($composerManager->installPackage('FooBar'));
	}

	function testInstallPackage()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['canExecuteComposer', 'installMissingPackages'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli
			->expects($this->once())
			->method('canExecuteComposer')
			->willReturn(true);

		$composerCli
			->expects($this->once())
			->method('installMissingPackages')
			->willReturn('__PACKAGE__INSTALLED__');

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$this->assertRegexp('/__PACKAGE__INSTALLED__/', $composerManager->installPackage('CasperJS'));

		$this->assertJsonFileEqualsJsonFile(
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'CasperJsComposer.json',
			$this->rootPath . '/composer.json'
		);
	}

	function testGetInstalled()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli
			->expects($this->once())
			->method('getListOfPackagesFromConfig')
			->willReturn(
				[
					[
						'name' => 'jerome-breton/casperjs-installer',
						'status' => 'installed',
						'required' => '^1.0.0',
						'installed' => '1.2.3',
					],
					[
						'name' => 'Foo/Bar',
						'status' => 'installed',
						'required' => '^2.0.0',
						'installed' => '2.2.3',
					],
				]
			);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$response = $composerManager->getInstalled();

		$this->assertCount(2, $response);

		$this->assertEquals('CasperJS', $response[0]['key']);
		$this->assertEquals('jerome-breton/casperjs-installer', $response[0]['name']);

		$this->assertEquals('', $response[1]['key']);
		$this->assertEquals('Foo/Bar', $response[1]['name']);
	}

	function testGetInstalledCaseMismatch()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['getListOfPackagesFromConfig'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli
			->expects($this->once())
			->method('getListOfPackagesFromConfig')
			->willReturn(
				[
					[
						'name' => 'JEROME-BRETON/casperjs-installer',
						'status' => 'installed',
						'required' => '^1.0.0',
						'installed' => '1.2.3',
					],
				]
			);

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$response = $composerManager->getInstalled();

		$this->assertCount(1, $response);

		$this->assertEquals('CasperJS', $response[0]['key']);
		$this->assertEquals('JEROME-BRETON/casperjs-installer', $response[0]['name']);
	}

	function testRemoveUnknownPackageFails()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['removePackage'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli
			->expects($this->never())
			->method('removePackage');

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$this->assertNull($composerManager->removePackage('FooBar'));
	}

	function testRemovePackage()
	{
		$composerCli = $this->getMockBuilder('Tiki\Package\ComposerCli')
			->setMethods(['removePackage'])
			->setConstructorArgs([$this->rootPath])
			->getMock();

		$composerCli
			->expects($this->once())
			->method('removePackage')
			->willReturn('__PACKAGE__REMOVED__');

		$composerManager = new ComposerManager(
			$this->rootPath,
			null,
			$composerCli,
			__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml'
		);

		$this->assertEquals('__PACKAGE__REMOVED__', $composerManager->removePackage('CasperJS'));
	}

	/**
	 * @dataProvider providerForTestCheckThatCanInstallPackages
	 */
	function testCheckThatCanInstallPackages($files, $expected)
	{
		foreach ($files as $file) {
			list( $path, $isDir, $mode) = $file;
			if ($path === '/') {
				$this->root->chmod($mode);
			}
			if ($isDir) {
				vfsStream::newDirectory($path, $mode)->at($this->root);
			} else {
				vfsStream::newFile($path, $mode)->at($this->root);
			}
		}

		$result = $this->composerManager->checkThatCanInstallPackages();

		$this->assertEquals($expected, $result);
	}

	function providerForTestCheckThatCanInstallPackages()
	{
		return [
			[ // root dir writable, no files
				[
					['/', true, 0700],
				],
				[]
			],
			[ // root dir and all files / dir writable
				[
					['/', true, 0700],
					['vendor', true, 0700],
					['composer.json', false, 0600],
					['composer.lock', false, 0600],
				],
				[]
			],
			[ // root dir not writable, all files and directories writable and in place
				[
					['/', true, 0111],
					['vendor', true, 0700],
					['composer.json', false, 0600],
					['composer.lock', false, 0600],
				],
				[]
			],
			[ // all in place, but nothing is writable
				[
					['/', true, 0111],
					['vendor', true, 0500],
					['composer.json', false, 0400],
					['composer.lock', false, 0400],
				],
				[
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.json"',
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.lock"',
					'Tiki can not write to directory "vfs://Tiki_Package_ComposerManagerTest/vendor"',
				]
			],
			[ // root dir not writable, no files
				[
					['/', true, 0000],
				],
				[
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.json" can not be created',
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.lock" can not be created',
					'Tiki root directory is not writable, so directory "vfs://Tiki_Package_ComposerManagerTest/vendor" can not be created',
				]
			],
			[ // Mixed Environment
				[
					['/', true, 0111],
					['vendor', true, 0700],
					['composer.json', false, 0400],
					['composer.lock', false, 0400],
				],
				[
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.json"',
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.lock"',
				]
			],
			[ // Mixed Environment
				[
					['/', true, 0111],
					['vendor', true, 0500],
					['composer.json', false, 0600],
					['composer.lock', false, 0400],
				],
				[
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.lock"',
					'Tiki can not write to directory "vfs://Tiki_Package_ComposerManagerTest/vendor"',
				]
			],
			[ // Mixed Environment
				[
					['/', true, 0111],
					['vendor', true, 0500],
					['composer.json', false, 0400],
					['composer.lock', false, 0600],
				],
				[
					'Tiki can not write to file "vfs://Tiki_Package_ComposerManagerTest/composer.json"',
					'Tiki can not write to directory "vfs://Tiki_Package_ComposerManagerTest/vendor"',
				]
			],
			[ // root dir not writable, Mixed Environment
				[
					['/', true, 0000],
					['vendor', true, 0700],
				],
				[
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.json" can not be created',
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.lock" can not be created',
				]
			],
			[ // root dir not writable, Mixed Environment
				[
					['/', true, 0000],
					['composer.json', false, 0600],
				],
				[
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.lock" can not be created',
					'Tiki root directory is not writable, so directory "vfs://Tiki_Package_ComposerManagerTest/vendor" can not be created',
				]
			],
			[ // root dir not writable, Mixed Environment
				[
					['/', true, 0000],
					['composer.lock', false, 0600],
				],
				[
					'Tiki root directory is not writable, so file "vfs://Tiki_Package_ComposerManagerTest/composer.json" can not be created',
					'Tiki root directory is not writable, so directory "vfs://Tiki_Package_ComposerManagerTest/vendor" can not be created',
				]
			],
		];
	}

	/**
	 * @covers Tiki\Package\ComposerManager::getPackageInfo()
	 */
	public function testGetPackagesInfo()
	{
		$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComposerPackages.yml';

		$expected = [
			'licence' => 'MIT',
			'licenceUrl' => 'https://github.com/jerome-breton/casperjs-installer/blob/master/LICENSE',
			'name' => 'jerome-breton/casperjs-installer',
			'requiredBy' => [
				'wikiplugin_casperjs'
			],
			'requiredVersion' => 'dev-master',
			'scripts' => [
				'post-install-cmd' => [
					"CasperJsInstaller\Installer::install"
				],
				'post-update-cmd' => [
					'CasperJsInstaller\Installer::install',
				]
			]
		];

		$this->assertEquals($expected, ComposerManager::getPackageInfo('jerome-breton/casperjs-installer', $configFile));

		$expected = [$expected];
		$expected[] = [
			'licence' => 'MIT',
			'licenceUrl' => 'https://github.com/enygma/expose/blob/master/LICENSE',
			'name' => 'enygma/expose',
			'requiredBy' => [
				'ids_enabled'
			],
			'requiredVersion' => '^3.0',
		];

		$this->assertEquals($expected, ComposerManager::getPackageInfo(['jerome-breton/casperjs-installer', 'enygma/expose'], $configFile));
	}
}
