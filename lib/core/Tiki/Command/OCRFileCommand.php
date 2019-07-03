<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\HelpCommand;
use Exception;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OCRFileCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('ocr:file')
			->setDescription(
				'Attempt to OCR a file. Defaults to queued OCR job'
			)
			->addOption(
				'file-gal-id',
				'f',
				InputOption::VALUE_REQUIRED,
				'File gallery ID of the file to OCR.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$ocrLib = \TikiLib::lib('ocr');
		$outputStyle = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $outputStyle);

		// Set $nextOCRFile with the fileid of the next file scheduled to be processed by the OCR engine.
		$ocrLib->nextOCRFile = $ocrLib->table('tiki_files')->fetchOne('fileId', ['ocr_state' => $ocrLib::OCR_STATUS_PENDING]);

		$fgalId = $input->getOption('file-gal-id');
		if ($fgalId) {
			if (preg_match('/^\d+$/', $fgalId)) {
				$ocrLib->nextOCRFile = (int)$fgalId;
			} else {
				$help = new HelpCommand();
				$help->setCommand($this);
				$help->run($input, $output);
				$output->writeln(
					"<error>file-gal-id must be an int, $fgalId is an illegal value."
				);
				exit;
			}
		}

		if (! $ocrLib->nextOCRFile) {
			$output->writeln('<comment>No files to OCR</comment>');
			exit;
		}

		try {
			$ocrLib->checkFileGalID();
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			exit;
		}

		if (! $ocrLib->checkOCRDependencies()) {
			$output->writeln(
				'<error>' . tr('Dependencies not satisfied. Exiting.')
				. '</error>'
			);
		}

		try {
			$output->writeln('<comment>' . $ocrLib->OCRfile() . '</comment>');
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}
}
