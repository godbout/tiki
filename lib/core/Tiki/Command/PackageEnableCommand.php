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

class PackageEnableCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('package:enable')
			->setDescription('Enable a Tiki Package')
			->addArgument(
				'package',
				InputArgument::REQUIRED,
				'Tiki package name'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);

		$packageName = $input->getArgument('package');

		$path = ExtensionManager::locatePackage($packageName);

		if (empty($path)) {
			$io->error('Package was not found. Did you forgot to install');
			return 1;
		}

		$extensionPackage = ExtensionManager::get($packageName);
		$update = isset($extensionPackage) ? $extensionPackage->hasUpdate() : false;

		$success = ExtensionManager::enableExtension($packageName, $path);
		$messages = ExtensionManager::getMessages();
		$io->writeln(implode(PHP_EOL, $messages));

		if ($success && $update) {
			$io->success(tr('Extension %0 was updated', $packageName));
			return 0;
		}

		if ($success) {
			$io->success(tr('Extension %0 is now enabled', $packageName));
			return 0;
		}

		$io->error(tr('Extension %0 was not enabled.', $packageName));
		return 1;
	}
}
