<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\test\TestHelpers;

/**
 * Class GlobRecursiveHelper
 *
 * Used to find files of one type or another. Defaults to all Tiki files.
 *
 * @package Tiki\Lib\test\TestHelpers
 */
class GlobRecursiveHelper
{
	private $excludes;
	private $pattern;
	private $flags;

	/**
	 * GlobRecursiveHelper constructor.
	 *
	 * @param array          $excludes	Defaults to all Tiki files
	 * @param string         $pattern	A file glob file pattern that must be matched eg. '*.php'
	 * @param int            $flags		Any glob flags that should be used
	 */
	public function __construct(string $pattern = '*', array $excludes = ['vendor_', 'vendor/', 'temp/', 'lib/test/'], int $flags = GLOB_BRACE)
	{
		$this->excludes = $excludes;
		$this->pattern = $pattern;
		$this->flags = $flags;
	}

	/**
	 * @param string $startDir	The directory to scan, defaults to Tiki root dir
	 *
	 * @return array			An array of all files matched, or an empty array if no files matched
	 */
	public function process(string $startDir = '') : array
	{

		$files = glob($startDir . $this->pattern, $this->flags);
		foreach ($files as $key => $fileName) {
			foreach ($this->excludes as $exclude) {
				if (strpos($fileName, $exclude)) {
					unset($files[$key]);
					break;
				}
			}
		}
		foreach (glob($startDir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) as $dir) {
			// lets ignore hidden directories (and the .. and . files)
			if (strpos($dir, '.') === 0 && is_dir($dir)) {
				break;
			}
			/** If the directory has not been excluded from processing */
			$include = true;
			foreach ($this->excludes as $exclude) {
				if (strpos($dir, $exclude) !== false) {
					$include = false;
					break;
				}
			}
			if ($include) {
				$files = array_merge($files, $this->process($dir));
			}
		}
		return $files;
	}
}
