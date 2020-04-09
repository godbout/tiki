<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


/**
 *
 * This file may be run to fix the Id's of SVN Keyword for all files within tiki.
 *
 * Reads the beginning of each file in tiki, and adds a svn Keyword id if the $Id$ marker is found.
 *
 */

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fix BOM encoding, windows formatting and other invisible weirdness.
 *
 * Uses dos2unix on all Tiki files
 *
 * @package Tiki\Command
 */

class FixBOMandUnixCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('dev:fixbom')
			->setDescription('Fix BOM and line endings for all files')
			->setHelp('Fixes BOM encoding, converts windows to Unix line endings and fixes other invisible weirdness in all Tiki files.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Lets first check that some requirements are met.
		if (! is_callable('exec')) {
			$output->writeln('<error>Must enable exec() for this command</error>');
			exit(1);
		}
		if (! is_callable('shell_exec')) {
			$output->writeln('<error>Must enable shell_exec() for this command</error>');
			exit(1);
		}
		if (! exec('dos2unix --version  2>&1')) {
			$output->writeln('<error>dos2unix must be installed before using this command.</error>');
			$output->writeln('On mac OS you may install this command by typing: brew install dos2unix');
			exit(1);
		}

		$filesUpdated = 0;
		// apply filter only to these file types, excluding any vendor files.
		$files = $this->globRecursive(
			'*',
			GLOB_BRACE,
			'',
			['vendor_', 'vendor/', 'temp/', 'lib/cypht', '.png', '.jpg', '.gif']
		);
		$progress = new ProgressBar($output, count($files));
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$progress->setOverwrite(false);
		}
		$progress::setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
		$progress->setFormat('custom');

		$progress->start();

		foreach ($files as $fileName) {
			$progress->setMessage('Processing ' . $fileName);
			$progress->advance();
			$beforeHash = hash_file('crc32b', $fileName);
			$raw = shell_exec('dos2unix ' . $fileName . ' 2>&1');
			if ($output->isDebug()) {
				$output->writeln($raw);
			}
			if ($beforeHash !== hash_file('crc32b', $fileName)) {
				$filesUpdated++;
			}
		}

		if (! $filesUpdated) {
			$progress->setMessage('<comment>All files look good, no changes made.</comment>');
		} else {
				$progress->setMessage("<comment>$filesUpdated files updated, you may now review and commit.</comment>");
		}
		$progress->finish();
	}

	/**
	 * Recursively calls, glob()
	 *
	 * @param string $pattern
	 * @param int    $flags
	 * @param string $startdir
	 * @param array  $excludes  If this string is found within a directory name, it wont be included
	 *
	 * @return array
	 */

	private function globRecursive($pattern = '*', $flags = 0, $startdir = '', $excludes = [])
	{
		$files = glob($startdir . $pattern, $flags);
		foreach ($files as $key => $fileName) {
			foreach ($excludes as $exclude) {
				if (strpos($fileName, $exclude)) {
					unset($files[$key]);
					break;
				}
			}
		}

		foreach (glob($startdir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) as $dir) {
			$include = true;
			/** If the directory has not been excluded from processing */
			foreach ($excludes as $exclude) {
				if (strpos($dir, $exclude) !== false) {
					$include = false;
					break;
				}
			}
			if ($include) {
				$files = array_merge($files, $this->globRecursive($pattern, $flags, $dir, $excludes));
			}
		}
		return $files;
	}
}
