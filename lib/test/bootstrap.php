<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Laminas\Config\Config;

const TIKI_IN_TEST = 1;

define('TIKI_PATH', dirname(dirname(__DIR__)) . '/');
chdir(TIKI_PATH);

ini_set('display_errors', 'on');
error_reporting(E_ALL ^ E_DEPRECATED);

require_once 'vendor_bundled/vendor/autoload.php';

global $local_php, $api_tiki, $style_base;
$local_php = __DIR__ . '/local.php';

if (! is_file($local_php)) {
	die("\nYou need to setup a new database and create a local.php file for the test suite inside " . __DIR__ .
		"\nSee lib/test/local.php.dist for further instructions\n\n");
}

$api_tiki = 'adodb';
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
require_once 'lib/tikilib.php';
$tikilib = new TikiLib;

// update db if needed
require_once 'lib/init/initlib.php';
$installer = Installer::getInstance();

if (! $installer->tableExists('tiki_preferences')) {
	echo "Installing Tiki database...\n";
	$installer->cleanInstall();
} elseif ($installer->requiresUpdate()) {
	echo "Updating Tiki database...\n";
	$installer->update();
	if (count($installer->queries['failed'])) {
		foreach ($installer->queries['failed'] as $key => $error) {
			[$query, $message, $patch] = $error;

			echo "Error $key in $patch\n\t$query\n\t$message\n\n";
		}
		echo 'Exiting, fix database issues and try again.';
		exit(1);
	}
}

$smarty = TikiLib::lib('smarty');
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

require_once(__DIR__ . '/TikiTestCase.php');
require_once(__DIR__ . '/TestableTikiLib.php');

global $systemConfiguration;
$systemConfiguration = new Config(
	[
		'preference' => [],
		'rules' => [],
	],
	['readOnly' => false]
);

global $user_overrider_prefs, $prefs;
$user_overrider_prefs = [];
$prefs['language'] = 'en';
require_once 'lib/setup/prefs.php';
$prefs['site_language'] = 'en';
$prefs['zend_mail_handler'] = 'file';
$prefs['feature_typo_quotes'] = 'n';
$prefs['feature_typo_approximative_quotes'] = 'n';
$prefs['feature_typo_dashes_and_ellipses'] = 'n';
$prefs['feature_typo_smart_nobreak_spaces'] = 'n';

$builder = new Perms_Builder;
Perms::set($builder->build());

ini_set('display_errors', 'on');
error_reporting(E_ALL ^ E_DEPRECATED);
