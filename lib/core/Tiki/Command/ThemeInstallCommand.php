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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Tiki\Theme\Handler as ThemeHandler;
use Tiki\Theme\Menu as ThemeMenu;
use Tiki\Theme\Module as ThemeModule;
use Tiki\Theme\ThemeInstaller;
use Tiki\Theme\Zip as ThemeZip;
use ZipArchive;
use Exception;
use Installer;

/**
 * Install theme deploying via a theme package
 */
class ThemeInstallCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('theme:install')
			->setDescription('Install a new theme')
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
		$path_parts = pathinfo($file);
		if (! $isZipFile) {
			$output->writeln('<error>' . tr('File is not a .zip file.') . '</error>');
			return;
		}
		$uniqueHash = 'ThemeZipTmp_' . uniqid('', true) . rand(0, PHP_INT_MAX);
		$sourceFolder = $tikiRootFolder . '/temp/' . $uniqueHash ;
		try {
			$zip = new ZipArchive;
			$zip->open($file);
			$zip->extractTo($sourceFolder);
			$zip->close();
			global $tikipath;
			$tikiRootFolder = ! empty($tikipath) ? $tikipath : dirname(dirname(dirname(dirname(__DIR__))));


			$themeInstaller = new ThemeInstaller($sourceFolder . "/" . $path_parts['filename'], $tikiRootFolder);
			$themeInstaller->install();

			foreach ($themeInstaller->getMessages() as $message) {
				$output->writeln($message);
			}
			$output->writeln('<info>' . tr('Theme installed:') . ' ' . $themeInstaller->getThemeName() . '</info>');
		} catch (Exception $ex) {
			$output->writeln($ex->getMessage());
			return;
		} finally {
			$this->removeTemp($sourceFolder);
		}
	}

	public function removeTemp($folder)
	{
		$fs = new Filesystem();
		$fs->remove($folder);
	}
}
