<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use TikiFilter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Command\HelpCommand;

class OCRSetCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('ocr:set')
			->setDescription('Set the OCR status of files (Queue, Skip)')
			->addArgument(
				'Queue or Skip',
				InputArgument::REQUIRED,
				'Do you want to Queue the files for OCR processing, or mark the files to be skipped. Valid options = (q queue s skip).')
			->addArgument(
				'File ID',
				InputArgument::OPTIONAL,
				'Specify a file id, or a range to filter or omit to match all. eg. (12 or 90:92)')
			->addOption(
				'stalled',
				's',
				InputOption::VALUE_NONE,
				'Filter files so only stalled files are updated')
			->addOption(
				'finished',
				'f',
				InputOption::VALUE_NONE,
				'Filter only files that are already finished to be updated')
			->addOption(
				'processing',
				'p',
				InputOption::VALUE_NONE,
				'Filter only files that are currently being processed to be updated')
			->addOption(
				'queued',
				'u',
				InputOption::VALUE_NONE,
				'Filter only files that are queued for OCR to be updated.')
			->addOption(
				'refrained',
				'r',
				InputOption::VALUE_NONE,
				'Filter only files not marked for OCR processing to be updated')
			->addOption(
				'no-confirm',
				'c',
				InputOption::VALUE_NONE,
				'Prompts to confirm updating files will be skipped. Useful for automated tasks.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$ocrLib = \TikiLib::lib('ocr');
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);

		$optionCount = array_sum([$input->getOption('stalled'),
								  $input->getOption('finished'),
								  $input->getOption('processing'),
								  $input->getOptions('queued'),
								  $input->getOptions('refrained')]);
		$conditions = [];
		$update = $ocrLib->table('tiki_files');
		if ($optionCount > 1) {
			$help = new HelpCommand();
			$help->setCommand($this);
			$help->run($input, $output);
			$output->writeln('<error>You may only specify 1 option</error>');
			return;
		}
		if ($input->getOption('stalled')) {
			$conditions['ocr_state'] = $ocrLib::OCR_STATUS_STALLED;
		} elseif ($input->getOption('finished')) {
			$conditions['ocr_state'] = $ocrLib::OCR_STATUS_FINISHED;
		} elseif ($input->getOption('processing')) {
			$conditions['ocr_state'] = $ocrLib::OCR_STATUS_PROCESSING;
		} elseif ($input->getOption('queued')) {
			$conditions['ocr_state'] = $ocrLib::OCR_STATUS_PENDING;
		} elseif ($input->getOption('refrained')) {
			$conditions['ocr_state'] = $ocrLib::OCR_STATUS_SKIP;
		}


		$task = $input->getArgument('Queue or Skip');
		$task = strtolower($task);

		if ($task[0] === 'q') {
			$state['ocr_state'] = $ocrLib::OCR_STATUS_PENDING;
			$stateCount['ocr_state'] = $update->not($ocrLib::OCR_STATUS_PENDING);
			$task = 'queue';
		} elseif ($task[0] === 's') {
			$state['ocr_state'] = $ocrLib::OCR_STATUS_SKIP;
			$stateCount['ocr_state'] = $update->not($ocrLib::OCR_STATUS_SKIP);
			$task = 'skip';
		} else {
			$help = new HelpCommand();
			$help->setCommand($this);
			$help->run($input, $output);
			$output->writeln('<error>Must specify a valid option. Use Q to Queue files or S to Skip Files.</error>');
			return;
		}
		$range = TikiFilter::get('digitscolons')->filter($input->getArgument('File ID'));
		$range = explode(':', $range);
		sort($range);											// we need lower values first for search results to match

		if (! empty($range[1])) {
			$conditions['fileId'] = $update->between($range);
		} elseif (! empty($range[0])) {
			$conditions['fileId'] = $range[0];
		}

		$fileCount = $update->fetchCount($conditions + $stateCount);
		$helper = $this->getHelper('question');
		if ($fileCount > 1 && ! $input->getOption('no-confirm')) {
			$question = new ConfirmationQuestion("Set OCR status of $fileCount files to '$task'? (y or n) ", false);

			if (! $helper->ask($input, $output, $question)) {
				return;
			}
		}
		$updated = $update->updateMultiple($state, $conditions);
		$numrows = $updated->numrows;
		if (! $updated->numrows) {
			$numrows = '<error>' . $numrows . '</error>';
		}
		$output->writeln('<comment>Status of ' . $numrows . ' files updated.</comment>');
	}
}