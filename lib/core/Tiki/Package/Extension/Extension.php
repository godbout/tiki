<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package\Extension;

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder;
use Tiki\Command\ConsoleApplicationBuilder;
use Tiki\Theme\ThemeInstaller;
use Tiki_Profile;
use TikiLib;

class Extension
{
	private $configuration = null;
	public $smarty = null;
	private $utilities;
	private $path;

	public static function createFromPackageInDisk($packagePath)
	{
		$extensionPackage = new self();

		$composerJson = json_decode(file_get_contents($packagePath . '/composer.json'), true);

		$packageJson = json_decode(file_get_contents($packagePath . '/tiki-package.json'), true);

		$packageJson['package'] = $composerJson['name'] ?? '';
		$packageJson['name'] = $composerJson['description'] ?? '';
		$packageJson['version'] = $composerJson['version'] ?? '';
		$packageJson['url'] = $composerJson['homepage'] ?? '';

		$packageJson['path'] = $packagePath;

		$extensionPackage->setPath($packagePath);
		$extensionPackage->setConfiguration($packageJson);

		return $extensionPackage;
	}

	public static function createFromConfiguration($configuration)
	{
		$extensionPackage = new self();

		$extensionPackage->setPath($configuration['path']);
		$extensionPackage->setConfiguration($configuration);

		return $extensionPackage;
	}

