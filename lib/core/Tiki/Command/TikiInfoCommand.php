<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TikiInfoCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('tiki:info')
			->setDescription('Displays the Tiki and/or PHP version')
			->addArgument(
				'tiki_php',
				InputArgument::OPTIONAL,
				tr('Displays the Tiki (tiki) or PHP version (php), empty to display both')
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$tikiPhpArgument = $input->getArgument('tiki_php');

		$TWV = new \TWVersion();
		$tikiVersion = $TWV->version;
		$phpVersion = phpversion();

		if (empty($tikiPhpArgument)) {
			$output->writeln("<info>PHP version: " . $phpVersion . "</info>");
			$output->writeln("<info>Tiki version: " . $tikiVersion . "</info>");
		} elseif ($tikiPhpArgument == 'php') {
			$output->writeln("<info>" . $phpVersion . "</info>");
		} elseif ($tikiPhpArgument == 'tiki') {
			$output->writeln("<info>" . $tikiVersion . "</info>");
		} else {
			$output->writeln("<info>Unknown argument '" . $tikiPhpArgument . "'</info>");
		}
	}
}
