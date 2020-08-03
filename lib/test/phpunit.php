<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
* A simple wrapper around /usr/bin/phpunit to enable debugging
* tests with Xdebug from within Aptana or Eclipse.
*
* Linux only (it should be simple to add support to other OSs).
*/
echo "Please use 'php phpunit' instead" . PHP_EOL;

// Linux
//require_once(__DIR__ . '/../../vendor_bundled/vendor/phpunit/phpunit/phpunit');

// Windows
// comment out the Linux require line (above) and uncomment the 2 lines below
//$pear_bin_path = getenv('PHP_PEAR_BIN_DIR').DIRECTORY_SEPARATOR;
//require_once($pear_bin_path."phpunit");
