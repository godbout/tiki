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
use Symfony\Component\Process\PhpProcess;

/**
 * Update Tiki development files
 *
 * Update Tiki version number for unit testing.
 *
 * @package Tiki\Command
 */

class DevUnitCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('dev:phpunit')
			->setDescription('Run unit tests')
			->setHelp('Run Tiki PHP Unit tests.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Lets first check that some requirements are met.
		if (! is_callable('exec')) {
			$output->writeln('<error>Must enable exec() for this command</error>');
			exit(1);
		}

		if (file_exists('lib/test/local.php')) {
			require_once('lib/test/local.php');
		} else {
			$output->writeln('<error>Could not find lib/test/local.php</error>');
			return;
		}

		$link = mysqli_connect($host_tiki, $user_tiki, $pass_tiki, $dbs_tiki);

		if (! $link) {
			$output->writeln('<error>Can not connect to unit testing database.</error>');
			$output->writeln("Debugging errno: " . mysqli_connect_errno());
			$output->writeln("Debugging error: " . mysqli_connect_error());
			return;
		}
		mysqli_close($link);

		$process = new PhpProcess("<?php require('vendor_bundled/vendor/phpunit/phpunit/phpunit');");
		$process->start();
		while ($process->isRunning()) {
			$raw = $process->getIncrementalOutput();
			if ($raw) {
				$output->write($raw);
			}
		}
	}
}
