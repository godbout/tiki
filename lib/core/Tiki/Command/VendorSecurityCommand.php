<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: OCRAllCommand.php 69958 2019-05-10 18:32:17Z drsassafras $

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\Console\Input\InputOption;
use Tiki\Package\ComposerManager;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;

class VendorSecurityCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('dev:vendorcheck')
			->setDescription('Check vendor files against known security issues.')
			->addOption(
				'packages',
				'p',
				InputOption::VALUE_REQUIRED,
				'Check package file dependencies? (y or n)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $tikipath;

		// die gracefully if shell_exec is not enabled;
		if (! is_callable('shell_exec')) {
			$output->writeln('<error>shell_exec must be enabled</error>');
			return;
		}
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);


		$usePackages = $input->getOption('packages');

		if (! empty($usePackages)) {
			$usePackages = strtolower($usePackages);

			if ($usePackages !== 'y' || $usePackages !== 'n') {
				$help = new HelpCommand();
				$help->setCommand($this);
				$help->run($input, $output);
				$output->writeln(
					'<error>Must specify a valid option for package option. (y or n)</error>'
				);
				return;
			}
		} else {
			$usePackages = '';
		}

		$composerManager = new ComposerManager($tikipath);
		$availableComposerPackages = $composerManager->getAvailable(true, true);
		$packageCount = count($availableComposerPackages);
		if (! $packageCount){
			$usePackages = 'n';
		}

		if (empty($usePackages)) {
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Install and check package dependencies? (y or n) ', true);

			if ($helper->ask($input, $output, $question)) {
				$usePackages = 'y';
			}
		}

		if ($usePackages === 'y') {
			if (! $composerManager->composerIsAvailable()) {
				$output->writeln('<error>Composer is not available</error>');
			}

			$progress = new ProgressBar($output, $packageCount);
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$progress->setOverwrite(false);
			}
			$progress->setFormatDefinition(
				'custom', ' %current%/%max% [%bar%] -- %message%'
			);
			$progress->setFormat('custom');
			$progress->setMessage('Starting package installation');
			$progress->start();


			foreach ($availableComposerPackages as $package) {
				$progress->setmessage('Installing ' . $package['key']);
				$progress->advance();
				$output->writeln(shell_exec('php console.php package:install ' . $package['key'] . '  2>&1'),OutputInterface::VERBOSITY_DEBUG);
			}
			echo "\n";
		}

		$checker = new SecurityChecker();
		$result = $checker->check('vendor_bundled/composer.lock', 'text');

		echo $result . "\n";

		$result = $checker->check('composer.lock', 'text');

		echo $result . "\n";


	}
}
