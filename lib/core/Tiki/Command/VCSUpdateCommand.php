<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Warning: this script does not check the required and available PHP versions
// before doing an update. That might result in a broken Tiki installation.

namespace Tiki\Command;

use LogsLib;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Command\HelpCommand;
use Exception;

/**
 * Add a singleton command "svnup" using the Symfony console component for this script
 *
 * Class SvnUpCommand
 * @package Tiki\Command
 */

class VCSUpdateCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('vcs:update')
			->setDescription('Update SVN to latest version & perform tasks for a smooth update.')
			->setHelp('Updates SVN repository to latest version and performs necessary tasks in Tiki for a smooth update. Suitable for both development and production.')
			->addOption(
				'no-secdb',
				's',
				InputOption::VALUE_NONE,
				'Skip updating the secdb database.'
			)
			->addOption(
				'no-reindex',
				'r',
				InputOption::VALUE_NONE,
				'Skip re-indexing Tiki.'
			)
			->addOption(
				'no-db',
				'd',
				InputOption::VALUE_NONE,
				'Make no changes to the database. (SvnUp, dependencies and privilege checks only. Logging disabled.)'
			)
			->addOption(
				'no-generate',
				'G',
				InputOption::VALUE_NONE,
				"Don't re-generate the caches. Can take a long time on a large site."
			)
			->addOption(
				'conflict',
				'c',
				InputOption::VALUE_REQUIRED,
				'What would you like to do if a svn conflict is found? Options:abort, postpone, mine-conflict, theirs-conflict',
				'abort'
			)
			->addOption(
				'email',
				'e',
				InputOption::VALUE_REQUIRED,
				'Email address to send a message to if errors are encountered.'
			)
			->addOption(
				'lag',
				'l',
				InputOption::VALUE_REQUIRED,
				'Time delay commits by X number of days. Useful for avoiding newly introduced bugs in automated updates.'
			)
			->addOption(
				'user',
				'u',
				InputOption::VALUE_REQUIRED,
				'User account to run setup.sh with (for file permissions setting).'
			)
			->addOption(
				'group',
				'g',
				InputOption::VALUE_REQUIRED,
				'User group to run setup.sh with (for file permissions setting).'
			);
	}

	/**
	 *
	 * Determines if errors exist and outputs error messages.
	 *
	 * @param ConsoleLogger $logger
	 * @param string $return			Info to print, in a level of elevated verbosity
	 * @param string $errorMessage		Error message to log-display upon failure
	 * @param array  $errors			Error messages to check for, sending a '' will produce an error if no output is
	 * 													produced, handy as an extra check when output is expected.
	 * @param bool 	$log				If errors should be logged.
	 */
	public function OutputErrors(ConsoleLogger $logger, $return, $errorMessage = '', $errors = [], $log = true)
	{
		$logger->info($return);

		// check for errors.
		foreach ($errors as $error) {
			if (($error === '' && ! $return) || ($error && strpos($return, $error))) {
				$logger->error($errorMessage);
				if ($log) {
					$logs = new LogsLib();
					$logs->add_action('svn update', $errorMessage, 'system');
				}
			}
		}
	}

	/**
	 * Calls database update command and handles verbiage.
	 *
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */

	protected function dbUpdate(OutputInterface $output)
	{
		$console = new Application;
		$console->add(new UpdateCommand);
		$console->setAutoExit(false);
		$console->setDefaultCommand('database:update');
		$input = null;
		if ($output->getVerbosity() <= OutputInterface::VERBOSITY_VERBOSE) {
			$input = new ArrayInput(['-q' => null]);
		} elseif ($output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
			$input = new ArrayInput(['-vvv' => null]);
		}
		$console->run($input);
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$verbosityLevelMap = [
			LogLevel::CRITICAL   => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::ERROR      => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::NOTICE     => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::INFO       => OutputInterface::VERBOSITY_VERY_VERBOSE
		];
		$logger = new ConsoleLogger($output, $verbosityLevelMap);
		$errors = false;
		$rev = 'HEAD';

		// check that proper options were given, else die with help options.
		if (! in_array($input->getOption('conflict'), ['abort', 'postpone', 'mine-conflict', 'theirs-conflict'])) {
			$help = new HelpCommand();
			$help->setCommand($this);
			$help->run($input, $output);
			$logger->notice('Invalid option for --conflict, see usage above.');
			return;
		}

		// check that the --lag option is valid, and complain if its not.
		if ($input->getOption('lag')) {
			if ($input->getOption('lag') < 0 || ! is_numeric($input->getOption('lag'))) {
				$help = new HelpCommand();
				$help->setCommand($this);
				$help->run($input, $output);
				$logger->notice('Invalid option for --lag, must be a positive integer.');
				return;
			}
			// current time minus number of days specified through lag
			$rev = date('{"Y-m-d H:i"}', time() - $input->getOption('lag') * 60 * 60 * 24);
		}
		// if were using a db, then configure it.
		if (! DB_STATUS && ! $input->getOption('no-db')) {
			$input->setOption('no-db', true);
		}

		// if were using a db, then configure it.
		if (! $input->getOption('no-db')) {
			$logslib = new LogsLib();
		}

		// die gracefully if shell_exec is not enabled;
		if (! is_callable('shell_exec')) {
			if (! $input->getOption('no-db')) {
				$logslib->add_action('svn update', 'Automatic update failed. Could not execute shell_exec()', 'system');
			}
			$logger->critical('Automatic update failed. Could not execute shell_exec()');
			die();
		}
		if (! is_dir('.svn')) {
			$logger->critical('Only SVN supported at the moment.');
			die();
		}

		/** @var int The number of steps the progress bar will show */
		$max = 8;
		// now subtract steps depending on options elected
		if ($input->getOption('no-db')) {
			$max -= 5;
		} else {
			if ($input->getOption('no-secdb')) {
				$max --;
			}
			if ($input->getOption('no-reindex')) {
				$max --;
			}
		}

		$progress = new ProgressBar($output, $max);
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$progress->setOverwrite(false);
		}
		$progress::setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
		$progress->setFormat('custom');


		$progress->setMessage('Pre-update checks');
		$progress->start();

		// set revision number beginning with.
		$raw = shell_exec('svn info 2>&1');
		$output->writeln($raw, OutputInterface::VERBOSITY_DEBUG);
		preg_match('/Revision: (\d+)/', $raw, $startRev);
		if ($startRev) {
			$startRev = $startRev[1];
		} else {
			$startRev = ' unknown';
		}

		// Set this before, so if 'abort' is used, it can be changed to a valid option later
		$svnConflict = $input->getOption('conflict');
		// start svn conflict checks
		if ($input->getOption('conflict') === 'abort') {
			$raw = shell_exec("svn merge --dry-run -r BASE:$rev . 2>&1");
			$output->writeln($raw, OutputInterface::VERBOSITY_DEBUG);

			if (strpos($raw, 'E155035:')) {
				$progress->setMessage('Working copy currently conflicted. Update Aborted.');
				if ($input->getOption('email')) {
					mail($input->getOption('email'), 'Svn Up Aborted', wordwrap('Working copy currency conflicted. Update Aborted. ' . __FILE__, 70, "\r\n"));
				}
				if (! $input->getOption('no-db')) {
					$logslib->add_action('svn update', "Working copy currency conflicted. Update Aborted. r$startRev", 'system');
				}
				$progress->advance();
				die("\n");
			}

			//	Check if working from from mixed revision, this happens when a commit is made and causes merges to fail.
			if (strpos($raw, 'E195020:')) {
				$progress->setMessage('Updating mixed revision working copy to single reversion');
				preg_match('/\[\d*:(\d*)]/', $raw, $mixedRev);
				$mixedRev = $mixedRev[1];

				// Now that we know the upper revision number, svn up to it.
				$errors = ['', 'Text conflicts'];
				$this->OutputErrors($logger, shell_exec('svn update --accept postpone --revision ' . $mixedRev . ' 2>&1'), 'Problem with svn up, check for conflicts.', $errors, ! $input->getOption('no-db'));
				if ($logger->hasErrored()) {
					$progress->setMessage('Preexisting local conflicts exist. Update Aborted.');
					if ($input->getOption('email')) {
						echo mail($input->getOption('email'), 'Svn Up Aborted', wordwrap('Preexisting local conflicts exist. Update Aborted. ' . __FILE__, 70, "\r\n"));
					}
					if (! $input->getOption('no-db')) {
						$logslib->add_action('svn update', "Preexisting local conflicts exist. Update Aborted. r$startRev", 'system');
					}
					$progress->advance();
					die("\n"); // If custom mixed revision merges were made with local changes, this could happen.... (very unlikely)
				}
				// now re-check for conflicts
				$raw = shell_exec("svn merge --dry-run -r BASE:$rev .  2>&1");
				$output->writeln($raw, OutputInterface::VERBOSITY_DEBUG);
			}
			if (strpos($raw, "\nC    ") !== false) {
				$progress->setMessage('Conflicts exist between working copy and repository. Update Aborted.');
				if ($input->getOption('email')) {
					echo mail($input->getOption('email'), 'Svn Up Aborted', wordwrap('Conflicts exist between working copy and repository. Update Aborted. ' . __FILE__, 70, "\r\n"));
				}
				if (! $input->getOption('no-db')) {
					$logslib->add_action('svn update', "Conflicts exist between working copy and repository. Update Aborted. r$startRev", 'system');
				}
				$progress->advance();
				die("\n");
			}
			// we need a valid option, even though it wil never be used.
			$svnConflict = 'postpone';
		}

		$progress->setMessage('Updating SVN');
		$progress->advance();
		$errors = ['','Text conflicts'];
		$this->OutputErrors($logger, shell_exec("svn update --revision $rev --accept $svnConflict 2>&1"), 'Problem with svn up, check for conflicts.', $errors, ! $input->getOption('no-db'));

		// set revision number updated to.
		$raw = shell_exec('svn info  2>&1');
		$output->writeln($raw, OutputInterface::VERBOSITY_DEBUG);
		preg_match('/Revision: (\d+)/', $raw, $endRev);
		if ($endRev) {
			$endRev = $endRev[1];
		} else {
			$endRev = ' unknown';
		}

		$raw = shell_exec('svn cleanup  2>&1');
		$output->writeln($raw, OutputInterface::VERBOSITY_DEBUG);

		if (! $input->getOption('no-db')) {
			$cacheLib = new \Cachelib();
			$progress->setMessage('Clearing all caches');
			$progress->advance();
			$cacheLib->empty_cache();
		}

		$progress->setMessage('Updating dependencies & setting file permissions');
		$progress->advance();
		$errors = ['', 'Please provide an existing command', 'you are behind a proxy', 'Composer failed', 'Wrong PHP version'];

		$setupParams = '';
		if ($input->getOption('user')) {
			$setupParams .= ' -u ' . $input->getOption('user');
		}
		if ($input->getOption('group')) {
			$setupParams .= ' -g ' . $input->getOption('group');
		}

		$this->OutputErrors($logger, shell_exec("sh setup.sh $setupParams -n fix 2>&1"), 'Problem running setup.sh', $errors, ! $input->getOption('no-db'));   // 2>&1 suppresses all terminal output, but allows full capturing for logs & verbiage

		if (! $input->getOption('no-db')) {
			// generate a secdb database so when database:update is run, it also gets updated.
			if (! $input->getOption('no-secdb')) {
				$progress->setMessage('Updating secdb');
				$progress->advance();

				$errors = ['is not writable', ''];
				$this->OutputErrors($logger, shell_exec('php doc/devtools/release.php --only-secdb --no-check-svn'), 'Problem updating secdb', $errors);
			}

			// note: running database update also clears the cache
			$progress->setMessage('Updating database');
			$progress->advance();
			try {
				$this->dbUpdate($output);
			} catch (\Exception $e) {
				$logger->error('Database update error: ' . $e->getMessage());
				$logslib->add_action('svn update', 'Database update error: ' . $e, 'system');
			}


			// rebuild tiki index. Since this could take a while, make it optional.
			if (! $input->getOption('no-reindex')) {
				$progress->setMessage('Rebuilding search index');
				$progress->advance();
				$errors = ['', 'Fatal error'];
				$shellCom = 'php console.php index:rebuild';
				if ($output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
					$shellCom .= ' -vvv';
				}

				putenv('SHELL_VERBOSITY'); // Clear the environment variable, since console.php (Symfony console application) will pick this value if set
				$this->OutputErrors($logger, shell_exec($shellCom . ' 2>&1'), 'Problem Rebuilding Index', $errors, ! $input->getOption('no-db'));   // 2>&1 suppresses all terminal output, but allows full capturing for logs & verbiage
			}

			/* generate caches */
			if (! $input->getOption('no-generate')) {
				$progress->setMessage('Generating caches');
				$progress->advance();
				try {
					//$cacheLib->generateCache();    disable generating module cache until regression if fixed that causes premature termination.
					$cacheLib->generateCache(['templates', 'misc']);
				} catch (\Exception $e) {
					$logger->error('Cache generating error: ' . $e->getMessage());
					$logslib->add_action('svn update', 'Cache generating error: ' . $e, 'system');
				}
			}
		}

		if ($logger->hasErrored()) {
			if (! $input->getOption('no-db')) {
				$logslib->add_action('svn update', "Automatic update completed with errors, r$startRev -> r$endRev, Try again or debug.", 'system');
			}
			if ($input->getOption('email')) {
				echo mail($input->getOption('email'), 'Svn Up Aborted', wordwrap("Automatic update completed with errors, r$startRev -> r$endRev, Try again or debug." . __FILE__, 70, "\r\n"));
			}
			$progress->setMessage("Automatic update completed with errors, r$startRev -> r$endRev, Try again or ensure update functioning.");
		} elseif ($input->getOption('no-db')) {
			$progress->setMessage("<comment>Automatic update completed in no-db mode, r$startRev -> r$endRev, Database not updated.</comment>");
		} else {
			$logslib->add_action('svn update', "Automatic update completed, r$startRev -> r$endRev", 'system');
			$progress->setMessage("<comment>Automatic update completed r$startRev -> r$endRev</comment>");
		}

		$progress->finish();
		echo "\n";
	}
}
