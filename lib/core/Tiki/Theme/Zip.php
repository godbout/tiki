<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Theme;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tiki\Theme\Handler as ThemeHandler;
use Symfony\Component\Filesystem\Filesystem as Filesystem;
use ZipArchive;

/**
 * Class that handles tiki theme zip operations
 *
 * @access public
 */
class Zip
{
	const TEMPORARY_FOLDER_NAME = 'temp/';
	const CONFIG_FOLDER_NAME = 'db/';
	const SCHEMA_FOLDER_NAME = 'installer/schema/';

	protected $fileName = '';

	protected $currentFolder = '';
	protected $schemas = [];
	protected $profiles = [];
	protected $configFiles = [];
	protected $existCssFolder = false;
	protected $themeName = '';

	protected $uniqueHash;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->uniqueHash = 'ThemeZipTmp_' . uniqid('', true) . rand(0, PHP_INT_MAX);
	}

	/**
	 * Check if is a zip file
	 *
	 * @param string $file
	 * @return bool
	 */
	public function isZipFile($file)
	{
		if (empty($file)) {
			return false;
		}
		$fileNamePieces = explode(".", $file);
		$fileCountPieces = count($fileNamePieces);
		$fileName = basename($fileNamePieces[$fileCountPieces - 2]);
		if (! empty($fileName)) {
			$this->setFileName($fileName);
		}
		$fileExtension = strtolower($fileNamePieces[$fileCountPieces - 1]);
		$isZipFile = $fileExtension === 'zip' ? true : false;
		return $isZipFile;
	}

	/**
	 * Check zip folder structure and get files names
	 *
	 * @param ZipArchive $zip
	 * @return false|null
	 */
	public function getInfo(ZipArchive $zip)
	{
		if (empty($zip)) {
			return false;
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$fileName = $zip->getNameIndex($i);
			$fileInfo = pathinfo($fileName);
			$fileExtension = ! empty($fileInfo['extension']) ? $fileInfo['extension'] : '';
			if ($fileExtension == 'sql' && strpos($fileInfo['dirname'], 'installer/schema') !== false) {
				$this->schemas[] = $fileInfo['basename'];
			}
			if ($fileExtension == 'yaml' && strpos($fileInfo['dirname'], '/profiles') !== false) {
				$this->profiles[] = $fileInfo['basename'];
			}
			if ($fileExtension == 'ini' && strpos($fileInfo['dirname'], '/config') !== false) {
				$this->configFiles[] = $fileInfo['basename'];
			}
			if ($fileExtension == 'css' && strpos($fileInfo['dirname'], '/css') !== false) {
				$this->existCssFolder = true;
				$themeName = $this->getThemeName();
				if (empty($themeName) && preg_match('/themes\/([^\/]*)/', dirname($fileInfo['dirname']), $matches)) {
					$this->setThemeName($matches[1]);
				}
			}
		}
	}

	/**
	 * Update database using schema files
	 *
	 * @return false|string
	 */
	public function databaseUpdate()
	{
		$schemasFiles = $this->schemas;
		if (! empty($schemasFiles)) {
			$sourceFolder = $this->getSourceSchemaFolder();
			$destFolder = $this->getDestinationSchemaFolder();
			if (! file_exists($destFolder)) {
				return false;
			}
			foreach ($schemasFiles as $schema) {
				$sourceSchema = $sourceFolder . $schema;
				$destSchema = $destFolder . $schema;
				if (file_exists($sourceSchema)) {
					copy($sourceSchema, $destSchema);
				}
			}
			$phpFinder = new PhpExecutableFinder();
			$phpPath = $phpFinder->find();

			$updateProcess = new Process([$phpPath, 'console.php', 'database:update']);
			$updateProcess->setEnv(['HTTP_ACCEPT_ENCODING', '']);
			$updateProcess->setWorkingDirectory($this->getCurrentFolder());
			$updateProcess->run();
			$updateProcess->wait();

			return $updateProcess->getOutput();
		}
		return false;
	}

	/**
	 * Copy configuration files to config folder
	 *
	 * @return array
	 */
	public function applyConfig()
	{
		$sourceConfigFolder = $this->getSourceConfigFolder();
		$destinationConfigFolder = $this->getDestinationConfigFolder();

		$configFiles = $this->configFiles;
		$configApplied = [];
		if (! empty($configFiles)) {
			foreach ($configFiles as $file) {
				$sourceFile = $this->composePath($sourceConfigFolder, $file);
				$destFile = $this->composePath($destinationConfigFolder, $file);
				if (file_exists($sourceFile)) {
					copy($sourceFile, $destFile);
					$configApplied[] = $file;
				}
			}
		}
		return $configApplied;
	}

	/**
	 * Remove folders
	 *
	 * @param array $folders
	 * @return null
	 */
	protected function removeFolders($folders)
	{
		if (! empty($folders)) {
			$fs = new Filesystem();
			foreach ($folders as $folder) {
				$fs->remove($folder);
			}
		}
	}

	/**
	 * Copy files to theme folder
	 *
	 * @return null
	 */
	public function copyThemeFiles()
	{
		$remove = [
			$this->getSourceConfigFolder(),
			$this->getSourceProfilesFolder(),
		];
		$this->removeFolders($remove);

		$destThemeFolder = $this->getDestinationThemeFolder();
		$tmpThemeFolder = $this->getSourceThemeFolder();
		$fs = new Filesystem();
		$fs->mirror($tmpThemeFolder, $destThemeFolder);

		$this->removeFolders([$this->getTemporaryFolder()]);
	}

	/**
	 * Get zip theme name
	 *
	 * @return string
	 */
	public function getThemeName()
	{
		return $this->themeName;
	}

	/**
	 * Set zip theme name
	 *
	 * @param string $themeName
	 * @return string
	 */
	public function setThemeName($themeName)
	{
		$this->themeName = $themeName;
	}

	/**
	 * Get zip file name
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * Set zip file name
	 *
	 * @param string $fileName
	 * @return string
	 */
	public function setFileName($fileName)
	{
		$this->fileName = $fileName;
	}

	/**
	 * Set css folder exists
	 *
	 * @param bool $existCssFolder
	 * @return bool
	 */
	public function setExistCssFolder($existCssFolder)
	{
		$this->existCssFolder = $existCssFolder;
	}

	/**
	 * Get if css folder exists
	 *
	 * @return bool
	 */
	public function getExistCssFolder()
	{
		return $this->existCssFolder;
	}

	/**
	 * Get the list of profiles extracted
	 *
	 * @return array The list of profiles collected
	 */
	public function getProfiles()
	{
		return $this->profiles;
	}

	/**
	 * Get current folder
	 *
	 * @return string
	 */
	public function getCurrentFolder()
	{
		return $this->currentFolder;
	}

	/**
	 * Set current folder
	 *
	 * @param string $folder
	 * @return string
	 */
	public function setCurrentFolder($folder)
	{
		$this->currentFolder = rtrim(trim($folder), '\/');
	}

	/**
	 * Glue all the path components with the appropriated directory separator
	 *
	 * @param string $path list of arguments with the path components
	 * @return string
	 */
	protected function composePath($path = '')
	{
		$parts = func_get_args();
		$parts[0] = rtrim(trim($parts[0]), '\/');

		return implode(DIRECTORY_SEPARATOR, $parts);
	}

	/**
	 * Return the path for the temporary folder to be used
	 *
	 * @return string
	 */
	public function getTemporaryFolder()
	{
		$temporaryFolder = $this->composePath($this->currentFolder, $this->uniqueHash);
		if (! file_exists($temporaryFolder)) {
			mkdir($temporaryFolder);
		}
		return $temporaryFolder;
	}

	/**
	 * Get temp theme folder
	 *
	 * @return string
	 */
	public function getSourceThemeFolder()
	{
		return $this->composePath($this->getTemporaryFolder(), $this->fileName, 'themes', $this->themeName);
	}

	/**
	 * Get temp config folder
	 *
	 * @return string
	 */
	public function getSourceConfigFolder()
	{
		return $this->composePath($this->getSourceThemeFolder(), 'config') . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get temp profiles folder
	 *
	 * @return string
	 */
	public function getSourceProfilesFolder()
	{
		return $this->composePath($this->getSourceThemeFolder(), 'profiles') . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get temp profiles folder
	 *
	 * @return string
	 */
	public function getSourceSchemaFolder()
	{
		return $this->composePath($this->getTemporaryFolder(), $this->fileName, 'themes', self::SCHEMA_FOLDER_NAME);
	}

	/**
	 * Get theme folder destination
	 *
	 * @return string
	 */
	public function getDestinationThemeFolder()
	{
		$themeHandler = new ThemeHandler();
		$installThemeName = $themeHandler->getNameCamelCase($this->getThemeName());
		return $this->composePath($this->getCurrentFolder(), 'themes', $installThemeName);
	}

	/**
	 * Get temp config folder
	 *
	 * @return string
	 */
	public function getDestinationConfigFolder()
	{
		return $this->composePath($this->getCurrentFolder(), self::CONFIG_FOLDER_NAME);
	}

	/**
	 * Get temp profiles folder
	 *
	 * @return string
	 */
	public function getDestinationSchemaFolder()
	{
		return $this->composePath($this->getCurrentFolder(), self::SCHEMA_FOLDER_NAME);
	}

	/**
	 * Remove all temp folders related with this zip
	 *
	 * @return void
	 */
	public function clean()
	{
		$this->removeFolders(
			[
				$this->getTemporaryFolder(),
				$this->getSourceConfigFolder(),
				$this->getSourceProfilesFolder()
			]
		);
	}
}
