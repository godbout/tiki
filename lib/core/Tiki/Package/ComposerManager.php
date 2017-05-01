<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package;

use Symfony\Component\Config\Definition\Exception\Exception;

class ComposerManager
{

	const STATUS_INSTALLED = 'installed';
	const STATUS_MISSING = 'missing';


	protected $basePath = '';
	protected $packagesNamespace;

	/** @var ComposerCli */
	protected $composerWrapper;

	function __construct($basePath)
	{
		$this->packagesNamespace = __NAMESPACE__ . '\\External\\';
		$this->basePath = $basePath;
		$this->composerWrapper = new ComposerCli($basePath);
	}

	public function composerIsAvailable()
	{
		return $this->composerWrapper->canExecuteComposer();
	}

	public function getInstalled()
	{
		return $this->composerWrapper->getListOfPackagesFromConfig();
	}

	public function fixMissing()
	{
		return $this->composerWrapper->installMissingPackages();
	}

	public function getAvailable($filterInstalled = true)
	{
		$packagesDir = __DIR__ . DIRECTORY_SEPARATOR . 'External';
		if (!is_dir($packagesDir)) {
			return [];
		}

		$installedPackages = $this->getListOfInstalledPackages($filterInstalled);

		$availablePackages = [];
		foreach (new \GlobIterator($packagesDir . DIRECTORY_SEPARATOR . '*.php') as $fileInfo) {
			$class = $fileInfo->getBasename('.php');
			$fullClassName = $this->packagesNamespace . $class;
			if (class_exists($fullClassName)) {
				try {
					/** @var ComposerPackage $externalPackage */
					$externalPackage = new $fullClassName;
					if ($externalPackage->getType() != Type::COMPOSER) {
						continue;
					}
					if ($filterInstalled && array_key_exists($externalPackage->getName(), $installedPackages)) {
						continue;
					}
					$availablePackages[] = $externalPackage->getAsArray();
				} catch (Exception $e) {
					//ignore
				}
			}
		}

		return $availablePackages;
	}

	/**
	 * @param $filterInstalled
	 * @return array
	 */
	protected function getListOfInstalledPackages($filterInstalled)
	{
		$installedPackages = [];
		if ($filterInstalled) {
			foreach ($this->getInstalled() as $pkg) {
				if ($pkg['status'] == self::STATUS_INSTALLED) {
					$installedPackages[$pkg['name']] = $pkg['name'];
				}
			}
		}

		return $installedPackages;
	}

	public function installPackage($packageKey)
	{
		$packageKey = preg_replace("/[^a-zA-Z0-9]+/", "", $packageKey);
		$packageClass = $this->packagesNamespace . $packageKey;
		try {
			if (class_exists($packageClass)) {
				/** @var ComposerPackage $externalPackage */
				$externalPackage = new $packageClass;

				return $this->composerWrapper->installPackage($externalPackage);
			}
		} catch (Exception $e) {
			//ignore
		}
	}
}