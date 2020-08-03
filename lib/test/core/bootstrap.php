<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

define('TIKI_IN_TEST', 1);
require_once(__DIR__ . '/../TikiTestCase.php');

ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_DEPRECATED);

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . "." . PATH_SEPARATOR . "../../core" . PATH_SEPARATOR . "../../..");
include_once('./include_non_autoload_compatible_classes.php');

function tra($string)
{
    return $string;
}

require __DIR__ . '/../../../vendor_bundled/vendor/autoload.php';

$tikidomain = '';
$api_tiki = null;
require 'db/local.php';

if ($api_tiki === 'pdo' && extension_loaded("pdo")) {
    require_once('db/tiki-db-pdo.php');
} else {
    require_once('db/tiki-db-adodb.php');
}

$db = TikiDb::get();
$db->setServerType($db_tiki);

$pwd = getcwd();
chdir(__DIR__ . '/../../../');
$cachelib = TikiLib::lib('cache');
chdir($pwd);
