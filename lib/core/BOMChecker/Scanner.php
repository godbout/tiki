<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class BOMChecker_Scanner
{

	// Tiki source folder
	protected $sourceDir = __DIR__ . '/../../../';

	protected $excludeDir = [];
	protected $scanFiles = [];

	protected $scanExtensions = [
		'php',
		'tpl'
	];

	// The number of files scanned.
	protected $scannedFiles = 0;

	// The list of files detected with BOM
	protected $bomFiles = [];

	// The list of files detected without BOM
	protected $withoutBomFiles = [];

	/**
	 * @param string $scanDir The file directory to scan.
	 * @param array $scanExtensions An array with the file extensions to scan for BOM.
	 */
	public function __construct($scanDir = null, $scanExtensions = [], $excludeDir = [], $scanFiles = [])
	{
		if (! empty($scanDir) && is_dir($scanDir)) {
			$this->sourceDir = $scanDir;
		}

		if (is_array($scanExtensions)&&count($scanExtensions)) {
			$this->scanExtensions = $scanExtensions;
		}

		if (! empty($excludeDir)) {
			$this->excludeDir = $excludeDir;
		}

		if (! empty($scanFiles)) {
			$this->scanFiles = $scanFiles;
		}
	}

	/**
	 * Scan the folder for BOM files
	 * @return array
	 *  An array with the path to the BOM detected files.
	 */
	public function scan()
	{
		if (! empty($this->scanFiles)) {
			$this->checkListFiles($this->scanFiles);
		} else {
			$this->checkDir($this->sourceDir);
		}

		return $this->bomFiles;
	}

	/**
	 * Check directory path
	 *
	 * @param string $sourceDir
	 * @return void
	 */
	protected function checkDir($sourceDir)
	{
		if (! empty($this->excludeDir) && in_array($sourceDir, $this->excludeDir)) {
			return;
		}

		$sourceDir = $this->fixDirSlash($sourceDir);

		// Copy files and directories.
		$sourceDirHandler = opendir($sourceDir);

		while ($file = readdir($sourceDirHandler)) {
			// Skip ".", ".." and hidden fields (Unix).
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			$sourcefilePath = $sourceDir . $file;

			if (is_dir($sourcefilePath)) {
				$this->checkDir($sourcefilePath);
			}

			if (! is_file($sourcefilePath)
				|| ! in_array($this->getFileExtension($sourcefilePath), $this->scanExtensions)
				|| ! $this->checkUtf8Bom($sourcefilePath)
			) {
				if (in_array($this->getFileExtension($sourcefilePath), $this->scanExtensions)
					&& ! $this->checkUtf8Bom($sourcefilePath)
				) {
					$this->withoutBomFiles[] = $sourcefilePath;
				}
				continue;
			}
			$this->bomFiles[] = str_replace($this->sourceDir, '', $sourcefilePath);
		}
	}

	/**
	 * Check a list of files
	 *
	 * @param string $listFiles
	 * @return void
	 */
	protected function checkListFiles($listFiles)
	{
		if (empty($listFiles)) {
			return;
		}

		foreach ($listFiles as $file) {
			if (in_array($this->getFileExtension($file), $this->scanExtensions)) {
				if (! $this->checkUtf8Bom($file)) {
					$this->withoutBomFiles[] = $file;
				} else {
					$this->bomFiles[] = $file;
				}
			}
		}
	}

	/**
	 * Check and change slash directory path
	 *
	 * @param string $dirPath
	 * @return string
	 */
	protected function fixDirSlash($dirPath)
	{
		$dirPath = str_replace('\\', '/', $dirPath);

		if (substr($dirPath, -1, 1) != '/') {
			$dirPath .= '/';
		}

		return $dirPath;
	}

	/**
	 * Get file extension
	 *
	 * @param string $filePath
	 * @return string
	 */
	protected function getFileExtension($filePath)
	{
		$info = pathinfo($filePath);
		return isset($info['extension']) ? $info['extension'] : '';
	}

	/**
	 * Check if UTF-8 BOM codification file
	 *
	 * @param string $filePath
	 * @return bool
	 */
	protected function checkUtf8Bom($filePath)
	{
		$file = fopen($filePath, 'r');
		$data = fgets($file, 10);
		fclose($file);

		$this->scannedFiles++;

		return (substr($data, 0, 3) == "\xEF\xBB\xBF");
	}

	/**
	 * Get the number of files scanned.
	 *
	 * @return int
	 */
	public function getScannedFiles()
	{
		return $this->scannedFiles;
	}

	/**
	 * Get the list of files detected with BOM.
	 *
	 * @return array
	 */
	public function getBomFiles()
	{
		return $this->bomFiles;
	}

	/**
	 * Get the list of files detected without BOM.
	 *
	 * @return array
	 */
	public function getWithoutBomFiles()
	{
		return $this->withoutBomFiles;
	}
}
