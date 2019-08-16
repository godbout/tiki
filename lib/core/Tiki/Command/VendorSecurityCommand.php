<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: OCRAllCommand.php 69958 2019-05-10 18:32:17Z drsassafras $

namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\Console\Input\InputOption;
use Tiki\Package\ComposerManager;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Command\HelpCommand;

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
				'Install package file dependencies? Useful for automated scripts. Valid option = (y or n)'
			);
	}


	/**
	 * Formats and displays security advisories, also looks up useful information (such as dependents)
	 * @param OutputInterface $output		Symfony output for displaying text.
	 * @param array           $vendors		Vendors and security advisories to render
	 * @param string          $workingDir	If the composer working dir is outside the root dir, specify
	 */
	protected function renderAdvisories (OutputInterface &$output, array $vendors, string $workingDir = ''): void
	{
		if (empty($vendors)) {
			$output->writeln('No Advisories');
			return;
		}

		foreach ($vendors as $vendorName => $vendor) {
			if ($workingDir) {
				$workingDir = ' --working-dir="' . $workingDir . '"';
			}
			$command = 'php temp/composer.phar depends ' . $vendorName . ' ' . $vendor['version'] . $workingDir . '  2>&1';
			$command = trim(shell_exec($command));
			$command = preg_replace('/(?<=requires.\s).*(?=\s*\()/mU', '<comment>$0 ' . $vendor['version'] . '</comment>', $command, 1);
			$output->writeln($command);
			foreach ($vendor['advisories'] as $advisory) {
				$advisory['title'] = preg_replace('/' . $advisory['cve'] . '[:\s]*/m', '', $advisory['title'], 1);
				$output->writeln($advisory['cve'] . ' - ' . $advisory['title'] . ' - ' . $advisory['link']);
			}
			$output->writeln('');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		global $tikipath;

		// die gracefully if shell_exec is not enabled;
		if (! is_callable('shell_exec')) {
			$output->writeln('<error>shell_exec must be enabled</error>');
			return;
		}
		// remove horrible red backgrounds from errors
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);


		// check if packages installation has been specified via command option
		$usePackages = $input->getOption('packages');
		if (! empty($usePackages)) {
			$usePackages = strtolower($usePackages);

			// check for valid command option inputs
			if ($usePackages !== 'y' && $usePackages !== 'n') {
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

		// if all packages are installed, don't prompt to install them
		$composerManager = new ComposerManager($tikipath);
		$availableComposerPackages = $composerManager->getAvailable(true, true);
		$packageCount = count($availableComposerPackages);
		if (! $packageCount) {
			$usePackages = 'n';
		}

		// prompt to install packages
		if (empty($usePackages)) {
			$output->writeln('Packages must be installed before they can be checked.', OutputInterface::VERBOSITY_VERBOSE);
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Install and check package dependencies? This may take a while. (<comment>y</comment> or <comment>n</comment>) ', false);

			if ($helper->ask($input, $output, $question)) {
				$usePackages = 'y';
			}
		}

		// if wer are installing packages, then do so.
		if ($usePackages === 'y') {
			if (! $composerManager->composerIsAvailable()) {
				$output->writeln('<error>Composer is not available</error>');
			}

			$progress = new ProgressBar($output, $packageCount);
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$progress->setOverwrite(false);
			}
			$progress->setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
			$progress->setFormat('custom');
			$progress->setMessage('Starting package installation');
			$progress->start();


			foreach ($availableComposerPackages as $package) {
				$progress->setmessage('Installing ' . $package['key']);
				$progress->advance();
				$output->writeln(shell_exec('php console.php package:install ' . $package['key'] . '  2>&1'), OutputInterface::VERBOSITY_DEBUG);
			}
			$output->writeln('');
		}

		$checker = new SecurityChecker();
		$lockFile = 'vendor_bundled/composer.lock';
		try {
			$alerts = $checker->check($lockFile, 'json');
		} catch (Exception $e) {
			$output->writeln('<error>Could not fetch security advisories</error>');
			$output->writeln('<comment>Error message:</comment> ' . $e->getMessage());
			return;
		}
		$alerts = json_decode((string)$alerts, true);
		$output->writeln('<info>Tiki Vendor Advisories</info>');
		$this->renderAdvisories($output, $alerts, 'vendor_bundled');

		$lockFile = 'composer.lock';
		// check if packages lockfile exists
		if (is_readable($lockFile)) {
			$installedCount = count($composerManager->getInstalled());
			$availableComposerPackages = $composerManager->getAvailable(true, true);
			$totalCount = $installedCount + count($availableComposerPackages);
			$output->writeln("<info>Tiki Package Advisories ($installedCount of $totalCount checked)</info>");
			if ($availableComposerPackages) {
				$output->write('<comment>Packages not evaluated:</comment> ');
				foreach ($availableComposerPackages as $package) {
					$output->write($package['key'] . ' ');
				}
				$output->writeln('& the dependencies thereof');
			}
			try {
				$alerts = $checker->check($lockFile, 'json');
			} catch (Exception $e) {
				$output->writeln('<error>Could not fetch security advisories</error>');
				$output->writeln('<comment>Error message:</comment> ' . $e->getMessage());
				return;
			}
			$alerts = json_decode((string)$alerts, true);
			$this->renderAdvisories($output, $alerts);
		}
	}
}
