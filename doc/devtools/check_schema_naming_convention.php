<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace TikiDevTools;

use DateTime;

/*
 *  Script to check name convention of schema files
 *  Available options:
 *    -p and --path: folder path to check naming convention (default: ../../installer/schema)
 *    -o and --output: path to save check result
 */

class CheckSchemaNamingConvention
{
	protected $exceptions = [
		'.',
		'..',
		'index.php',
		'00000000_schema_change_tiki.sql',
		'999999991_decode_pages_sources_tiki.php',
		'99999999_image_plugins_kill_tiki.php',
		'optional_20170801_initialize_article_nbreads_tiki.php',
	];

	/**
	 * Execute check
	 */
	public function execute()
	{
		$schemaFolderPath = $this->getPath();
		if (! file_exists($schemaFolderPath)) {
			echo "\033[0;31mThe folder to check does not exist\033[0m" . PHP_EOL;
			exit(1);
		}
		$errorCount = $this->checkFolder($schemaFolderPath);
		if ($errorCount > 0) {
			exit(1);
		}
	}

	/**
	 * Check for command line options
	 *
	 * @return array
	 */
	protected function getOpts()
	{
		$shortOpts = "p:o:";
		$longOpts = [
			"path:",
			"output",
		];
		$options = getopt($shortOpts, $longOpts);
		return ($options);
	}

	/**
	 * Get path to check
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$schemaFolderPath = '../../installer/schema';
		$options = $this->getOpts();
		if (! empty($options['p'])) {
			$schemaFolderPath = $options['p'];
		} elseif (! empty($options['path'])) {
			$schemaFolderPath = $options['path'];
		}
		if ($schemaFolderPath[0] !== '/') {
			$schemaFolderPath = __DIR__ . DIRECTORY_SEPARATOR . $schemaFolderPath;
		}
		return $schemaFolderPath;
	}

	/**
	 * Check filename
	 *
	 * @param $filename
	 * @return bool
	 */
	protected function checkFilename($filename)
	{
		$firstUlPos = strpos($filename, "_");
		if ($firstUlPos === false) {
			return false;
		}
		$lastUlPos = strrpos($filename, "_");

		$datepart = substr($filename, 0, $firstUlPos);
		$date = DateTime::createFromFormat('Ymd', $datepart);
		if ($date === false or $date->format('Ymd') !== $datepart) {
			return false;
		}

		$tikipart = substr($filename, $lastUlPos + 1);
		if ($tikipart !== 'tiki') {
			return false;
		}

		return true;
	}

	protected function printMessage($message, $outputPath = null)
	{
		echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
		if (! empty($outputPath)) {
			file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * verify the schema folder
	 * @param $path
	 * @return int
	 */
	protected function checkFolder($path)
	{
		echo "\033[0;32mChecking started...\033[0m" . PHP_EOL;


		$options = $this->getOpts();
		$outputPath = null;
		if (! empty($options['o'])) {
			$outputPath = $options['o'];
		} elseif (! empty($options['output'])) {
			$outputPath = $options['output'];
		}
		if (isset($outputPath)) {
			file_put_contents($outputPath, "");
		}

		$filenameList = scandir($path);
		if ($filenameList === false) {
			echo "\033[0;31mScandir failed on specified folder\033[0m" . PHP_EOL;
			return 1;
		}

		$errorCount = 0;
		foreach ($filenameList as $filename) {
			if (in_array($filename, $this->exceptions)) {
				continue;
			}

			$ext = substr($filename, -4);
			if ($ext === ".sql" || $ext === ".php") {
				$onlyName = substr($filename, 0, strlen($filename) - 4);
				if (strlen($onlyName) > 100) { // schema for the field is varchar(100)
					$this->printMessage(
						$filename . ": the name, without extension need to smaller that 100 chars",
						$outputPath
					);
					$errorCount++;
				} elseif (! $this->checkFilename($onlyName)) {
					$this->printMessage($filename . ": invalid naming convention", $outputPath);
					$errorCount++;
				}
			} else {
				$this->printMessage($filename . ": unknown file in the schema folder", $outputPath);
				$errorCount++;
			}
		}

		echo "\033[0;31m" . $errorCount . " errors found\033[0m" . PHP_EOL;
		echo "\033[0;32mCompleted\033[0m" . PHP_EOL;
		return $errorCount;
	}
}

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
	die("Please run from a shell");
}

$checker = new CheckSchemaNamingConvention();
$checker->execute();
