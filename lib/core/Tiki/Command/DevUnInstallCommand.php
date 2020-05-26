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

/**
 * Remove Tiki development files
 *
 * Remove composer development files and configurations for unit testing.
 *
 * @package Tiki\Command
 */

class DevUnInstallCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('dev:uninstall')
			->setDescription('Uninstall development files')
			->setHelp('Remove composer development vendor files and unit test config. Leaves unit testing database intact.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Lets first check that some requirements are met.
		if (! is_callable('exec')) {
			$output->writeln('<error>Must enable exec() for this command</error>');
			exit(1);
		}

		// remove phplint symlink from root directory first (wont work after composer files are removed)
		if (file_exists('phplint')) {
			if (unlink('phplint')) {
				$output->writeln('phplint was removed from the root directory');
			} else {
				$output->writeln('<error>phplint could not be removed from the root directory, delete manually.</error>');
			}
		} else {
			$output->writeln('phplint was not present in root directory');
		}

		// remove phplint config
		if (file_exists('.phplint.yml')) {
			if (unlink('.phplint.yml')) {
				$output->writeln('.phplint.yml was removed from the root directory', OutputInterface::VERBOSITY_VERBOSE);
			} else {
				$output->writeln('<error>.phplint.yml could not be removed from the root directory, delete manually.</error>');
			}
		} else {
			$output->writeln('.phplint.yml was not present in root directory', OutputInterface::VERBOSITY_VERY_VERBOSE);
		}

		// remove phpunit symlink from root directory first (wont work after composer files are removed)
		if (file_exists('phpunit')) {
			if (unlink('phpunit')) {
				$output->writeln('phpunit was removed from the root directory');
			} else {
				$output->writeln('<error>phpunit could not be removed from the root directory, delete manually.</error>');
			}
		} else {
			$output->writeln('phpunit was not present in root directory');
		}

		if (class_exists(\PHPUnit\Framework\TestCase::class)) {
			$output->writeln('Removing composer development files');
			exec('php temp/composer.phar --ansi install -d vendor_bundled --no-progress --prefer-dist -n --no-dev 2>&1', $raw, $error);
			if ($error) {
				$output->writeln('<error>composer error. Check temp/composer.phar</error>');
			} else {
				$output->writeln($raw, OutputInterface::VERBOSITY_VERY_VERBOSE);
				$output->writeln('Composer dev files removed');
			}
		} else {
			$output->writeln('No composer development files detected');
		}

		if (file_exists('lib/test/local.php')) {
			if (DB_STATUS) {
				$tikilib = \TikiLib::lib('tiki');
				$error = '';

				require_once('lib/test/local.php');
				$query = "DROP SCHEMA IF EXISTS $dbs_tiki;";
				$tikilib->queryError($query, $error);
				if (! empty($error)) {
					$output->writeln('<comment>Could not remove database</comment>');
					$output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
				} else {
					$output->writeln('PHPUnit database removed');
				}

				$query = "DROP USER IF EXISTS $user_tiki;";
				$tikilib->queryError($query, $error);
				if (! empty($error)) {
					$output->writeln('<comment>Could not remove database user</comment>');
					$output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
				} else {
					$output->writeln('PHPUnit database user removed');
				}
			} else {
				$output->writeln('<comment>Database not available, could not remove automatically.</comment>');
				$output->writeln('Please remove database and user manually, if they exist. See: http://dev.tiki.org/Tiki-Unit-Testing');
			}

			if (unlink('lib/test/local.php')) {
					$output->writeln('Unit test configuration removed');
			} else {
					$output->writeln('<error>Unit test configuration cold not be removed</error>');
			}
		} else {
			$output->writeln('Unit test configuration file not found');
		}
	}
}
