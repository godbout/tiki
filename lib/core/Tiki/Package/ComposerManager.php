<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package;

use DirectoryIterator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Allows the management of Composer Packages
 */
class ComposerManager
{

	const STATUS_INSTALLED = 'installed';
	const STATUS_MISSING = 'missing';
	const CONFIG_PACKAGE_FILE = 'ComposerPackages.yml';

	/**
	 * @var string the path where the composer file is located
	 */
	protected $basePath = '';

	/**
	 * @var ComposerCli wrapper for composer phar
	 */
	protected $composerWrapper;


	/**
	 * @var string Path to the file with the package definition
	 */
	protected $packagesConfigFile;

	/**
	 * Setups the composer.json location
	 *
	 * @param string $basePath
	 * @param string $workingPath
	 * @param ComposerCli $composerWrapper composer.phar wrapper, optional in the constructor to allow injection for test
	 * @param string $packagesConfigFile package config file path, optional in the constructor to allow injection for test
	 */
	public function __construct($basePath, $workingPath = null, $composerWrapper = null, $packagesConfigFile = null)
	{
		$this->basePath = $basePath;

		if (is_null($composerWrapper)) {
			$composerWrapper = new ComposerCli($basePath, $workingPath);
		}
		$this->composerWrapper = $composerWrapper;

		if (is_null($packagesConfigFile)) {
			$packagesConfigFile = __DIR__ . DIRECTORY_SEPARATOR . self::CONFIG_PACKAGE_FILE;
		}
		$this->packagesConfigFile = $packagesConfigFile;
	}

	/**
	 * Return the Composer Wrapper
	 * @return ComposerCli
	 */
	public function getComposer()
	{
		return $this->composerWrapper;
	}

	/**
	 * Check if composer is available
	 * @return bool
	 */
	public function composerIsAvailable()
	{
		return $this->composerWrapper->canExecuteComposer();
	}

	/**
	 * Check that composer can install packages, and return a list of issues, if found
	 * @return array
	 */
	public function checkThatCanInstallPackages()
	{
		$errors = [];

		$composerFile = $this->composerWrapper->getComposerConfigFilePath();
		$composerLockFile = substr($composerFile, 0, -5) . '.lock'; // replace .json with .lock
		$composerRootDir = dirname($composerFile);
		$composerVendorDir = $composerRootDir . DIRECTORY_SEPARATOR . 'vendor';

		if (! is_writable($composerRootDir)) {
			if (! file_exists($composerFile)) {
				$errors[] = tr('Tiki root directory is not writable, so file "%0" can not be created', $composerFile);
			}
			if (! file_exists($composerLockFile)) {
				$errors[] = tr(
					'Tiki root directory is not writable, so file "%0" can not be created',
					$composerLockFile
				);
			}
			if (! is_dir($composerVendorDir)) {
				$errors[] = tr(
					'Tiki root directory is not writable, so directory "%0" can not be created',
					$composerVendorDir
				);
			}
		}
		if (file_exists($composerFile) && ! is_writable($composerFile)) {
			$errors[] = tr('Tiki can not write to file "%0"', $composerFile);
		}
		if (file_exists($composerLockFile) && ! is_writable($composerLockFile)) {
			$errors[] = tr('Tiki can not write to file "%0"', $composerLockFile);
		}
		if (is_dir($composerVendorDir) && ! is_writable($composerVendorDir)) {
			$errors[] = tr('Tiki can not write to directory "%0"', $composerVendorDir);
		}

		return $errors;
	}

	/**
	 * Check if composer is available
	 * @return bool
	 */
	public function composerPath()
	{
		return $this->composerWrapper->getComposerPharPath();
	}

	/**
	 * Get list of packages installed
	 * @param $fromLockFile boolean Should this packages be extracted from the .lock file?
	 * @return array|boolean
	 */
	public function getInstalled($fromLockFile = false)
	{
		$installedPackages = $fromLockFile ? $this->composerWrapper->getListOfPackagesFromLock() : $this->composerWrapper->getListOfPackagesFromConfig();
		$packageDefinitions = $this->getAvailable(false);
		$packageListLookup = [];
		foreach ($packageDefinitions as $package) {
			$packageName = $this->normalizePackageName($package['name']);
			$packageListLookup[$packageName] = $package;
		}

		if ($installedPackages !== false) {
			foreach ($installedPackages as &$package) {
				$packageName = $this->normalizePackageName($package['name']);
				if (isset($packageListLookup[$packageName])) {
					$package['key'] = $packageListLookup[$packageName]['key'];
					$package['requiredVersion'] = $packageListLookup[$packageName]['requiredVersion'];
					$package['upgradeVersion'] = $package['required'] != $package['requiredVersion'];
				} else {
					$package['key'] = '';
				}

				if (ExtensionManager::isExtension($packageName)) {
					$package['extension'] = true;
					if ($enabled = ExtensionManager::isExtensionEnabled($packageName)) {
						$package['extensionUpdate'] = ExtensionManager::get($packageName)->hasUpdate();
					}
					$package['extensionEnabled'] = $enabled;
				} else {
					$package['extension'] = false;
				}
			}
		}

		return $installedPackages;
	}

