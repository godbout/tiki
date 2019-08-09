<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package;

use DirectoryIterator;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Tiki\Package\Extension\Extension;
use Tiki\Package\Extension\Api\Events as ApiEvents;
use Tiki\Package\Extension\Api\Search as ApiSearch;

class ExtensionManager
{
	const ENABLED_PACKAGES_FILE = 'db/config/packages.yml';

	private static $enabled = [];
	private static $installed = []; // Hold config
	private static $paths = [];
	protected static $extensions = [];

	protected static $messages = [];

	public static $availablePaths = [
		'vendor',
		'vendor_custom'
	];

	/**
	 * Get the list of extensions enabled
	 *
	 * @param bool $useContainer retrieve information from teh container or directly from the package file
	 * @return array|mixed
	 */
	public static function getEnabledPackageExtensions($useContainer = true)
	{
		if (! empty(self::$enabled)) {
			return self::$enabled;
		}

		if ($useContainer) {
			$container = \TikiInit::getContainer();
			try {
				$result = $container->getParameter('tiki.packages.extensions');
				if (! empty($result)) {
					self::$enabled = $result;
					return self::$enabled;
				}
			} catch (\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException $e) {
				// do nothing
			}
		}

		if (! file_exists(self::ENABLED_PACKAGES_FILE)) {
			return [];
		}

		if ($info = Yaml::parse(file_get_contents(self::ENABLED_PACKAGES_FILE))) {
			self::$enabled = $info;
		};

		return self::$enabled;
	}

	/**
	 * Check if a given package is a valid extension
	 *
	 * @param $packageName
	 * @param string $packagePath
	 * @return bool
	 */
	public static function isExtension($packageName, $packagePath = null)
	{
		if (is_null($packagePath)) {
			$path = implode('/', ['vendor', $packageName, 'tiki-package.json']);
		} else {
			$path = implode('/', [$packagePath, 'tiki-package.json']);
		}
		return file_exists($path);
	}

	/**
	 * Check if a given package is enabled
	 *
	 * @param $packageName
	 * @return bool
	 */
	public static function isExtensionEnabled($packageName)
	{
		$enabledAddOns = self::getEnabledPackageExtensions();
		return array_key_exists($packageName, $enabledAddOns);
	}

	/**
	 * Handler to enable a given extension
	 *
	 * @param $packageName
	 * @param string $packagePath
	 * @return bool
	 */
	public static function enableExtension($packageName, $packagePath)
	{
		if (! self::isExtension($packageName, $packagePath)) {
			self::$messages[] = tr('Package %0 is not a Tiki Extension', $packageName);
			return false;
		}

		$extensionPackage = self::get($packageName);
		$update = isset($extensionPackage) ? $extensionPackage->hasUpdate() : false;

		if (isset($extensionPackage) && ! $update) {
			self::$messages[] = tr('Package %0 is already enabled', $packageName);
			return false;
		}

		$extensionPackage = Extension::createFromPackageInDisk($packagePath);
		list($success, $log) = $extensionPackage->enable($update);
		self::$messages = array_merge(self::$messages, $log);

		if ($success) {
			$enabledPackages = self::getEnabledPackageExtensions();
			$enabledPackages[$packageName] = [
				'name' => $packageName,
				'path' => $packagePath,
				'config' => $extensionPackage->exportConfiguration()
			];

			file_put_contents(self::ENABLED_PACKAGES_FILE, Yaml::dump($enabledPackages));

			self::$enabled = []; // Force reload from file

			\TikiLib::lib('cache')->invalidate('global_preferences');
			\TikiLib::lib('cache')->invalidate('tiki_default_preferences_cache');
		}

		if (file_exists($packagePath . '/lang')) {
			self::refreshLanguages($packagePath . '/lang');
		}

		return $success;
	}

	/**
	 * Handler to disable a given extension
	 *
	 * @param $packageName
	 * @param bool $rollback
	 * @return bool|string
	 */
	public static function disableExtension($packageName, $rollback = false)
	{
		if (! self::isExtensionEnabled($packageName)) {
			self::$messages[] = tr('Package %0 is not enabled', $packageName);
			return false;
		}

		$extension = self::get($packageName);
		list($success, $log) = $extension->disable($rollback);
		self::$messages = array_merge(self::$messages, $log);

		if ($success) {
			$enabledPackages = self::getEnabledPackageExtensions(false);
			unset($enabledPackages[$packageName]);

			file_put_contents(self::ENABLED_PACKAGES_FILE, Yaml::dump($enabledPackages));

			self::$enabled = $enabledPackages; // Update list of enabled packages

			// Force container refresh
			@unlink(TIKI_PATH . '/temp/cache/container.php');

			\TikiLib::lib('cache')->invalidate('global_preferences');
			\TikiLib::lib('cache')->invalidate('tiki_default_preferences_cache');
		}

		return $success;
	}

