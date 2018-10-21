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
use Tiki\Package\ComposerManager;

class PackageClearCacheCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('package:clearcache')
			->setDescription('Deletes all content from Composer\'s cache directories.')
			->setHelp('This command allows you to clear the composer cache.');
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
			$result = $composerManager->getComposer()->execClearCache();
			$output->writeln($result);
		} else {
			$output->writeln('<error>' . tr('Composer could not be executed.') . '</error>');
			return;
		}
	}
}
