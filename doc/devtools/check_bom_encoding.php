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
require dirname(__DIR__) . '/../lib/core/BOMChecker/Scanner.php';

$dir = __DIR__ . '/../../';

$excludeFolders = [
	$dir . 'vendor',
	$dir . 'vendor_bundled',
	$dir . 'temp'
];

$extensions = [
	'php',
	'tpl',
	'sql',
	'css',
	'less',
	'js',
	'htaccess',
	'config',
	'xml'
];

$paramList = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
$listFiles = [];
foreach ($paramList as $paramFile) {
	$file = $dir . $paramFile;
	if (file_exists($file) && basename(__FILE__) != basename($file)) {
		$listFiles[] = $file;
	}
}

$BOMScanner = new BOMChecker_Scanner($dir, $extensions, $excludeFolders, $listFiles);
$BOMFiles = $BOMScanner->scan();
$totalFilesScanned = $BOMScanner->getScannedFiles();
$listBOMFiles = $BOMScanner->getBomFiles();

if (! empty($listBOMFiles)) {
	echo color('Found ' . $totalFilesScanned . ' files with BOM encoding:', 'yellow') . PHP_EOL;
	foreach ($listBOMFiles as $files) {
		info($files);
	}
	exit(1);
} else {
	important('Files without BOM encoding');
}
