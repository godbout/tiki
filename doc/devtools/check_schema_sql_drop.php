<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace TikiDevTools;

/**
 *  Script to check sql CREATE statements have got DROP IF EXIST in front of it
 *  Available commands:
 *   -p and --path: sql file path to check (default: ../../db/tiki.sql)
 *   -f: flag to fix or not
 *   -o and --output: fine path to save check result
 */
class CheckSchemaSqlDrop
{
	/**
	 * Execute check
	 */
	public function execute()
	{
		$sqlPath = $this->getPath();
		if (! file_exists($sqlPath)) {
			$this->printMessageError('The sql file to check does not exist');
			echo "\033[0;31m\033[0m" . PHP_EOL;
			return 1;
		}
		return $this->checkFile($sqlPath);
	}

	/**
	 * Print a normal message
	 *
	 * @param $message
	 * @param null $outputPath
	 */
	protected function printMessage($message, $outputPath = null)
	{
		echo "\033[0;32m" . $message . "\033[0m" . PHP_EOL;
		if (! empty($outputPath)) {
			file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * Print an error message
	 *
	 * @param $message
	 * @param null $outputPath
	 */
	protected function printMessageError($message, $outputPath = null)
	{
		echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
		if (! empty($outputPath)) {
			file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * Get the options from command line
	 *
	 * @return array
	 */
	protected function getOpts()
	{
		$shortOpts = "p:o:f::";
		$longOpts = [
			"path:",
			"output",
		];
		$options = getopt($shortOpts, $longOpts);
		return ($options);
	}

	/**
	 * get the path of the schema file
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$sqlPath = '../../db/tiki.sql';
		$options = $this->getOpts();
		if (! empty($options['p'])) {
			$sqlPath = $options['p'];
		} elseif (! empty($options['path'])) {
			$sqlPath = $options['path'];
		}
		if ($sqlPath[0] !== '/') {
			$sqlPath = __DIR__ . DIRECTORY_SEPARATOR . $sqlPath;
		}
		return $sqlPath;
	}

	/**
	 * Validates the file
	 *
	 * @param $path
	 * @return int
	 */
	protected function checkFile($path)
	{
		$sqlFile = file_get_contents($path);

		if (! $sqlFile) {
			$this->printMessageError('Unable to open file');
			return 1;
		}

		$this->printMessage('Checking started...');
		$options = $this->getOpts();
		$shouldFix = isset($options['f']);
		if ($shouldFix) {
			$this->printMessage('Will fix problems automatically if exist');
		} else {
			$this->printMessage('Just check and output problems');
		}

		$outputPath = null;
		if (! empty($options['o'])) {
			$outputPath = $options['o'];
		} elseif (! empty($options['output'])) {
			$outputPath = $options['output'];
		}
		if (isset($outputPath)) {
			file_put_contents($outputPath, "");
		}

		$errorCount = 0;
		$queries = explode(';', $sqlFile);
		$queryCount = sizeof($queries);
		for ($i = 1; $i < $queryCount; $i++) {
			$query = preg_replace('/\s*/m', '', $queries[$i]);
			$startPos = stripos($query, "CREATETABLE`");
			if ($startPos !== false) {
				$endPos = strpos($query, "`", $startPos + 12);
				if ($endPos !== false) {
					$tableName = substr($query, $startPos + 12, $endPos - $startPos - 12);
					$prevQuery = preg_replace('/\s*/m', '', $queries[$i - 1]);
					$dropPos = stripos($prevQuery, "DROPTABLEIFEXISTS`" . $tableName . "`");
					if ($dropPos === false) {
						$message = "CREATE TABLE `" . $tableName . "`: missing DROP TABLE IF EXISTS statement";
						$this->printMessageError($message, $outputPath);
						if ($shouldFix) {
							$dropStatement = PHP_EOL . PHP_EOL . "DROP TABLE IF EXISTS `" . $tableName . "`";
							array_splice($queries, $i++, 0, $dropStatement);
							$queryCount++;
						}
						$errorCount++;
					}
				}
			}
		}

		$fixedCount = 0;
		if ($errorCount > 0 and $shouldFix) {
			for ($i = 0; $i < sizeof($queries) - 1; $i++) {
				$curQuery = $queries[$i];
				$nextQuery = $queries[$i + 1];
				if (strpos($curQuery, "DROP TABLE IF EXISTS") !== false
					&& substr($nextQuery, 0, 2) === (PHP_EOL . PHP_EOL)) {
					$queries[++$i] = substr($nextQuery, 1);
				}
			}
			$success = file_put_contents($path, implode(";", $queries));
			if ($success === false) {
				$this->printMessageError('Failed to save fixed content', $outputPath);
			} else {
				$this->printMessage('Saved fixed content', $outputPath);
				$fixedCount = $errorCount;
			}
		}

		$this->printMessageError(
			$errorCount . " errors found" . ($shouldFix ? ", " . $fixedCount . " errors fixed" : "")
		);
		$this->printMessage('Completed');
		return $errorCount - $fixedCount;
	}
}

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
	die("Please run from a shell");
}

$checker = new CheckSchemaSqlDrop();
$errors = $checker->execute();
if ($errors > 0) {
	exit(1);
}
exit(0);