	/**
	 * Handler to refesh and load new translations included in pacakge
	 *
	 * @param string $path Path to lookup existing languages to trigger cache refresh
	 */
	protected static function refreshLanguages($path)
	{
		$finder = new Finder();
		$files = $finder->in($path)->name('*.php')->notContains('index.php');
		$languageLib = \TikiLib::lib('language');

		foreach ($files as $file) {
			$lg = pathinfo($file, PATHINFO_FILENAME);

			if (! $languageLib->is_valid_language($lg)) {
				continue;
			}

			$languageLib::loadExtensions($lg, false); // Force refresh lang from enabled extensions
		}
	}

	/**
	 * Return the messages that occurred while managing extension packages
	 *
	 * @return array
	 */
	public static function getMessages()
	{
		$messages = self::$messages;
		self::$messages = [];

		return $messages;
	}

	public static function refresh()
	{
		self::$installed = [];
		self::$paths = [];
		$addOns = self::getEnabledPackageExtensions();

		foreach ($addOns as $addOn) {
			try {
				$package = $addOn['name'];
				self::$installed[$package] = json_decode(json_encode($addOn['config']));
				self::$paths[$package] = $addOn['path'];
				self::initializeEventsApi($package);
				self::initializeSearchApi($package);
			} catch (InvalidArgumentException $e) {
				// Do nothing, absence of tiki-package.json
			}
		}
	}

	private static function initializeEventsApi($package)
	{
		if (! empty(self::$installed[$package]->eventmap)) {
			$eventMap = self::$installed[$package]->eventmap;
			ApiEvents::setEventMap($package, $eventMap);
		}
	}

	private static function initializeSearchApi($package)
	{
		if (! empty(self::$installed[$package]->search->searchsources)) {
			$sources = self::$installed[$package]->search->searchsources;
			ApiSearch::setSources($package, $sources);
		}
	}

	public static function get($name)
	{
		if (isset(self::$extensions[$name])) {
			return self::$extensions[$name];
		}

		$enabledExtensions = self::getEnabledPackageExtensions();
		if (! array_key_exists($name, $enabledExtensions)) {
			return null;
		}

		return Extension::createFromConfiguration($enabledExtensions[$name]['config']);
	}

	public static function getFolder($folder)
	{
		if (strpos($folder, '/') !== false && strpos($folder, '_') === false) {
			$package = $folder;
		} else {
			$package = str_replace('_', '/', $folder);
		}
		return self::get($package);
	}

	public static function getInstalled()
	{
		return self::$installed;
	}

	public static function getPaths()
	{
		return self::$paths;
	}

	/**
	 * Search for a package by package name (first in vendor and then in vendor_custom)
	 *
	 * @param $packageName
	 * @return bool|string
	 */
	public static function locatePackage($packageName)
	{
		if ($path = self::locateVendorPackage($packageName)) {
			return $path;
		}

		$path = self::locateVendorCustomPackage($packageName);

		return $path;
	}

	/**
	 * Search for a package by name in teh vendor folder
	 *
	 * @param $packageName
	 * @return bool|string
	 */
	protected static function locateVendorPackage($packageName)
	{
		if (is_dir('vendor/' . $packageName)) {
			return 'vendor/' . $packageName;
		}

		return false;
	}

	/**
	 * Search for a package by package name in the vendor_custom folder
	 *
	 * @param $packageName
	 * @param string $searchFolder
	 * @return bool|string
	 */
	protected static function locateVendorCustomPackage($packageName, $searchFolder = 'vendor_custom')
	{
		$directories = new DirectoryIterator($searchFolder);
		foreach ($directories as $directory) {
			if (! $directory->isDir() || $directory->isDot()) {
				continue;
			}

			if (! file_exists($directory->getPathname() . '/composer.json')) {
				if ($path = self::locateVendorCustomPackage($packageName, $directory->getPathname())) {
					return $path;
				}
			}

			$composerContent = json_decode(file_get_contents($directory->getPathname() . '/composer.json'));

			if (! empty($composerContent->name) && $packageName === $composerContent->name) {
				return $directory->getPathname();
			}
		}

		return false;
	}
}
