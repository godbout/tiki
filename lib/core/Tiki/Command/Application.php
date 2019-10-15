<?php

namespace Tiki\Command;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
	/**
	 * Gets the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(new InputOption('--site', '', InputOption::VALUE_REQUIRED, 'Multi-Tiki instance'));
		$definition->addOption(new InputOption('--as-user', '', InputOption::VALUE_REQUIRED, 'Run the command as a different Tiki user'));

		return $definition;
	}

	/**
	 * Runs the current command.
	 *
	 * Calls the parent method then prints all Tiki Feedback (errors and messages) to the console
	 *
	 * @param Command         $command
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int 0 if everything went fine, or an error code
	 * @throws \Throwable
	 */
	protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
	{
		$exitCode = parent::doRunCommand($command, $input, $output);

		\Feedback::printToConsole($output);

		return $exitCode;
	}
}
