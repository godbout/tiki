<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU SCSSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ParserException;
use Leafo\ScssPhp\Exception\CompilerException;
use Leafo\ScssPhp\Exception\RangeException;
use Leafo\ScssPhp\Exception\ServerException;

class ScssCompileCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('scss:compile')
			->setDescription('Compile SCSS theme files into CSS')
			->addArgument(
				'themes',
				InputArgument::OPTIONAL,
				'Comma separateed list of themes (and/or base_files) to compile - omit to compile all',
				''
			)
			->addOption(
				'location',
				'l',
				InputOption::VALUE_NONE,
				'Location of scss files to compile (themes)'
			)
			->addOption(
				'without-options',
				null,
				InputOption::VALUE_NONE,
				'Do not compile the theme options if present'
			)
			->addOption(
				'check-timestamps',
				't',
				InputOption::VALUE_NONE,
				'Compare the modification timesof the SCSS and CSS files before compiling (does not check for included SCSS files)'
			);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$only = array_filter(explode(',', $input->getArgument('themes')));
		$all = empty($only);

		$location = $input->getOption('location');
		if (empty($location)) {
			$location = 'themes';
		}

		$checkTimestamps = $input->getOption('check-timestamps');

		$cachelib = \TikiLib::lib('cache');

		$output->writeln('Compiling scss files from themes');
		foreach (new \DirectoryIterator('themes') as $fileInfo) {
			if ($fileInfo->isDot() || ! $fileInfo->isDir()) {
				continue;
			}
			$themename = $fileInfo->getFilename();
			if (! empty($only) && ! in_array($themename, $only) && ! $all) {
				continue;
			}
			$files = [];

			if ($themename === 'base_files') {
				$scss_file = "$location/$themename/scss/tiki_base.scss";
				$css_file = "$location/$themename/css/tiki_base.css";
			} else {
				$scss_file = "$location/$themename/scss/$themename.scss";
				$css_file = "$location/$themename/css/$themename.css";
			}
			if (file_exists($scss_file) && (! file_exists($css_file) || ! $checkTimestamps || filemtime($css_file) < filemtime($scss_file))) {
				$files[] = ['scss' => $scss_file, 'css' => $css_file];
			}

			$scss_file = "$location/$themename/scss/newsletter.scss";
			$css_file = "$location/$themename/css/newsletter.css";
			if (file_exists($scss_file) && (! file_exists($css_file) || ! $checkTimestamps || filemtime($css_file) < filemtime($scss_file))) {
				$files[] = ['scss' => $scss_file, 'css' => $css_file];
			}

			if (! $input->getOption('without-options') && is_dir("$location/$themename/options")) {
				foreach (new \DirectoryIterator("$location/$themename/options") as $fileInfo2) {
					if ($fileInfo2->isDot() || ! $fileInfo2->isDir()) {
						continue;
					}
					$optionname = $fileInfo2->getFilename();
					$scss_file = "$location/$themename/options/$optionname/scss/$optionname.scss";
					$css_file = "$location/$themename/options/$optionname/css/$optionname.css";
					if (file_exists($scss_file) && (! file_exists($css_file) || ! $checkTimestamps || filemtime($css_file) < filemtime($scss_file))) {
						$files[] = ['scss' => $scss_file, 'css' => $css_file];
					}
				}
			}

			try {
				foreach ($files as $file) {
					$this->compile($file['scss'], $file['css'], $output);
				}
			} catch (ParserException $e) {
				$output->writeln('<error>' . tr('SCSS Parse Error') . ' compiling: ' . $scss_file . '</error>');
				$output->writeln('<info>' . $e->getMessage() . '</info>');
				return false;
			} catch (CompilerException $e) {
				$output->writeln('<error>' . tr('SCSS Compiler Error') . ' compiling: ' . $scss_file . '</error>');
				$output->writeln('<info>' . $e->getMessage() . '</info>');
				return false;
			} catch (RangeException $e) {
				$output->writeln('<error>' . tr('SCSS Range Error') . ' compiling: ' . $scss_file . '</error>');
				$output->writeln('<info>' . $e->getMessage() . '</info>');
				return false;
			} catch (ServerException $e) {
				$output->writeln('<error>' . tr('SCSS Server Error') . ' compiling: ' . $scss_file . '</error>');
				$output->writeln('<info>' . $e->getMessage() . '</info>');
				return false;
			} catch (\Exception $e) {
				$output->writeln('<error>' . tr('SCSS Error') . ' compiling: ' . $scss_file . '</error>');
				$output->writeln('<info>' . $e->getMessage() . '</info>');
				return false;
			}
		}

		$output->writeln('Clearing all caches');
		$cachelib->empty_cache();
	}

	/**
	 * @param $inputFile
	 * @param string $outputFile
	 * @param OutputInterface $output
	 */
	protected function compile($inputFile, $outputFile = '', $output = null)
	{
		$data = file_get_contents($inputFile);

		$newWorkingDir = dirname(realpath($inputFile));
		$oldWorkingDir = getcwd();

		if ($oldWorkingDir !== $newWorkingDir) {
			$changeDir = chdir($newWorkingDir);
			$inputFile = basename($inputFile);
		} else {
			$changeDir = '';
		}

		$scss = new Compiler();

		$result = $scss->compile($data, $inputFile);

		if ($changeDir) {
			chdir($oldWorkingDir);
		}

		if ($outputFile) {
			file_put_contents($outputFile, $result);
		}
	}
}
