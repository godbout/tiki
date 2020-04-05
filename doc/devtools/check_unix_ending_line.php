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

$dir = __DIR__ . '/../../';

$excludeDir = [
	$dir . 'vendor',
	$dir . 'vendor_bundled',
	$dir . 'temp'
];

$extensions = [
	'php',
	'tpl',
	'css',
	'less',
	'htaccess',
	'config'
];

$message = '';
$paramList = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

$iterator = [];
foreach ($paramList as $paramFile) {
	$file = $dir . $paramFile;
	if (file_exists($file) && basename(__FILE__) != basename($file)) {
		$iterator[] = $file;
	}
}

if (empty($iterator)) {
	$dirIterator = new RecursiveDirectoryIterator($dir);
	$iterator = new RecursiveIteratorIterator($dirIterator);
}

foreach ($iterator as $file) {
	$currentFile = $file;

	if ($file instanceof SplFileInfo) {
		$currentFile = $file->getPathname();
	}

	$fileInfo = pathinfo($currentFile);
	$excludeFile = (str_replace($excludeDir, '', $currentFile) != $currentFile);

	if ($excludeFile === false) {
		if (isset($fileInfo['extension']) && in_array($fileInfo['extension'], $extensions)) {
			$data = file($currentFile);
			$lastLine = $data[count($data) - 1];
			$lineEnding = substr($lastLine, -1);

			if ($lineEnding !== PHP_EOL) {
				$message .= $currentFile . PHP_EOL;
			}
		}
	}
}

if (! empty($message)) {
	echo color('Files without unix ending line:', 'yellow') . PHP_EOL;
	info($message);
	exit(1);
} else {
	important('All files OK');
}
