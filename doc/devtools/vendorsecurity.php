#!/usr/bin/php
<?php
// (c) Copyright 2002-2018 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: svnup.php 65474 2018-02-07 15:15:28Z fmg-sf $

namespace Tiki\Command;

use Error;
use function file_exists;
use function shell_exec;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

if (isset($_SERVER['REQUEST_METHOD'])) {
	die('Only available through command-line.');
}

//TODO check vendor files managed by Tiki Packages.
//TODO check against all CVE's and against non-PHP dependents.
//TODO find more comprehensive solution for automated checks

$tikiBase = realpath(dirname(__FILE__) . '/../..');

try{
	require_once $tikiBase . '/vendor_bundled/vendor/autoload.php';
} catch (Error $e) {
	echo 'Error: ',  $e->getMessage(), "\n";
}

chdir($tikiBase);


/**
 * Checks for known vulnerabilities in Tiki vendor dependencies
 * Uses https://security.sensiolabs.org/api to check against
 *
 * Class VendorSecurityCommand
 * @package Tiki\Command
 */


class VendorSecurityCommand extends Command
{

	protected function configure()
	{
		$this
			->setName('vendorsecurity')
			->setDescription("Checks for known vulnerabilities in Tiki vendor dependencies.")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (file_exists('vendor_bundled/composer.lock')) {
			$output->writeln(shell_exec('curl -sH "Accept: text/plain" https://security.sensiolabs.org/check_lock -F lock=@vendor_bundled/composer.lock'));
		} else {
			$output->writeln('Error: No composer.lock file');
		}
	}
}

// create the application and new console
$console = new Application;
$console->add(new VendorSecurityCommand);
$console->setDefaultCommand('vendorsecurity');
$console->run();

