<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

define('TIKI_IN_TEST', 1);
define('TIKI_PATH', realpath(__DIR__ . '/../../'));
define('CUSTOM_ERROR_LEVEL', defined('E_DEPRECATED') ? E_ALL ^ E_DEPRECATED : E_ALL);

ini_set('display_errors', 'on');
error_reporting(CUSTOM_ERROR_LEVEL);

$paths = [
		ini_get('include_path'),
		realpath('.'),
		realpath('../core'),
		realpath('../..'),
		realpath('core'),
		realpath('../pear'),
		realpath('../../vendor_bundled/vendor'),
		realpath('../../vendor_bundled/vendor/mikey179/vfsStream/src/main/php'),
		realpath('../../vendor_extra/pear')
		];

ini_set('include_path', implode(PATH_SEPARATOR, $paths));

require_once __DIR__ . '/../../vendor_bundled/vendor/autoload.php';

if (! is_file(__DIR__ . '/local.php')) {
	die("\nYou need to setup a new database and create a local.php file for the test suite inside " . __DIR__ .
		"\nSee lib/test/local.php.dist for further instructions\n\n");
}

global $local_php, $api_tiki, $style_base;
$api_tiki = 'adodb';
$local_php = __DIR__ . '/local.php';
require_once($local_php);

$style_base = 'skeleton';

// Force autoloading
if (! class_exists('ADOConnection')) {
	die('AdoDb not found.');
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$initializer = new TikiDb_Initializer;
$initializer->setPreferredConnector($api_tiki);
$db = $initializer->getConnection(
	[
		'host' => $host_tiki,
		'user' => $user_tiki,
		'pass' => $pass_tiki,
		'dbs' => $dbs_tiki,
		'charset' => $client_charset,
	]
);

if (! $db) {
	die("\nUnable to connect to the database\n\n");
}

TikiDb::set($db);

global $tikilib;
require_once __DIR__ . '/../../lib/tikilib.php';
$tikilib = new TikiLib;

// update db if needed
require_once __DIR__ . '/../../lib/init/initlib.php';
include_once(__DIR__ . '/../../installer/installlib.php');
$installer = Installer::getInstance();

if (! $installer->tableExists('tiki_preferences')) {
	echo "Installing Tiki database...\n";
	$installer->cleanInstall();
} elseif ($installer->requiresUpdate()) {
	echo "Updating Tiki database...\n";
	$installer->update();
	if (count($installer->queries['failed'])) {
		foreach ($installer->queries['failed'] as $key => $error) {
			list( $query, $message, $patch ) = $error;

			echo "Error $key in $patch\n\t$query\n\t$message\n\n";
		}
		echo 'Exiting, fix database issues and try again.';
		exit(1);
	}
}

$pwd = getcwd();
chdir(__DIR__ . '/../..');
$smarty = TikiLib::lib('smarty');
require_once 'lib/init/smarty.php';
$smarty->addPluginsDir('../smarty_tiki/');
$cachelib = TikiLib::lib('cache');
$wikilib = TikiLib::lib('wiki');
$userlib = TikiLib::lib('user');
$headerlib = TikiLib::lib('header');
require_once 'lib/init/tra.php';
$access = TikiLib::lib('access');
require_once 'lib/setup/timer.class.php';

$_SESSION = [
		'u_info' => [
			'login' => null
			]
		];
chdir($pwd);

require_once(__DIR__ . '/TikiTestCase.php');
require_once(__DIR__ . '/TestableTikiLib.php');

global $systemConfiguration;
$systemConfiguration = new Zend\Config\Config(
	[
		'preference' => [],
		'rules' => [],
	],
	['readOnly' => false]
);

global $user_overrider_prefs, $prefs;
$user_overrider_prefs = [];
$prefs['language'] = 'en';
require_once __DIR__ . '/../../lib/setup/prefs.php';
$prefs['site_language'] = 'en';
$prefs['zend_mail_handler'] = 'file';
$prefs['feature_typo_quotes'] = 'n';
$prefs['feature_typo_approximative_quotes'] = 'n';
$prefs['feature_typo_dashes_and_ellipses'] = 'n';
$prefs['feature_typo_smart_nobreak_spaces'] = 'n';

$builder = new Perms_Builder;
Perms::set($builder->build());

ini_set('display_errors', 'on');
error_reporting(CUSTOM_ERROR_LEVEL);
