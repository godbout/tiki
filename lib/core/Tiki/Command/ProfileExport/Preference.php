<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Preference extends ObjectWriter
{
	protected function configure()
	{
		$this
			->setName('profile:export:preference')
			->setDescription('Include a preference within the profile definition')
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'Export all preferences'
			)
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'Preference name'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$preference = $input->getArgument('name');
		$all = $input->getOption('all');

		if (! $all && empty($preference)) {
			$output->writeln('<error>' . tra('Not enough arguments (missing: "name" or "--all" options)') . '</error>');
			return false;
		}

		$writer = $this->getProfileWriter($input);

		$prefslib = \TikiLib::lib('prefs');
		$result = $prefslib->exportPreference($writer, $preference, $all);

		if ($result) {
			$writer->save();
		} else {
			$output->writeln("Preference not found: $preference");
		}
	}
}
