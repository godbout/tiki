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

use DOMDocument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fix the Id's of SVN Keyword for all files within Tiki.
 *
 * Reads the beginning of each file in Tiki, and adds a svn Keyword id if the $Id$ marker is found.
 *
 * @package Tiki\Command
 */

class FixSVNKeyIdsCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('vcs:fixids')
			->setDescription("Fix the Id's of SVN Keyword for all files")
			->setHelp("Fix the Id's of SVN Keyword for all files. Reads the beginning of each file in the working copy and if '\$Id:' is found, but a svnkeyword ID is not, then it is added.");
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Lets first check that some requirements are met.
		if (! is_dir('.svn')) {
			$output->writeln('<error>must be running SVN for this to work</error>');
			exit(1);
		}
		if (! is_callable('shell_exec')) {
			$output->writeln('<error>Must enable shell_exec() for this command');
			exit(1);
		}

		require_once('doc/devtools/svntools.php');

		$xml = new DOMDocument;
		$xml->loadXML(shell_exec('svn propget -R svn:keywords --xml'));

		/** The  offset length of the base pathname */
		$pathLen = strlen(TIKI_PATH) + 1;

		$Ids = [];

		foreach ($xml->getElementsByTagName('target') as $target) {
			foreach ($target->getElementsByTagName('property') as $isKey) {
				if ($isKey->getAttribute('name') === 'svn:keywords') {
					$Ids[substr($target->getAttribute('path'), $pathLen)] = $isKey->textContent;
				}
			}
		}
		$matches = 0;
		// apply filter only to these file types, excluding any vendor files.
		foreach ($this->globRecursive(
			'*{.php,.tpl,.sh,.sql,.js,.less,.css,.yml,htaccess}',
			GLOB_BRACE,
			'',
			'vendor_'
		) as $fileName) {
			// if there was no keywords defined in SVN or there is no Id defined in those keywords
			if (! isset($Ids[$fileName]) || ! preg_match('/(^I|\nI)(d$|d\n)/', $Ids[$fileName])) {
				$handle = fopen($fileName, "r");
				$count = 1;
				do {
					$buffer = fgets($handle);
					if (preg_match('/(\/\/ |{\* |\# |\* )\$Id.*\$/', $buffer)) { // match several different comment styles
						$keys = '';
						if (! empty($Ids[$fileName])) {    // if there is preexisting keys, then set them.
							$keys = $Ids[$fileName] . "\n";
						}
						$keys .= "Id";
						shell_exec("svn propset svn:keywords \"$keys\" " . escapeshellarg($fileName));
						$matches++;
						break;
					}
					$count++;
				} while ($count < 13 && $buffer); // search through up to 13 lines of code (no results increasing that)
				fclose($handle);
			}
		}

		if ($matches) {
			$output->writeln("<comment>$matches keywords updated, you may now review and commit.</comment>");
		} else {
			$output->writeln('<comment>All keywords were up to date, no changes made.</comment>');
		}
	}

	/**
	 * Recursively calls, glob()
	 *
	 * @param string $pattern
	 * @param int    $flags
	 * @param string $startdir
	 * @param        $exclude string|bool if this string is found withn a directory name, it wont be included
	 *
	 * @return array
	 */

	private function globRecursive($pattern, $flags = 0, $startdir = '', $exclude = false)
	{
		$files = glob($startdir . $pattern, $flags);
		foreach (glob($startdir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) as $dir) {
			if (strpos($dir, $exclude) === false) {
				$files = array_merge($files, $this->globRecursive($pattern, $flags, $dir, $exclude));
			}
		}
		return $files;
	}
}
