<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Theme;

use Exception;
use Installer;
use Symfony\Component\Yaml\Yaml;
use Tiki\Theme\Menu as ThemeMenu;
use Tiki\Theme\Module as ThemeModule;
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
class ThemeInstaller
{
	const TEMPORARY_FOLDER_NAME = 'temp/';
	const CONFIG_FOLDER_NAME = 'db/';
	const SCHEMA_FOLDER_NAME = 'installer/schema/';

	protected $fileName = '';

	protected $sourceFolder = '';
	protected $schemas = [];
	protected $profiles = [];
	protected $configFiles = [];
	protected $existCssFolder = false;
	protected $themeName = '';
	/**
	 * @var string
	 */
	private $tikiFolder;
	private $messages = [];

	/**
	 * ThemeFolder constructor.
	 * @param string $sourceFolder
	 * @param string $tikiFolder
	 */
	public function __construct($sourceFolder, $tikiFolder)
	{
		$this->sourceFolder = $sourceFolder;
		$this->tikiFolder = $tikiFolder;
	}


	/**
	 * Check zip folder structure and get files names
	 *
	 * @param ZipArchive $zip
	 * @return false|null
	 */
	public function getInfo()
	{
		$directory = new \RecursiveDirectoryIterator($this->sourceFolder);
		$iterator = new \RecursiveIteratorIterator($directory);
		foreach ($iterator as $info) {
			$fileName = $info->getPathname();
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
			$updateProcess->setWorkingDirectory($this->tikiFolder);
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
	 * Copy files to theme folder
	 *
	 * @return null
	 */
	public function copyThemeFiles()
	{

		$destThemeFolder = $this->getDestinationThemeFolder();
		$tmpThemeFolder = $this->getSourceThemeFolder();
		$fs = new Filesystem();
		$fs->mirror($tmpThemeFolder, $destThemeFolder);
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
		return empty($this->fileName) ? $this->themeName : $this->fileName;
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
	 * Get temp theme folder
	 *
	 * @return string
	 */
	public function getSourceThemeFolder()
	{
		return $this->composePath($this->sourceFolder, 'themes', $this->themeName);
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
		return $this->composePath($this->sourceFolder, $this->getFileName(), 'themes', self::SCHEMA_FOLDER_NAME);
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
		return $this->composePath($this->tikiFolder, 'themes', $installThemeName);
	}

	/**
	 * Get temp config folder
	 *
	 * @return string
	 */
	public function getDestinationConfigFolder()
	{
		return $this->composePath($this->tikiFolder, self::CONFIG_FOLDER_NAME);
	}

	/**
	 * Get temp profiles folder
	 *
	 * @return string
	 */
	public function getDestinationSchemaFolder()
	{
		return $this->composePath($this->tikiFolder, self::SCHEMA_FOLDER_NAME);
	}

	/**
	 *Install theme
	 */
	public function install()
	{
		$themeHandler = new ThemeHandler();
		$this->getInfo();
		$themeName = $this->getThemeName();
		$camelCaseThemeName = $themeHandler->getNameCamelCase($themeName);
		if ($themeHandler->themeExists($camelCaseThemeName)) {
			throw new Exception('<error>' . tr('Theme already installed') . '</error>');
		}

		if (! $this->getExistCssFolder()) {
			throw new Exception('<error>' . tr('CSS folder not found') . '</error>');
		}

		// Execute database update
		$schemasUpdate = $this->databaseUpdate();
		if (! empty($schemasUpdate)) {
			$this->messages[] = '<info>' . $schemasUpdate . '</info>';
		}

		$tmpThemeFolder = $this->getSourceThemeFolder();
		$tmpThemeFiles = $themeHandler->getAllFolderFiles($tmpThemeFolder . '/*');
		$themeHandler->convertFilesNames($tmpThemeFiles, $themeName, $camelCaseThemeName);

		// Apply config files
		$configApplied = $this->applyConfig();
		if (! empty($configApplied)) {
			foreach ($configApplied as $config) {
				$this->messages[] = '<info>' . tr('Configuration file added:') . ' ' . $config . '</info>';
			}
		}

		// Insert/Update preferences, menus and modules
		$profiles = $this->getProfiles();
		if (! empty($profiles)) {
			$menu = new ThemeMenu();
			$module = new ThemeModule();
			$installer = Installer::getInstance();
			$preferences = $installer->table('tiki_preferences');
			$profilesPath = $this->getSourceProfilesFolder();
			foreach ($profiles as $yamlFile) {
				$yamlFile = $profilesPath . $yamlFile;
				if (file_exists($yamlFile)) {
					$yamlParse = Yaml::parse(file_get_contents($yamlFile));
					// Add preferences
					if (! empty($yamlParse['preferences'])) {
						foreach ($yamlParse['preferences'] as $preference => $value) {
							$preferences->insertOrUpdate(['value' => $value], ['name' => $preference]);
							$this->messages[] = '<info>' . tr('Preference inserted or updated:') . ' ' . $preference . '=' . $value . '</info>';
						}
					}
					// Check for menus and modules
					if (! empty($yamlParse['objects'])) {
						foreach ($yamlParse['objects'] as $ObjectData) {
							// Add menus
							if (! empty($ObjectData['type']) && $ObjectData['type'] == 'menu' && ! empty($ObjectData['data'])) {
								$menuName = $menu->addOrUpdate($ObjectData['data']);
								if (! empty($menuName)) {
									$this->messages[] = '<info>' . tr('Menu inserted or updated:') . ' "' . $menuName . '"</info>';
								}
							}
							// Add modules
							if (! empty($ObjectData['type']) && $ObjectData['type'] == 'module' && ! empty($ObjectData['data'])) {
								$moduleName = $module->addOrUpdate($ObjectData['data']);
								if (! empty($moduleName)) {
									$this->messages[] = '<info>' . tr('Module inserted or updated:') . ' ' . $moduleName . '</info>';
								}
							}
						}
					}
				}
			}
		}
		$this->copyThemeFiles();
		// Rename files to camelcase names
		$themeHandler = new ThemeHandler;
		$tmpThemeFolder = $this->getDestinationThemeFolder();
		$tmpThemeFiles = $themeHandler->getAllFolderFiles($tmpThemeFolder . '/*');
		$themeHandler->convertFilesNames($tmpThemeFiles, $this->themeName, $camelCaseThemeName);

		return true;
	}

	/**
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}
}
