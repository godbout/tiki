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
use TikiFilter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
				InputArgument::REQUIRED,
				'Specify a file id, or a range to mark. eg. (12 or 90:92)');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$ocrLib = \TikiLib::lib('ocr');
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);
		$task = $input->getArgument('Queue or Skip');
		$task = strtolower($task);

		if ($task[0] === 'q'){
			$state = ['ocr_state' => $ocrLib::OCR_STATUS_PENDING];
		}elseif ($task[0] === 's'){
			$state = ['ocr_state' => $ocrLib::OCR_STATUS_SKIP];
		}else{
			$output->writeln('<error>Must specify a valid option. Use Q to Queue files or S to Skip Files.</error>');
			return;
		}

		$range = TikiFilter::get('digitscolons')->filter($input->getArgument('File ID'));
		$range = explode(':',$range);
		$optionsCount = count($range);
		$update = $ocrLib->table('tiki_files');

		if ($optionsCount === 1) {
			$updated = $update->update(
				$state,
				['fileId' => $range[0]]);
			if ($updated->numrows()) {
				$output->writeln("<comment>File status updated</comment>");
			}else{
				$output->writeln("<error>File could not be updated</error>");
			}
		} elseif (($optionsCount === 2)){
			$updated = $update->updateMultiple(
				$state,
				['fileId' => $update->between($range)]);
			$output->writeln('<comment>Status of ' . $updated->numrows() . ' files updated.</comment>');
		} else {
			$output->writeln('<error>Must specify a valid File ID or File ID Range eg. (12 or 90:92)</error>');
		}
	}
}