<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('database:update')
			->setDescription('Update the database to the latest schema')
			->addOption(
				'auto-register',
				'a',
				InputOption::VALUE_NONE,
				'Record any failed patch as applied.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$autoRegister = $input->getOption('auto-register');
		$installer = \Installer::getInstance();
		$installed = $installer->tableExists('users_users');

		if ($installed) {
			// tiki-setup.php may not have been run yet, so load the minimum required libs to be able process the schema updates
			require_once('lib/tikilib.php');

			$installer->update();
			$output->writeln('Update completed.');
			foreach (array_keys(\Patch::getPatches([\Patch::NEWLY_APPLIED])) as $patch) {
				$output->writeln("<info>Installed: $patch</info>");
			}
			foreach (array_keys(\Patch::getPatches([\Patch::NOT_APPLIED])) as $patch) {
				$output->writeln("<error>Failed: $patch</error>");

				if ($autoRegister) {
					\Patch::$list[$patch]->record();
				}
			}

			if (count($installer->executed)) {
				foreach ($installer->executed as $script) {
					$output->writeln("<info>Executed: $script</info>");
				}
			}

			$output->writeln('<info>Queries executed successfully: ' . count($installer->queries['successful']) . '</info>');

			foreach ($installer->queries['failed'] as $error) {
				list( $query, $message, $patch ) = $error;
				if (! $patch) {
					// Installer::query() does not set a meaningful third element when the error is caused by a PHP script. Needs some architectural work to solve properly
					$patch = 'unknown patch script';
				}
				$output->writeln("<error>Error in $patch\n\t$query\n\t$message</error>");
			}

			// tiki-setup.php may not have been run yet, so load the minimum required libs to be able to clear the caches
			require_once('lib/cache/cachelib.php');
			$cachelib = new \Cachelib();
			$cachelib->empty_cache();
		} else {
			$output->writeln('<error>Database not found.</error>');
		}
	}
}
