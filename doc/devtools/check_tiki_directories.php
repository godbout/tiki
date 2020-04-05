<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (PHP_SAPI !== 'cli') {
	die('Only available through command-line.');
}

require dirname(__FILE__) . '/svntools.php';

$dir = realpath(__DIR__ . '/../../') . '/';

$excludeDir = [
	$dir . 'vendor',
	$dir . 'vendor_bundled',
	$dir . 'temp',
];

$excludeMissingFiles = [
	$dir . 'vendor_bundled/vendor',
	'.'
];

$emptyDirectoriesMessage = '';
$missingIndexMessage = '';
$missingHtaccessMessage = '';

$it = new RecursiveDirectoryIterator(__DIR__ . '/../../');

foreach (new RecursiveIteratorIterator($it) as $file) {
	$filePath = $file->getRealpath();
	$fileName = $file->getFilename();

	if (in_array($fileName, ['..'])) {
		continue;
	}

	if (strpos($fileName, 'vendor') !== false) {
		$a = 1;
	}

	$excludeFile = toExclude($excludeDir, $filePath);
	$excludeMissingFile = toExclude($excludeMissingFiles, $filePath);

	if ($file->isDir()) {
		if (isEmptyDir($filePath)) {
			if ($excludeMissingFile === true) {
				continue;
			}
			if ($excludeFile === true) {
				continue;
			}
			$emptyDirectoriesMessage .= color($filePath, 'blue') . PHP_EOL;
			$missingIndexMessage .= color($filePath, 'blue') . PHP_EOL;
			$missingHtaccessMessage .= color($filePath, 'blue') . PHP_EOL;
		} else {
			if ($excludeMissingFile === true) {
				continue;
			}
			if ($excludeFile === true) {
				continue;
			}
			if (! file_exists($filePath . '/index.php')) {
				$missingIndexMessage .= color($filePath, 'blue') . PHP_EOL;
			}
			if (! file_exists($filePath . '/.htaccess')) {
				$missingHtaccessMessage .= color($filePath, 'blue') . PHP_EOL;
			}
		}
	}
}

if (! empty($emptyDirectoriesMessage) || ! empty($missingIndexMessage) || ! empty($missingHtaccessMessage)) {
	if (! empty($emptyDirectoriesMessage)) {
		echo color('The following directories are empty:', 'yellow') . PHP_EOL;
		info($emptyDirectoriesMessage);
	}
	if (! empty($missingIndexMessage)) {
		echo color('index.php file is missing in the following directories:', 'yellow') . PHP_EOL;
		info($missingIndexMessage);
	}
	if (! empty($missingHtaccessMessage)) {
		echo color('.htaccess file is missing in the following directories:', 'yellow') . PHP_EOL;
		info($missingHtaccessMessage);
	}
	exit(1);
} else {
	important('All directories OK');
}

/**
 * Check if folder is empty
 *
 * @param $dir
 * @return boolean
 */
function isEmptyDir($dir)
{
	return (($files = scandir($dir)) && count($files) <= 2);
}

/**
 * Check if folder is marked to be excluded
 *
 * @param $dir
 * @param $path
 * @return boolean
 */
function toExclude($dir, $path)
{
	return (str_replace($dir, '', $path) != $path);
}
