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

class PackageInstallCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('package:install')
			->setDescription('Install package')
			->setHelp('This command allows you to install packages.')
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
			$availableComposerPackages = $composerManager->getAvailable(true, true);
			if (! empty($availableComposerPackages)) {
				$io = new SymfonyStyle($input, $output);
				$io->newLine();

				$packageKey = $input->getArgument('package');
				if (isset($packageKey) && ! empty($packageKey)) {
					if (in_array($packageKey, array_column($availableComposerPackages, 'key'))) {
						$output->writeln('<info>' . tr('Installing package: ') . $packageKey . '</info>');
						$result = $composerManager->installPackage($packageKey);
						$io->newLine();
						$output->writeln($result);
					} else {
						$output->writeln('<error>' . tr('Invalid Package: ') . $packageKey . '</error>');
						return;
					}
				} else {
					$availablePackagesInfo = PackageCommandHelper::getAvailablePackagesInfo($availableComposerPackages);

					$output->writeln('Packages Available to Install');
					PackageCommandHelper::renderAvailablePackagesTable($output, $availablePackagesInfo);

					$helper = $this->getHelper('question');
					$question = PackageCommandHelper::getQuestion('Which package do you want to install', null, '?');
					$question->setValidator(function ($answer) use ($availableComposerPackages) {
						return PackageCommandHelper::validatePackageSelection($answer, $availableComposerPackages);
					});

					$packageKey = $helper->ask($input, $output, $question);

					$output->writeln('<info>' . tr('Installing package: ') . $packageKey . '</info>');
					$result = $composerManager->installPackage($packageKey);
					$io->newLine();
					$output->writeln($result);
				}
			} else {
				$output->writeln('<comment>' . tr('No packages available to be installed.') . '</comment>');
			}
		} else {
			$output->writeln('<error>' . tr('Composer could not be executed.') . '</error>');
			return;
		}
	}
}
