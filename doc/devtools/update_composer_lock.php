<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (isset($_SERVER['REQUEST_METHOD'])) {
	die('Only available through command-line.');
}

$dir = __DIR__;
require dirname(__FILE__) . '/svntools.php';

$vendorBundledDir = $dir . '/../../vendor_bundled';
$composerLockFile = $dir . '/../../vendor_bundled/composer.lock'; 
$composerPharFile = $dir . '/../../temp/composer.phar'; 

if (! is_dir($vendorBundledDir)) {
	error('vendor_bundled folder does not exits');
}

if (! file_exists($composerLockFile)) {
	error('file vendor_bundled/composer.lock not found');
}

if (! file_exists($composerPharFile)) {
	error('file temp/composer.phar not found');
}

$composerLockBefore = file_get_contents($composerLockFile);
exec('cd ' . $vendorBundledDir . ' && ../temp/composer.phar update nothing');
$composerLockAfter = file_get_contents($composerLockFile);

if ($composerLockBefore != $composerLockAfter) {
	important('composer.lock updated');
	exit(1);
} else {
	important('composer.lock is up to date');
	exit(0);
}
