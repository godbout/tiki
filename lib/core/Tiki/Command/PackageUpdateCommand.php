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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ComposerManager;
use Tiki\Package\PackageCommandHelper;

class PackageUpdateCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('package:update')
			->setDescription('Update package')
			->setHelp('This command allows you to update packages.')
			->addArgument(
				'package',
				InputArgument::OPTIONAL,
				'Package ID'
			);
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
			$installedComposerPackages = $composerManager->getInstalled();

			if ($installedComposerPackages === false) {
				$output->writeln('<comment>' . tr('No packages found in composer.json in the root of the project.') . '</comment>');
			} else {
				$updatablePackages = PackageCommandHelper::getUpdatablePackages($installedComposerPackages);
				if (empty($installedComposerPackages) || empty($updatablePackages)) {
					$output->writeln('<comment>' . tr('No packages available to be updated.') . '</comment>');
				} else {
					$io = new SymfonyStyle($input, $output);
					$packageKey = $input->getArgument('package');
					if (isset($packageKey) && ! empty($packageKey)) {
						if (in_array($packageKey, array_column($updatablePackages, 'key'))) {
							$output->writeln('<info>' . tr('Updating package: ') . $packageKey . '</info>');
							$result = $composerManager->updatePackage($packageKey);
							$io->newLine();
							$output->writeln($result);
						} else {
							$output->writeln('<error>' . tr('Invalid Package: ') . $packageKey . '</error>');
							return;
						}
					} else {
						$io->newLine();

						$updatablePackagesInfo = PackageCommandHelper::getInstalledPackagesInfo($updatablePackages);

						$output->writeln(tr('Packages that can be updated'));
						PackageCommandHelper::renderInstalledPackagesTable($output, $updatablePackagesInfo);

						$helper = $this->getHelper('question');
						$question = PackageCommandHelper::getQuestion('Which package do you want to update', null, '?');
						$question->setValidator(function ($answer) use ($updatablePackages) {
							return PackageCommandHelper::validatePackageSelection($answer, $updatablePackages);
						});

						$packageKey = $helper->ask($input, $output, $question);

						$output->writeln('<info>' . tr('Updating package: ') . $packageKey . '</info>');
						$result = $composerManager->updatePackage($packageKey);
						$io->newLine();
						$output->writeln($result);
					}
				}
			}
		} else {
			$output->writeln('<error>' . tr('Composer could not be executed.') . '</error>');
			return;
		}
	}
}
