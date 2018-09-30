<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Theme;

use TikiLib;
use Zend\Filter\Word\DashToCamelCase as DashToCamelCase;
use Zend\Filter\Word\UnderscoreToCamelCase as UnderscoreToCamelCase;

/**
 * Class that handles tiki theme operations
 *
 * @access public
 */
class Handler
{
	/**
	 * Get all files inside folder
	 *
	 * @param string $pattern
	 * @param int $flags
	 * @return array
	 */
	public function getAllFolderFiles($pattern, $flags = 0)
	{
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
			$files = array_merge($files, $this->getAllFolderFiles($dir . '/' . basename($pattern), $flags));
		}
		return $files;
	}

	/**
	 * Convert theme name into camelcase
	 *
	 * @param string $name
	 * @return string
	 */
	public function getNameCamelCase($name)
	{
		$filterDash = new DashToCamelCase();
		$filterUnderscore = new UnderscoreToCamelCase();
		$convertedName = $filterDash->filter($name);
		$convertedName = lcfirst($filterUnderscore->filter($convertedName));
		return $convertedName;
	}

	/**
	 * Check if theme exists
	 *
	 * @param string $theme
	 * @return boolean
	 */
	public function themeExists($theme)
	{
		$themelib = TikiLib::lib('theme');
		$listThemes = $themelib->get_themes();
		if (in_array($theme, $listThemes)) {
			return true;
		}
		return false;
	}

	/**
	 * Rename theme files
	 *
	 * @param array $themeFiles
	 * @param string $name
	 * @param string $rename
	 * @return null
	 */
	public function convertFilesNames($themeFiles, $name, $rename)
	{
		if (! empty($themeFiles) && ! empty($name) && ! empty($rename)) {
			$pattern = $name . '.';
			$replacePattern = $rename . '.';
			foreach ($themeFiles as $file) {
				if (strpos($file, $pattern) !== false) {
					$renameFile = str_replace($pattern, $replacePattern, $file);
					rename($file, $renameFile);
				}
			}
		}
	}
}
