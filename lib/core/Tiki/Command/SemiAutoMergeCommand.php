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
use TWVersion;

class SemiAutoMergeCommand extends Command
{
	private const TIKISVN = 'https://svn.code.sf.net/p/tikiwiki/code/';
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
				'Do not check if there is pre-existing changes in the local copy.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) : void
	{

		// Perform basic checks
		$output->writeln("Verifying...");

		require 'doc/devtools/svntools.php';

		$TWV = new TWVersion;

		if ($TWV->branch !== 'trunk'){
			$output->writeln('<error>Must be in trunk to merge</error>');
			exit(1);
		}

		if ($input->getOption('no-check-vcs')) {
			$output->writeln('<info>Note: Not checking uncommitted changes. Make sure you commit the right files!</info>');
		} elseif ($this->uncommittedChanges()) {
			$output->writeln('<error>Working copy has uncommitted changes. Shelf your changes or use --no-check-vcs to bypass</error>');
			exit(1);
		}

		// Proceed to update
		$output->writeln('Updating Working Copy...');

		shell_exec('svn up');

		$this->branch = '/' . ((int)$TWV->getBaseVersion()-1) . '.x';

		// Do merge
		$output->writeln('Merging...');

		// we find the last merge and then advance by one to know where we must merge from
		$mergeFrom = $this->findLastMerge();
		$mergeFrom ++;

		if (! $mergeFrom) {
			$output->writeln('<error>Could not find previous merge. Impossible to merge automatically.</error>');
			exit(1);
		}
		$logfile = shell_exec("svn log -r $mergeFrom:HEAD --xml " . TIKISVN . $this->branch . ' 2>&1');
		$logfile = simplexml_load_string($logfile, null, LIBXML_NOCDATA);
		$logfile = json_encode($logfile);
		$logfile = json_decode($logfile, true);
		$logfile = $logfile['logentry'];
		$commitCount = count($logfile);


		if ($commitCount === 0) {
			$output->writeln('<info>No changes to merge</info>');
			exit(0);
		}

		foreach ($logfile as $log) {
			$authors[] = $log['author'];
			$authors = array_unique($authors);
			$commitMessages[] = $log['msg'];
		}

		$authors = implode(', ', $authors);
		$lastRevision = '';
		$separator = '';

		if ($commitCount > 1 ) {
			$separator = ':';
			$lastRevision = $logfile[($commitCount - 1)]['@attributes']['revision'];
			$commitCount = "$commitCount commits from ";
			$authors .= "\n";
		} else {
			$commitCount = '';
			$authors = $logfile[0]['author'];
		}

		$commitMessages = implode("\n", $commitMessages);

		$firstRevision = $logfile[0]['@attributes']['revision'];

		passthru('svn merge '. escapeshellarg(TIKISVN . $this->branch) . " -r$mergeFrom$separator$lastRevision");

		$conflicts = get_conflicts('.');
		$conflictMessage = '';
		if ($conflicts->length > 0) {
			$errorMessage = 'Conflicts occurred during the merge. Fix the conflicts and start again.';
			foreach ($conflicts as $path) {
				$path = $path->parentNode->getAttribute('path');
				$errorMessage .= "\n\t$path";
			}

			$output->writeln('<error>' . $errorMessage . '</error>');
		$conflictMessage = '- With Conflicts - ';
		}

		$output->writeln('<info>After verifications, commit using `svn ci -F svn-commit.tmp`</info>');

		$message = "[MRG][fp/r$firstRevision$separator$lastRevision] $conflictMessage$commitCount$authors$commitMessages";

		file_put_contents('svn-commit.tmp', $message);


	}

	private function findLastMerge(): int
	{
		$short = preg_quote($this->branch, '/');
		echo $short;
		die();
		$pattern = "/^\\[(MRG|BRANCH)\\].*$short'?\s+\d+\s+to\s+(\d+)/";

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

				if (preg_match($pattern, $line, $parts)) {
					$rev = (int) $parts[2];
					break;
				}
				$c++;
				if ($c > 100000) {
					error("[MRG] or [BRANCH] message for '$this->branch' not found in 1000000 lines of logs, something has gone wrong...");
					break;
				}
			}

			fclose($fp);
			proc_close($process);
		}

		return $rev;
	}

	private function uncommittedChanges() : bool
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
	 * @param $full
	 * @return string
	 */
	function short($full)
	{
		return substr($full, strlen(TIKISVN) + 1);
	}
}
