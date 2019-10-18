<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Language_CollectFiles;
use Language_FileType_Php;
use Language_FileType_Tpl;
use Language_GetStrings;
use Language_WriteFile_Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use timer;

/**
 * @package Tiki\Command
 *
 * Update lang/xx/language.php files
 *
 * Scans a directory (its files) and a set of (individual) files
 * By default, the directory scanned is the Tiki root, excluding $excludeDirs. By default, the individual files scanned are files in these otherwise excluded directories.
 *
 * Examples:
 * 		- http://localhost/pathToTiki/get_strings.php -> update all language.php files
 * 		- http://localhost/pathToTiki/get_strings.php?lang=fr -> update just lang/fr/language.php file
 * 		- http://localhost/pathToTiki/get_strings.php?lang[]=fr&lang[]=pt-br&outputFiles -> update both French
 * 		  and Brazilian Portuguese language.php files and for each string add a line with
 * 		  the file where it was found.
 *
 * Command line examples:
 * 		- php get_strings.php
 * 		- php get_strings.php lang=pt-br outputFiles=true
 *
 * 		Only scan lib/, and only part of lib/ (exclude lib/core/Zend and lib/captcha), but still include captchalib.php and index.php
 * 		This FAILS as of 2017-09-15, since the language files (for output) are looked for in baseDir.
 * 		- php get_strings.php baseDir=lib/ excludeDirs=lib/core/Zend,lib/captcha includeFiles=captchalib.php,index.php fileName=language_r.php
 *
 *
 */

class GetStringsCommand extends Command
{
	protected function configure(): void
	{
		$this
			->setName('translation:getstrings')
			->setDescription('Update language.php files with new strings')
			->setHelp('Scans all Tiki files and adds new English strings to language files. Also reorganizes existing strings.')
			->addOption(
				'lang',
				'l',
				InputOption::VALUE_OPTIONAL,
				'Language code to process eg. lang=pt-br'
			)
			->addOption(
				'outputfiles',
				null,
				InputOption::VALUE_NONE,
				'For each string add a line with the file where it was found'
			)
			->addOption(
				'exclude',
				null,
				InputOption::VALUE_OPTIONAL,
				'Directories that should be excluded from searching'
			)
			->addOption(
				'include',
				null,
				InputOption::VALUE_OPTIONAL,
				'Individual files that should be included in otherwise excluded directories'
			)
			->addOption(
				'basedir',
				null,
				InputOption::VALUE_OPTIONAL,
				'The base directory to use. Will invalidate default exclude and include parameters'
			)
			->addOption(
				'filename',
				null,
				InputOption::VALUE_OPTIONAL,
				'eg. filename=language_r.php'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$timer = new timer();
		$timer->start();

		$options = [];
		$options['lang'] = $input->getOption('lang') ?: null;
		$options['outputFiles'] = $input->getOption('outputfiles') ?: null;
		$excludeDirs = ['dump', 'img', 'lang', 'bin', 'installer/schema', 'vendor_bundled', 'vendor', 'vendor_extra', 'vendor_custom', 'lib/test', 'temp', 'permissioncheck', 'storage', 'tiki_tests',
						'doc', 'db', 'lib/openlayers', 'tests', 'modules/cache'];
		$excludeDirs = array_filter($excludeDirs, 'is_dir'); // only keep in the exclude list if the dir exists

		// Files are processed after the base directory, so adding a file here allows to scan it even if its directory was excluded.
		$includeFiles = ['./lang/langmapping.php', './img/flags/flagnames.php'];

		if ($input->getOption('basedir')) {
			$options['baseDir'] = $input->getOption('basedir');

			// when a custom base dir is set, default $includeFiles and $excludeDirs are not used
			$includeFiles = [];
			$excludeDirs = [];
		}
		if ($input->getOption('exclude')) {
			$excludeDirs = explode(',', $input->getOption('exclude'));
		}
		if ($input->getOption('include')) {
			$includeFiles = explode(',', $input->getOption('include'));
		}
		$options['fileName'] = $input->getOption('filename') ?: null;

		$getStrings = new Language_GetStrings(new Language_CollectFiles, new Language_WriteFile_Factory, $options);

		$getStrings->addFileType(new Language_FileType_Php);
		$getStrings->addFileType(new Language_FileType_Tpl);

		// skip the following directories
		$getStrings->collectFiles->setExcludeDirs($excludeDirs);

		// manually add the following files from skipped directories
		$getStrings->collectFiles->setIncludeFiles($includeFiles);

		$langs = $getStrings->getLanguages();
		sort($langs);
		$output->writeln(count($langs) . ' Languages: ' . implode(' ', $langs));

		$getStrings->run();

		$output->writeln('Total time spent: ' . $timer->stop() . ' seconds');
		$output->writeln('<comment>You may now review and commit</comment>');
		$output->writeln(
			'<info>Warning: Committing the results of getstrings will prevent identifying broken strings with translation:englishupdate so englishupdate should be run first to prevent gradual translation loss. See englishupdate help for details.</info>'
		);
	}
}
