<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ComposerManager;
use Tiki\Package\PackageCommandHelper;

class PackageListCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('package:list')
			->setDescription('List installed/available to install packages')
			->setHelp('This command allows you to list installed and available to install packages.');
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $tikipath;
		$composerManager = new ComposerManager($tikipath);

		if ($composerManager->composerIsAvailable()) {
			$io = new SymfonyStyle($input, $output);
			$io->newLine();

			$installedComposerPackages = $composerManager->getInstalled();
			if ($installedComposerPackages === false) {
				$output->writeln('<comment>' . tr('No packages found in composer.json in the root of the project.') . '</comment>');
			} else {
				if (empty($installedComposerPackages)) {
					$output->writeln('<comment>' . tr('No packages installed.') . '</comment>');
				} else {
					$installedPackagesInfo = PackageCommandHelper::getInstalledPackagesInfo($installedComposerPackages);
					$output->writeln(tr('Packages Installed'));
					PackageCommandHelper::renderInstalledPackagesTable($output, $installedPackagesInfo);
				}
			}

			$io->newLine();

			$availableComposerPackages = $composerManager->getAvailable(true, true);
			if (! empty($availableComposerPackages)) {
				$availablePackagesInfo = PackageCommandHelper::getAvailablePackagesInfo($availableComposerPackages);
				$output->writeln(tr('Packages Available to Install'));
				PackageCommandHelper::renderAvailablePackagesTable($output, $availablePackagesInfo);
			} else {
				$output->writeln('<comment>' . tr('No packages available to be installed.') . '</comment>');
			}
		} else {
			$output->writeln('<error>' . tr('Composer could not be executed.') . '</error>');
			return;
		}
	}
}
