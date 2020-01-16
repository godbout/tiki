<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TWVersion;

class SemiAutoMergeCommand extends Command
{
	private $branch;

	protected function configure()
	{
		$this
			->setName('vcs:automerge')
			->setDescription('Semi Auto Merge')
			->setHelp('While in trunk, semi auto merge changes from the previous branch.')
			->addOption(
				'no-check-vcs',
				null,
				InputOption::VALUE_NONE,
				'Do not check if there is pre-existing changes in the local copy. Warning: All changes in working copy are committed irregardless of if they were present beforehand.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) : void
	{
		$output->writeln("Verifying...");

		$TWV = new TWVersion;

		if ($TWV->branch !== 'trunk'){
			$output->writeln('<error>Must be in trunk to merge</error>');
			exit(1);
		}

		// Proceed to update
		$output->writeln('Updating Working Copy...');

		$command = 'svn up 2>&1';
		$raw = shell_exec('svn up 2>&1');
		$output->writeln($command, $output::VERBOSITY_DEBUG);
		$output->writeln($raw, $output::VERBOSITY_DEBUG);

		// if there is changes that have not been committed, then ask to commit them.
		if (is_file('svn-commit.tmp')) {
            $output->writeln('<info>A previous semi-auto merge existed </info>');
			$this->commitChanges($input, $output, true);
		}

		if ($input->getOption('no-check-vcs')) {
			$output->writeln('<info>Note: Not checking uncommitted changes. Make sure you commit the right files!</info>');
		} elseif ($this->uncommittedChanges()) {
			$output->writeln('<error>Working copy has uncommitted changes. Shelf your changes or use --no-check-vcs to bypass</error>');
			exit(1);
		}

		// Do merge
		$output->writeln('Merging...');

		$this->branch = 'branches/' . ((int)$TWV->getBaseVersion()-1) . '.x';
		// we find the last merge and then advance by one to know where we must merge from
		try {
		    $output->writeln('Finding last merge', $output::VERBOSITY_DEBUG);
			$mergeFrom = $this->findLastMerge();
			$mergeFrom ++;
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}

		if (! $mergeFrom) {
			$output->writeln('<error>Could not find previous merge. Impossible to merge automatically.</error>');
			exit(1);
		}

		$command = "svn log -r $mergeFrom:HEAD --xml " . escapeshellarg('^/' . $this->branch) . ' 2>&1';
        $output->writeln($command, $output::VERBOSITY_DEBUG);
		$logFile = shell_exec($command);
		$output->writeln($logFile, $output::VERBOSITY_DEBUG);
		$logFile = simplexml_load_string($logFile, null, LIBXML_NOCDATA);
		$logFile = json_encode($logFile);
		$logFile = json_decode($logFile, true);


		if (! isset($logFile['logentry'])) {
		    // if there was no results returned, remove any empty array elements
		    $logFile = false;
        } elseif (isset($logFile['logentry'][0])) {
            // the logentry is an multidimensional array if more than one result is returned, so lets make them all even so we can loop through.
		    $logFile = $logFile['logentry'];
		}
		$commitMessages = [];
		$commitTags = [];

		foreach ($logFile as $log) {
			if (strpos($log['msg'],'[bp/r') === false) {
				$authors[] = $log['author'];
				$commitMessages[] = '(' . $log['author'] . ') ' . trim($log['msg']);
				preg_match_all('/\[[A-Z]{2,3}\]/', $log['msg'], $matches);
				foreach ($matches[0] as $match){
					if (! in_array($match, $commitTags, false)) {
						$commitTags[] = $match;
					}
				}
			}
		}

		$commitCount = count($commitMessages);
		if ($commitCount === 0) {
			$output->writeln('<info>No changes to merge</info>');
			// If there was an svn commit file, we remove it because it is certainly not needed any longer.
            $output->writeln('Now deleting svn-commit.tmp if it exists.', $output::VERBOSITY_DEBUG);
			if (file_exists('svn-commit.tmp')) {
				unlink('svn-commit.tmp');
			}
			exit(0);
		}

		$lastRevision = '';
		$separator = '';

		if ($commitCount > 1 ) {
			$authors = array_unique($authors);
			$authors = implode(', ', $authors) . "\n";
			$separator = ':';
			$lastRevision = $logFile[($commitCount - 1)]['@attributes']['revision'];
			$commitCount = "$commitCount commits from ";
		} else {
			$commitCount = '';
			$authors = '';
		}

		$firstRevision = $logFile[0]['@attributes']['revision'];

		$command = 'svn merge '. escapeshellarg('^/' . $this->branch) . " -r $mergeFrom:HEAD";
		$output->writeln($command, $output::VERBOSITY_DEBUG);
		passthru($command);

		$conflictMessage = '';
		if ($this->hasConflicts()) {
			$conflictMessage = 'With Conflicts - ';
		}
		$commitMessages = implode("\n", $commitMessages);

		$message = "[MRG/r$firstRevision$separator$lastRevision] $conflictMessage$commitCount$authors$commitMessages";

		file_put_contents('svn-commit.tmp', $message);

		$output->writeln('<info>Please verify local copy now. All changes in the working copy will be committed.</info>');
		$this->commitChanges($input, $output);

	}

	/**
	 * Commits the semi-auto merge, performs checks surrounding merging, and asks the user for confirmation.
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @param bool            $ignore  true if ignore option should be presented, false otherwise
	 *
	 */
	private function commitChanges(InputInterface $input, OutputInterface $output, bool $ignore = false): void
	{
		$helper = $this->getHelper('question');
		$output->writeln('<info>The following commit message will be used:</info>');
		$output->writeln(file_get_contents('svn-commit.tmp'));

        /** Contains the options available, the last option becomes the default */
		$bundles = ['yes', 'no'];
		if ($ignore) {
			$bundles[] = 'ignore';

		}

		$options = implode('|', $bundles);
        if ($ignore) {
            $output->writeln('<info>This is a previous Semi Merge. (use ignore to discard and check for new changes)</info>');
        }
		$question = new Question("<info>Would you like to commit the Semi Auto Merge now?</info> ($options)", end($bundles));
		$question->setAutocompleterValues($bundles);

		switch ($helper->ask($input, $output, $question)) {
			case 'no':
				$output->writeln('Exiting. Re-run command to commit when ready');
				exit(0);
			case 'yes':
				if ($this->hasConflicts()) {
					$output->writeln('There are unresolved conflicts in your commit. Are you sure you verified the changes?');
					$this->commitChanges($input, $output, $ignore);
				} else {
					$command = 'svn ci -F svn-commit.tmp';
					$output->writeln($command, $output::VERBOSITY_DEBUG);
					passthru($command);
                    $output->writeln('Now deleting svn-commit.tmp.', $output::VERBOSITY_DEBUG);
                    unlink('svn-commit.tmp');
				}
		}
	}

	/**
	 * Searches through SVN logs to find the last MRG or BRANCH commit number, and returns it
	 *
	 * @return int The last semi-auto merge number listed in the commit description.
	 * @throws Exception When an error in establishing a last commit number is encountered.
	 */
	private function findLastMerge(): int
	{
		$short = preg_quote($this->branch, '/');
		$descriptorspec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
		];
		$process = proc_open('svn log --stop-on-copy ', $descriptorspec, $pipes);
		$rev = 0;
		$c = 0;

		if (is_resource($process)) {
			$fp = $pipes[1];

			while (! feof($fp)) {
				$line = fgets($fp, 1024);

				if (preg_match("/^\\[(?:MRG|BRANCH)\\].*$short'?\s+\d+\s+to\s+(\d+)/", $line, $parts)) {
					$rev = (int) $parts[1];
					break;
				}
				if (preg_match('/^\[MRG\/r(\d+):?(\d*)]/', $line, $parts)) {
					$rev = (int)(empty($parts[2]) ? $parts[1] : $parts[2]);
					break;
				}
				$c++;
				if ($c > 100000) {
					Throw New Exception("[MRG] or [BRANCH] message for '$this->branch' not found in 1000000 lines of logs, something has gone wrong...");
				}
			}

			fclose($fp);
			proc_close($process);
			if (! $rev) {
				if ($c > 100000) {
					exit (1);
				}
				throw new Exception('Could not find last revision.');
			}
		}
		return $rev;
	}

	/**
	 * Check if the working copy has any uncommitted changes.
	 *
	 * @return bool true if there is uncommitted changes, false otherwise.
	 */
	private function uncommittedChanges(): bool
	{
		exec('svn diff --summarize 2>&1', $output);
		foreach ($output as $line) {
			if (preg_match('/^[A-Z] {2}/', $line)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Find if conflicts existing in the working copy.
	 *
	 * @return bool true if tree conflicts exist, false otherwise.
	 */
	private function hasConflicts(): bool
	{
		$raw = shell_exec('svn status 2>&1');
		if (strpos($raw, 'Tree conflicts:')) {
			return true;
		}
		return false;
	}
}
