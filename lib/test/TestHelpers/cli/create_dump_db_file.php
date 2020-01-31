<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
	die("Please run from a shell");
}

require_once __DIR__ . '/../../../../vendor_bundled/vendor/autoload.php';

if ($argc != 2) {
	die("Missing argument. USAGE: $argv[0] <dump_filename>");
}

$test_TikiAcceptanceTestDBRestorer = new TikiAcceptanceTestDBRestorerSQLDumps();
$test_TikiAcceptanceTestDBRestorer->create_dump_file($argv[1]);
echo "File $argv[1] was created in your mysql data directory\n";
