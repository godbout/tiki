<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Tiki\Theme\Handler as ThemeHandler;
use Tiki\Theme\Menu as ThemeMenu;
use Tiki\Theme\Module as ThemeModule;
use Tiki\Theme\Zip as ThemeZip;
use ZipArchive;
use Exception;
use Installer;

/**
 * Update theme deploying via a theme package
 */
class ThemeUpdateCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('theme:update')
			->setDescription('Update a theme')
			->addArgument(
				'file',
				InputArgument::REQUIRED,
				'Zip file'
			);
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $tikipath;
		$tikiRootFolder = ! empty($tikipath) ? $tikipath : dirname(dirname(dirname(dirname(__DIR__))));

		$file = $input->getArgument('file');
		if (! file_exists($file)) {
			$output->writeln('<error>' . tr('File not found') . '</error>');
			return;
		}

		$themeZip = new ThemeZip();
		$isZipFile = $themeZip->isZipFile($file);
		if (! $isZipFile) {
			$output->writeln('<error>' . tr('File is not a .zip file.') . '</error>');
			return;
		}

		try {
			$themeZip->setCurrentFolder($tikiRootFolder);
			$zip = new ZipArchive();
			if ($zip->open($file) === true) {
				$themeHandler = new ThemeHandler();
				$themeZip->getInfo($zip);
				$zipThemeName = $themeZip->getThemeName();
				$camelCaseThemeName = $themeHandler->getNameCamelCase($zipThemeName);

				if (! $themeHandler->themeExists($camelCaseThemeName)) {
					$output->writeln('<error>' . tr('Theme not found') . '</error>');
					return;
				}
				if (! $themeZip->getExistCssFolder()) {
					$output->writeln('<error>' . tr('CSS folder not found') . '</error>');
					return;
				}
				$zip->extractTo($themeZip->getTemporaryFolder());
				$zip->close();

				// Execute database update
				$schemasUpdate = $themeZip->databaseUpdate();
				if (! empty($schemasUpdate)) {
					$output->writeln('<info>' . $schemasUpdate . '</info>');
				}

				// Rename files to camelcase names
				$themeHandler = new ThemeHandler;
				$tmpThemeFolder = $themeZip->getSourceThemeFolder();
				$tmpThemeFiles = $themeHandler->getAllFolderFiles($tmpThemeFolder . '/*');
				$themeHandler->convertFilesNames($tmpThemeFiles, $zipThemeName, $camelCaseThemeName);

				// Apply config files
				$configApplied = $themeZip->applyConfig();
				if (! empty($configApplied)) {
					foreach ($configApplied as $config) {
						$output->writeln('<info>' . tr('Configuration file added:') . ' ' . $config . '</info>');
					}
				}

				// Insert/Update preferences, menus and modules
				$profiles = $themeZip->getProfiles();
				if (! empty($profiles)) {
					$menu = new ThemeMenu();
					$module = new ThemeModule();
					$installer = Installer::getInstance();
					$preferences = $installer->table('tiki_preferences');
					$profilesPath = $themeZip->getSourceProfilesFolder();
					foreach ($profiles as $yamlFile) {
						$yamlFile = $profilesPath . $yamlFile;
						if (file_exists($yamlFile)) {
							$yamlParse = Yaml::parse(file_get_contents($yamlFile));
							// Add preferences
							if (! empty($yamlParse['preferences'])) {
								foreach ($yamlParse['preferences'] as $preference => $value) {
									$preferences->insertOrUpdate(['value' => $value], ['name' => $preference]);
									$output->writeln('<info>' . tr('Preference inserted or updated:') . ' ' . $preference . '=' . $value . '</info>');
								}
							}
							// Check for menus and modules
							if (! empty($yamlParse['objects'])) {
								foreach ($yamlParse['objects'] as $ObjectData) {
									// Add menus
									if (! empty($ObjectData['type']) && $ObjectData['type'] == 'menu' && ! empty($ObjectData['data'])) {
										$menuName = $menu->addOrUpdate($ObjectData['data']);
										if (! empty($menuName)) {
											$output->writeln('<info>' . tr('Menu inserted or update:') . ' "' . $menuName . '"</info>');
										}
									}
									// Add modules
									if (! empty($ObjectData['type']) && $ObjectData['type'] == 'module' && ! empty($ObjectData['data'])) {
										$moduleName = $module->addOrUpdate($ObjectData['data']);
										if (! empty($moduleName)) {
											$output->writeln('<info>' . tr('Module inserted or update:') . ' ' . $moduleName . '</info>');
										}
									}
								}
							}
						}
					}
				}
				$themeZip->copyThemeFiles();
				$output->writeln('<info>' . tr('Theme updated:') . ' ' . $camelCaseThemeName . '</info>');
			}
		} catch (Exception $ex) {
			$output->writeln('<error>' . tr('Could not open file') . '</error>');
			return;
		}
	}
}
