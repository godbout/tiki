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
use Symfony\Component\Console\Helper\ProgressBar;
use Exception;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;


class OCRAllCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('ocr:all')
			->setDescription('OCR all queued files');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$ocrLib = \TikiLib::lib('ocr');
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);

		if (! $ocrLib->checkOCRDependencies()) {
			$output->writeln(
				'<error>' . tr('Dependencies not satisfied. Exiting.')
				. '</error>'
			);
			return;
		}

		//Retrieve the number of files marked as waiting to be processed.
		$queueCount = $ocrLib->table('tiki_files')->fetchCount(
			['ocr_state' => $ocrLib::OCR_STATUS_PENDING]
		);

		$progress = new ProgressBar($output, $queueCount + 1);
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$progress->setOverwrite(false);
		}
		$progress->setFormatDefinition(
			'custom', ' %current%/%max% [%bar%] -- %message%'
		);
		$progress->setFormat('custom');
		$progress->setMessage('Preparatory checks');
		$progress->start();
		$OCRCount = 0;

		// release old files that might have died while processing, and report as error
		$processingNum = $ocrLib->releaseAllProcessing();
		if ($processingNum) {
			$progress->setMessage(
				"WARNING: there was $processingNum files that had not previously finished."
			);
			$progress->setMessage(
				"<comment>Reset processing files, run again to perform OCR.</comment>\n"
			);
			$progress->finish();
			return;
		}

		// Set $nextOCRFile with the fileid of the next file scheduled to be processed by the OCR engine.
		$ocrLib->nextOCRFile = $ocrLib->table('tiki_files')->fetchOne(
			'fileId', ['ocr_state' => $ocrLib::OCR_STATUS_PENDING]
		);

		if (! $ocrLib->nextOCRFile) {
			$progress->setMessage("<comment>No files to OCR</comment>\n");
			$progress->finish();
			return;
		}

		while ($ocrLib->nextOCRFile) {
			try {
				$progress->setMessage(
					'OCR processing file gallery id ' . $ocrLib->nextOCRFile . ': '
				);
				$progress->advance();
				$ocrLib->OCRfile();
				$output->write('done');
				$OCRCount++;
			} catch (Exception $e) {
				$output->writeln('<error>' . $e->getMessage(). '</error>');
			}
		}
		$progress->setMessage(
			"<comment>Finished the OCR of $OCRCount files.</comment>\n"
		);
		$progress->finish();
	}
}
