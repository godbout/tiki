<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki CMS Groupware Project
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

class LockInstallerCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('installer:lock')
			->setDescription('Lock the Tiki installer')
			->setHelp('Lock the Tiki installer so that users can\'t destroy the database through the browser');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$out = <<<LOCK
This lock file was created with:

php console.php installer:lock

Please don't remove or rename this file as it would unlock the installer. The
installer allows a user to change or destroy the site’s database through the
browser so it is very important to keep it locked.

LOCK;
		$file='db/lock';
		file_put_contents($file, $out);

		$output->writeln("Wrote $file");
	}
}
