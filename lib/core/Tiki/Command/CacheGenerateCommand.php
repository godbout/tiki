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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheGenerateCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('cache:generate')
			->setDescription('Generate Tiki caches')
			->addArgument(
				'cache',
				InputArgument::OPTIONAL,
				'Type of cache to generate (templates, modules, misc, all)',
				'all'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$type = $input->getArgument('cache');

		$cachelib = \TikiLib::lib('cache');

		switch ($type) {
			case 'templates':
				$output->writeln('Generating templates caches');
				$cachelib->generateCache('templates');
				break;
			case 'modules':
				$output->writeln('Generating modules caches');
				$cachelib->generateCache('modules');
				break;
			case 'misc':
				$output->writeln('Generating misc caches');
				$cachelib->generateCache('misc');
				break;
			case 'all':
				$output->writeln('Generating all caches');
				$cachelib->generateCache();
				// Rebuild preference cache also
				\TikiLib::lib('prefs')->rebuildIndex();
				break;
			default:
				$output->writeln('<error>Invalid cache requested.</error>');
				return;
		}

		$output->writeln('Caches generated');
	}
}
