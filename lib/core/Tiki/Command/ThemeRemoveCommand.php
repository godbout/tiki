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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Tiki\Theme\Handler as ThemeHandler;
use TikiLib;

/**
 * Command to remove themes
 */
class ThemeRemoveCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('theme:remove')
			->setDescription('Remove a theme')
			->addArgument(
				'theme',
				InputArgument::REQUIRED,
				'Theme name'
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

		$themeName = $input->getArgument('theme');

		$themeHandler = new ThemeHandler();
		$themeName = $themeHandler->getNameCamelCase($themeName);

		$themelib = TikiLib::lib('theme');
		$listThemes = $themelib->get_themes();
		if (! in_array($themeName, $listThemes)) {
			$output->writeln('<error>' . tr('Theme not found') . '</error>');
			return;
		}

		$currentFolder = $tikiRootFolder;
		$fullThemePath = $currentFolder . DIRECTORY_SEPARATOR . $themelib->get_theme_path($themeName);
		try {
			$fs = new Filesystem();
			$fs->remove($fullThemePath);
			$output->writeln('<info>' . tr('Theme removed successfully') . '</info>');
		} catch (IOExceptionInterface $e) {
			$output->writeln('<error>' . tr('An error occurred while deleting theme') . $themeName . '</error>');
		}
	}
}