	/**
	 * Get packages installed under vendor_custom
	 * @return array
	 */
	public function getCustomPackages()
	{
		if (! file_exists($this->composerWrapper->getWorkingPath())) {
			return [];
		}

		$packages = [];
		$directories = new DirectoryIterator($this->composerWrapper->getWorkingPath());
		foreach ($directories as $directory) {
			if (! $directory->isDir() || $directory->isDot()) {
				continue;
			}

			$currentWorkingPath = $this->composerWrapper->getWorkingPath();
			$this->composerWrapper->setWorkingPath($directory->getPathname() . "/");

			// Both Tiki Packages (packages.tiki.org) or Package Extensions will have a composer.json
			if (! file_exists($directory->getPathname() . '/composer.json')) {
				$subFolderPackages = $this->getCustomPackages();
				if (! empty($subFolderPackages)) {
					foreach ($subFolderPackages as $subFolderPath => $subFolderPackage) {
						$packages[$directory->getBasename()][$subFolderPath] = $subFolderPackage;
					}
				}
			}

			// Extension Packages (at this time are not available in packages, so no composer.lock is found in folder)
			if ($isExtension = ExtensionManager::isExtension($directory->getFilename(), $directory->getPathname())) {
				$packageDetails = json_decode(file_get_contents($directory->getPathname() . '/composer.json'), true);
				$packageName = $packageDetails['name'];
				$packages[$directory->getBasename()] = [
					'name' => $packageName,
					'status' => 'installed',
					'required' => $packageDetails['version'],
					'installed' => $packageDetails['version'],
					'key' => '',
					'extension' => true,
					'extensionEnabled' => ExtensionManager::isExtensionEnabled($packageName)
				];
			}

			if (! $isExtension && $package = $this->getInstalled(true)) {
				$packages[$directory->getFilename()] = $package;
			}

			$this->composerWrapper->setWorkingPath($currentWorkingPath);
		}

		return $packages;
	}

	/**
	 * Install missing packages (according to composer.json)
	 * @return bool
	 */
	public function fixMissing()
	{
		return $this->composerWrapper->installMissingPackages();
	}

	/**
	 * Get List of available (defined) packages
	 *
	 * @param bool $filterInstalled don't return if the package is already installed
	 * @param bool $filterNonInstalable don't return if the package cannot be installed (actions)
	 * @return array
	 */
	public function getAvailable($filterInstalled = true, $filterNonInstalable = false)
	{
		$installedPackages = [];
		if ($filterInstalled) {
			$installedPackages = $this->getListOfInstalledPackages($filterInstalled);
		}

		$availablePackages = $this->manageYaml('list', $installedPackages);

		if ($filterNonInstalable) {
			$availablePackages = $this->getListOfInstalablePackages($availablePackages);
		}

		return $availablePackages;
	}

	/**
	 * return the list of packages installed
	 *
	 * @param $filterInstalled
	 * @return array
	 */
	protected function getListOfInstalledPackages($filterInstalled)
	{
		$installedPackages = [];
		if ($filterInstalled) {
			$installed = $this->getInstalled();
			if ($installed !== false) {
				foreach ($installed as $pkg) {
					if ($pkg['status'] == self::STATUS_INSTALLED) {
						$packageName = $this->normalizePackageName($pkg['name']);
						$installedPackages[$packageName] = $packageName;
					}
				}
			}
		}

		return $installedPackages;
	}

	/**
	 * return the list of packages that can be installed
	 *
	 * @param $availablePackages
	 * @return array
	 */
	protected function getListOfInstalablePackages($availablePackages)
	{
		$canBeInstalled = [];
		if ($availablePackages) {
			foreach ($availablePackages as $pkg) {
				if (! in_array('remove', $pkg['actions'])) {
					$canBeInstalled[] = $pkg;
				}
			}
		}

		return $canBeInstalled;
	}

	/**
	 * Assure that only allowed chars are present in the package key name
	 * @param $packageKey
	 * @return mixed
	 */
	protected function sanitizePackageKey($packageKey)
	{
		return preg_replace("/[^a-zA-Z0-9]+/", "", $packageKey);
	}

	/**
	 * Try to install a packages by the package key (corresponding to the class name)
	 *
	 * @param $packageKey
	 * @return bool|string
	 */
	public function installPackage($packageKey)
	{
		$externalPackage = $this->manageYaml('search', [], $packageKey);

		if (! $externalPackage) {
			return null;
		}

		return $this->composerWrapper->installPackage($externalPackage);
	}

