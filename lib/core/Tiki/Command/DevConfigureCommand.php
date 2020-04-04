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
use TikiLib;
use TWVersion;

/**
 * Install or update Tiki development files
 *
 * Installs composer development files and configures Tiki for unit testing.
 *
 * @package Tiki\Command
 */

class DevConfigureCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('dev:configure')
			->setDescription('Install or update development files')
			->setHelp('Install or update and configure composer development vendor files and unit test config & database.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Lets first check that some requirements are met.
		if (! is_callable('exec')) {
			$output->writeln('<error>Must enable exec() for this command</error>');
			exit(1);
		}

		$output->writeln('Checking composer development files');
		if (! class_exists('PHPUnit\Framework\TestCase')) {
			exec('php temp/composer.phar --ansi install -d vendor_bundled --no-progress --prefer-dist -n 2>&1', $raw, $error);
			if ($error) {
				$output->writeln('<error>Error: composer files not installed. Check temp/composer.phar</error>');
			} else {
				$output->writeln($raw, OutputInterface::VERBOSITY_VERY_VERBOSE);
				$output->writeln('<info>Done: Composer dev files installed</info>');
			}

		} else {
			$output->writeln('<info>Done: Composer dev files already installed</info>');
		}

		$output->writeln('Checking phpunit');
		if (file_exists('phpunit')) {
			$output->writeln('<info>Done: phpunit was already callable via "php phpunit" in the project root.</info>');
		} else {
			if (symlink('vendor_bundled/vendor/phpunit/phpunit/phpunit', 'phpunit')) {
				$output->writeln('<info>Done: phpunit is now callable via "php phpunit" in the project root.</info>');
			} else {
				$output->writeln('<error>Could not create symlink</error>');
				$output->writeln('Try using the following command: ln -s vendor_bundled/vendor/phpunit/phpunit/phpunit phpunit');
			}
		}

		$output->writeln('Checking PHP Unit local.php file');
		include_once('lib/setup/twversion.class.php');
		$tikiVersion = new TWVersion();
		$tikiVersion = $tikiVersion->getBaseVersion();
		if (file_exists('lib/test/local.php')) {
			$config = file_get_contents('lib/test/local.php');
			preg_match('/\$dbversion_tiki=\'([\d.]+)\';/', $config, $matches);
			$version = $matches[1];
			if (empty($version)) {
				$output->write('<comment>Warning: Could not find database version in file, consider deleting lib/test/local.php and re-running command</comment>');
			} else {
				if ($tikiVersion === $version) {
					$output->writeln('<info>Done: Database version already current in config</info>');
				} else {
					$output->writeln("<info>Done: Updated database version in config from $version to $tikiVersion</info>");
					str_replace($version, $tikiVersion, $config);
					if (file_put_contents('lib/test/local.php', $config)) {
						$output->writeln("<info>Done: Updated database version in config from $version to $tikiVersion</info>");
					} else {
						$output->writeln('<error>Could not write to lib/test/local.php while updating database version</error>');
					}
				}
			}
		} else {
			$output->writeln('No unit test config file found', OutputInterface::VERBOSITY_VERY_VERBOSE);
			$config = <<<EOT
<?php
/*
File written by php console.php dev:configure
*/

\$db_tiki='mysqli';
\$dbversion_tiki='$tikiVersion';
\$host_tiki='localhost';
\$user_tiki='tiki_tester';
\$pass_tiki='tiki_tester_pass';
\$dbs_tiki='tiki_unit_test';
\$client_charset='utf8mb4';

EOT;
			if (file_put_contents('lib/test/local.php', $config)) {
				$output->writeln('<info>Done: lib/test/local.php written</info>');
			} else {
				$output->writeln('<error>Error: Could not write lib/test/local.php</error>');
			}
		}

		$output->writeln('Checking PHP Unit database status');
		if ($this->databaseConnect()) {
			$output->writeln('<info>Done: Database already connecting</info>');
		} elseif ((include('lib/test/local.php'))) {
			if (DB_STATUS) {
				$tikilib = TikiLib::lib('tiki');
				$error = '';

				$output->writeln('Creating Database User', OutputInterface::VERBOSITY_VERBOSE);
				$query = "CREATE USER IF NOT EXISTS `$user_tiki`@`$host_tiki` IDENTIFIED BY '$pass_tiki';";
				$tikilib->queryError($query, $error);
				if (! empty($error)) {
					$output->writeln('<comment>Could not create user</comment>', OutputInterface::VERBOSITY_VERBOSE);
					$output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
				}

				$output->writeln('Creating Database', OutputInterface::VERBOSITY_VERBOSE);
				$query = "CREATE DATABASE IF NOT EXISTS `$dbs_tiki` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
				$tikilib->queryError($query, $error);
				if (! empty($error)) {
					$output->writeln('<comment>Could not create database</comment>', OutputInterface::VERBOSITY_VERBOSE);
					$output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
				}

				$output->writeln('Assigning user rights on database', OutputInterface::VERBOSITY_VERBOSE);
				$query = "GRANT ALL ON $dbs_tiki.* TO `$user_tiki`@`$host_tiki`;";
				$tikilib->queryError($query, $error);
				if (! empty($error)) {
					$output->writeln('<comment>Could not assign user rights</comment>', OutputInterface::VERBOSITY_VERBOSE);
					$output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
				}
			}
			if ($this->databaseConnect()) {
				$output->writeln('<info>Done: PHP Unit database configured</info>');
			} else {
				if (DB_STATUS) {
					$output->writeln('<error>Error: PHP Unit database setup error</error>');
				} else {
					$output->writeln('<comment>Could not detect that PHP Unit database has been setup</comment>');
					$output->writeln('Tiki database is not connecting, are you sure that mysql is running?');
				}
				$output->writeln('You may try the following:');
				$output->writeln('1. Ensure Tiki database connection root credentials and run this command again.');
				$output->writeln('2. Open PHPMyAdmin and run the following commands:');
				$output->writeln("  CREATE USER IF NOT EXISTS `$user_tiki`@`$host_tiki` IDENTIFIED BY '$pass_tiki';");
				$output->writeln("  CREATE DATABASE IF NOT EXISTS `$dbs_tiki` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
				$output->writeln("  GRANT ALL ON $dbs_tiki.* TO `$user_tiki`@`$host_tiki`;");
				$output->writeln('3. Install via the terminal:');
				$output->writeln('Hints: The default mysql password is "root". Your mysql $PATH must be configured for the below to work.');
				$output->writeln('  mysql -u root -p');
				$output->writeln("  create database $dbs_tiki;");
				$output->writeln("  grant all privileges on $dbs_tiki.* TO '$user_tiki'@'$host_tiki' identified by '$pass_tiki';");
				$output->writeln('  flush privileges;');
				$output->writeln('  \q');
			}
		} else {
			$output->writeln('<error>Error: database config not found</error>');
			$output->writeln('Try running this command again, or follow instructions in lib/test/local.php.dist', OutputInterface::VERBOSITY_VERBOSE);
		}
	}

	/**
	 * Checks if a database connection can be made to PHP Unit database
	 *
	 * @return bool true on success, false on failure.
	 */

	private function databaseConnect() : bool
	{
		if (! (include 'lib/test/local.php')) {
			return false;
		}
		$link = mysqli_connect($host_tiki, $user_tiki, $pass_tiki, $dbs_tiki);

		if (! $link) {
			mysqli_close($link);
			return false;
		}
		mysqli_close($link);
		return true;
	}
}
