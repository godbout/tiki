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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ExtensionManager;

class PackageDisableCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('package:disable')
			->setDescription('Disable a Tiki Package')
			->addArgument(
				'package',
				InputArgument::REQUIRED,
				'Tiki package name'
			)
			->addOption(
				'revert',
				null,
				InputOption::VALUE_NONE,
				'Rollback profile changes'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);

		$availablePaths = [
			'vendor',
			'vendor_custom'
		];

		$packageName = $input->getArgument('package');

		foreach ($availablePaths as $path) {
			if (file_exists($path . '/' . $packageName)) {
				$basePath = $path;
				break;
			}
		}

		if (empty($basePath)) {
			$io->error('No folder was found. Did you forgot to install');
			return 1;
		}

		$rollback = $input->getOption('revert');

		$success = ExtensionManager::disableExtension($packageName, $rollback);
		$messages = ExtensionManager::getMessages();
		$io->writeln(implode(PHP_EOL, $messages));

		if ($success) {
			$io->success(tr('Extension %0 is now disabled', $packageName));
			return 0;
		}

		$io->error(tr('Extension %0 was not disabled.', $packageName));
		return 1;
	}
}