	/**
	 * Try to update a packages by the package key (corresponding to the class name)
	 *
	 * @param $packageKey
	 * @return bool|string
	 */
	public function updatePackage($packageKey)
	{
		$externalPackage = $this->manageYaml('search', [], $packageKey);

		if (! $externalPackage) {
			return null;
		}

		return $this->composerWrapper->updatePackage($externalPackage);
	}

	/**
	 * Try to remove a packages by the package key (corresponding to the class name)
	 *
	 * @param $packageKey
	 * @return bool|string
	 */
	public function removePackage($packageKey)
	{
		$externalPackage = $this->manageYaml('search', [], $packageKey);

		if (! $externalPackage) {
			return null;
		}

		return $this->composerWrapper->removePackage($externalPackage);
	}

	/**
	 * Normalize the package name
	 *
	 * @param string $packageName
	 * @return string
	 */
	protected function normalizePackageName($packageName)
	{
		return $this->composerWrapper->normalizePackageName($packageName);
	}

	/**
	 * Manage YAML configuration file. Read the file and iterate through it, with a specific action
	 *  If action is 'list' then it will return the complete list of external packages of configuration
	 *  If action is 'search' then it will search for a specific package and return the object
	 *
	 * @param $packageAction string Valid options include 'list' and 'search'
	 * @param $installedPackages
	 * @param $packageKey
	 * @return ComposerPackage|array
	 */
	protected function manageYaml($packageAction, $installedPackages = [], $packageKey = null)
	{
		$packageKey = $this->sanitizePackageKey($packageKey);

		//Open External Packages Config File
		if (! file_exists($this->packagesConfigFile)) {
			return [];
		}
		try {
			$yamlContent = Yaml::parse(file_get_contents($this->packagesConfigFile));
			if (! $yamlContent || ! is_array($yamlContent)) {
				return [];
			}
		} catch (ParseException $e) {
			return [];
		}

		$availablePackages = [];
		foreach ($yamlContent as $key => $fileInfo) {
			try {
				if ($fileInfo) {
					if (! isset($fileInfo['scripts'])) {
						$fileInfo['scripts'] = [];
					}
					if (! isset($fileInfo['actions'])) {
						$fileInfo['actions'] = [];
					}
					$externalPackage = new ComposerPackage(
						$key,
						$fileInfo['name'],
						$fileInfo['requiredVersion'],
						$fileInfo['licence'],
						$fileInfo['licenceUrl'],
						$fileInfo['requiredBy'],
						$fileInfo['scripts'],
						$fileInfo['actions']
					);
					if ($packageAction == 'search' && $key == $packageKey) {
						return $externalPackage;
					} else {
						if ($packageAction == 'list') {
							$packageName = $this->normalizePackageName($externalPackage->getName());
							if (array_key_exists($packageName, $installedPackages)) {
								continue;
							}
							$availablePackages[] = $externalPackage->getAsArray();
						}
					}
				}
			} catch (Exception $e) {
				//ignore
			}
		}

		return $availablePackages;
	}

	/**
	 * Retrieve Package information from a PackageConfigFile
	 *
	 * @param $packageNames string
	 *   Package name, or an array with package names to lookup.
	 * @param null $packagesConfigFile
	 *   The path for a PackagesConfigFile, if empty the default will be used.
	 * @return array
	 *   An array with the packages information, empty if not found.
	 */
	public static function getPackageInfo($packageNames, $packagesConfigFile = null)
	{

		if (is_null($packagesConfigFile)) {
			$packagesConfigFile = __DIR__ . DIRECTORY_SEPARATOR . self::CONFIG_PACKAGE_FILE;
		}

		try {
			$yamlContent = Yaml::parse(file_get_contents($packagesConfigFile));
			if (! $yamlContent || ! is_array($yamlContent)) {
				return [];
			}
		} catch (ParseException $e) {
			return [];
		}

		if (! is_array($packageNames)) {
			$packageNames = [$packageNames];
		}

		$info = [];

		foreach ($yamlContent as $fileInfo) {
			if (in_array($fileInfo['name'], $packageNames)) {
				$info[] = $fileInfo;
			}
		}

		return sizeof($info) == 1 ? array_shift($info) : $info;
	}

	/**
	 * Checks if a package appears in the composer lock file
	 * @param string $packageName		Name of package as specified in composer
	 * @return bool						True upon installed, false otherwise
	 */
	public function isInstalled(string $packageName): bool
	{
		$installedPackages = $this->getInstalled(true);
		foreach ($installedPackages as $name => $info) {
			if ($packageName === $name) {
				return true;
			}
		}
		return false;
	}
}