	public function exportConfiguration()
	{
		return $this->configuration;
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}

	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->configuration['name'];
	}

	/**
	 * @return mixed
	 */
	public function getPackage()
	{
		return $this->configuration['package'];
	}

	/**
	 * @return mixed
	 */
	public function getVersion()
	{
		return $this->configuration['version'];
	}

	/**
	 * @return mixed
	 */
	public function getURL()
	{
		return $this->configuration['url'];
	}

	/**
	 * @return mixed
	 */
	public function getFolder()
	{
		return str_replace('/', '_', $this->configuration['package']);
	}

	/**
	 * @return mixed
	 */
	public function getBaseNamespace()
	{
		return $this->configuration['namespace'] ?? '';
	}

	/**
	 * @return mixed
	 */
	public function getVendor()
	{
		$parts = explode('/', $this->getPackage());
		return $parts[0];
	}

	/**
	 * @return mixed
	 */
	public function getShortName()
	{
		$parts = explode('/', $this->getPackage());
		return $parts[1];
	}

	/**
	 * @return array
	 */
	public function getDepends()
	{
		if (is_array($this->configuration['depends'])) {
			return $this->configuration['depends'];
		} else {
			return [];
		}
	}

	/**
	 * @param bool $update
	 * @return array
	 */
	public function enable($update = false)
	{
		$success = true;
		$log = [];
		if (file_exists($this->path . '/themes')) {
			if ($update) {
				list($success, $themesOutput) = $this->uninstallThemes();
				$log = array_merge($log, $themesOutput);
			}

			list($success, $themesOutput) = $this->installThemes();
			$log = array_merge($log, $themesOutput);
		}

		$profilesOutput = $this->installProfiles();
		$log = array_merge($log, $profilesOutput);

		return [
			$success,
			$log,
		];
	}

	/**
	 * @param bool $revert
	 * @return array
	 * @throws Exception
	 */
	public function disable($revert = false)
	{
		$success = true;
		$log = [];
		if (file_exists($this->path . '/themes')) {
			list($success, $themesOutput) = $this->uninstallThemes();
			$log = array_merge($log, $themesOutput);
		}

		$profilesOutput = $this->uninstallProfiles($revert);
		$log = array_merge($log, $profilesOutput);

		return [
			$success,
			$log
		];
	}

	/**
	 * Install themes locates in themes folder
	 *
	 * @return array
	 */
	protected function installThemes()
	{
		$successful = true;
		$log = [];
		try {
			$folder = $this->path;

			if (! file_exists($folder) || ! is_dir($folder)) {
				throw new Exception('<error>' . "Folder: $folder does not exists." . '</error>');
			}

			$themeInstaller = new ThemeInstaller($folder, TIKI_PATH);
			$themeInstaller->install();
			$log[] = 'Themes installed';
		} catch (Exception $ex) {
			$successful = false;
			$log[] = $ex->getMessage();
		}

		return [$successful, $log];
	}

	/**
	 * Remove package themes from Tiki
	 *
	 * @return array
	 */
	protected function uninstallThemes()
	{
		$log = [];
		$finder = new Finder();

		$path = $this->path . '/themes';
		$commandName = 'theme:remove';

		$successful = false;
		foreach ($finder->in($path)->depth('== 0')->directories() as $themeDir) {
			try {
				$consoleBuilder = new ConsoleApplicationBuilder(
					isset($_SERVER['TIKI_VIRTUAL']) ? $_SERVER['TIKI_VIRTUAL'] : ''
				);
				$console = $consoleBuilder->create(true);
				$command = $console->find($commandName);

				$themeName = basename($themeDir);

				$input = new ArgvInput(['console.php', $commandName, $themeName]);
				$input->setInteractive(false);

				$output = new BufferedOutput();
				$statusCode = $command->run($input, $output);

				$successful = $statusCode === 0;
				$log[] = $output->fetch();
			} catch (Exception $e) {
				$successful = false;
				$log = $e->getMessage();
			}
		}

		return [$successful, $log];
	}

	/**
	 * Install profiles listed in configuration
	 *
	 * @return array
	 */
	protected function installProfiles()
	{
		global $tikilib;

		if (! isset($this->configuration['profiles'])) {
			return [];
		}

		$log = [];

		$transaction = $tikilib->begin();
		foreach ($this->configuration['profiles'] as $profileName) {
			$profile = Tiki_Profile::fromFile($this->path . '/profiles', $profileName);

			if (empty($profile)) {
				$log[] = tr(
					'Warning: Unable to load profile. Profile %0 not found in profiles folder. Skipping...',
					$profileName
				);
				continue;
			}

			$installer = new \Tiki_Profile_Installer();
			if ($installer->isInstalled($profile)) {
				$log[] = tr('Warning: Profile %0 already installed. Skipping...', $profileName);
				continue;
			}

			if ($result = $installer->install($profile)) {
				$logChanges = $installer->getTrackProfileChanges();
				$domain = 'file://'.$this->path . '/profiles';
				$logChanges['domain'] = $domain;
				TikiLib::lib('logs')->add_action('profile apply', $profileName, 'system', tr('profile applied'), '', '', '', '', '', '', $logChanges);
				$log[] = tr('Profile %0 applied', $profileName);
			} else {
				$installLog = $installer->getFeedback();
				$log[] = implode(PHP_EOL, $installLog);
			}
		}

		$transaction->commit();

		return $log;
	}

	/**
	 * Uninstall profiles listed in configuration
	 *
	 * @param bool $revert
	 * @return array
	 * @throws Exception
	 */
	protected function uninstallProfiles($revert = false)
	{
		global $tikilib;

		if (! isset($this->configuration['profiles'])) {
			return [];
		}

		$log = [];

		$transaction = $tikilib->begin();

		foreach ($this->configuration['profiles'] as $profileName) {
			$profile = Tiki_Profile::fromFile($this->path . '/profiles', $profileName);

			if (empty($profile)) {
				$log[] = tr(
					'Warning: Unable to load profile. Profile %0 not found in profiles folder. Skipping...',
					$profileName
				);
				continue;
			}

			$installer = new \Tiki_Profile_Installer();
			if (! $installer->isInstalled($profile)) {
				$log[] = tr('Warning: Profile %0 is not installed. Skipping...', $profileName);
				continue;
			}

			if ($revert) {
				$query = "SELECT * FROM tiki_actionlog where action = 'profile apply' and object=? ORDER BY actionId DESC LIMIT 1";
				$result = \TikiLib::lib('logs')->query($query, [$profileName]);
				if ($logResult = $result->fetchRow()) {
					$revertInfo = unserialize($logResult['log']);
					if (! isset($revertInfo['reverted'])) {
						\TikiLib::lib('logs')->revert_action(
							$logResult['actionId'],
							$logResult['object'],
							'profiles',
							$revertInfo
						);
						$installer->revert($profile, $revertInfo);
					}
				} else {
					$log[] = 'No changes were found in logs to revert.';
				}
			}

			$installer->forget($profile);
			$log[] = tr('Profile %0 removed', $profileName);
		}

		$transaction->commit();

		return $log;
	}

	/**
	 * Check if there is a new package version to be installed
	 *
	 * @return bool
	 */
	public function hasUpdate()
	{
		$composerJson = json_decode(file_get_contents($this->path . '/composer.json'), true);

		$packageVersion = $composerJson['version'];
		$versionInstalled = $this->configuration['version'];

		return version_compare($packageVersion, $versionInstalled) == 1;
	}
}
