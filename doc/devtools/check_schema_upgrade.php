<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace TikiDevTools;

use DBDiff;
use Exception;
use PDO;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use TWVersion;

/**
 * Class CheckSchemaUpgrade is a helper to check differences between upgrade and install a tiki db
 */
class CheckSchemaUpgrade
{
	const DB_URL_TEMPLATE = 'http://tiki.org/ci_%d.sql';

	const DB_OLD = 'OLD';
	const DB_NEW = 'NEW';

	/**
	 * @var string Tiki root folder
	 */
	protected $tikiRoot;

	/**
	 * @var string existing config file
	 */
	protected $localConfig;

	/**
	 * @var string Original value for the db that will be updated from last major
	 */
	protected $oldDbRaw;

	/**
	 * @var array Config for the db that will be updated from last major
	 */
	protected $oldDb;

	/**
	 * @var string Original value for the db that will have a clean install
	 */
	protected $newDbRaw;

	/**
	 * @var array Config for the db that will have a clean install
	 */
	protected $newDb;

	/**
	 * @var bool if we should use InnoDB
	 */
	protected $useInnoDB = true;

	/**
	 * @var bool If it outputs the execution of db diff
	 */
	protected $verbose = false;

	/**
	 * @var string previous major to compare to
	 */
	protected $previousMajor;

	/**
	 * @var string folder to use for caching db versions
	 */
	protected $cacheFolder = "dbdiff/cache";

	/**
	 * @var bool Ignore changes in preferences values
	 */
	protected $ignorePreferenceChanges = true;

	/**
	 * CheckSchemaUpgrade constructor.
	 */
	public function __construct()
	{
		$this->tikiRoot = dirname(dirname(__DIR__));

		$this->cacheFolder = __DIR__ . '/' . $this->cacheFolder;
	}

	/**
	 * Execute check
	 */
	public function execute()
	{
		$resultValue = 0;

		$this->printMessage('Check schema updated started: ' . date('c'));

		try {
			//
			// Prepare
			//
			$this->printMessage('Preparing and validating the environment');
			$this->checkEnvironment();
			$this->parseCommandLine();
			$this->backupLocalConfig();

			$tikiVersion = new TWVersion();

			//
			// Run upgrade from previous major (from latest SVN)
			//
			$this->printMessage('Loading database 1 from previous major version');
			$this->writeLocalConfig($this->oldDb);
			$dbConnectionOld = $this->prepareDb($this->oldDb);
			$this->bootstrapDbWithPreviousMajor($dbConnectionOld);

			$this->printMessage('Updating database 1 from previous major to version ' . $tikiVersion->getVersion());
			$this->runDatabaseUpdate();

			$this->scrubDbCleanThingsThatShouldChange($dbConnectionOld, $this->oldDb, self::DB_OLD);

			//
			// Run clean db install
			//
			$this->printMessage('Installing database 2 with version ' . $tikiVersion->getVersion());
			$this->writeLocalConfig($this->newDb);
			$dbConnectionNew = $this->prepareDb($this->newDb);
			$this->runDatabaseInstall();

			$this->scrubDbCleanThingsThatShouldChange($dbConnectionNew, $this->newDb, self::DB_NEW);

			//
			// Compare the DBS
			//
			$this->printMessage('Comparing Databases');
			$this->runDbCompare();
		} catch (\Exception $e) {
			$this->printMessageError($e->getMessage());
			$resultValue = 1;
		}

		//
		// Cleanup
		//
		$this->printMessage('Restoring local environment');
		$this->restoreLocalConfig();

		$this->printMessage('Check schema updated completed: ' . date('c'));

		return $resultValue;
	}

	/**
	 * Check usage
	 */
	protected function usage()
	{
		$this->printMessageError("\n" . 'How to execute this command:');
		$this->printMessage(
			'php check_schema_upgrade [-v] [-p] [-e=<MyISAM|InnoDB>] [-m=<major>] --db1=<user:pass@host:db> --db2=<user:pass@host:db>'
		);
		$this->printMessage('db1 and db2 are the databases to be used to load the schema');
		$this->printMessageError('!! Both databases will be erased !!' . "\n");
	}

	/**
	 * Validate the environment
	 *
	 * @throws Exception
	 */
	protected function checkEnvironment()
	{
		$errors = 0;

		if (! file_exists(__DIR__ . '/dbdiff/vendor/autoload.php')) {
			$errors++;
			$this->printMessageError('dbdiff/vendor/autoload.php not available, did you run composer for dbdiff?');
		} else {
			require_once __DIR__ . '/dbdiff/vendor/autoload.php';
		}

		if (! file_exists($this->tikiRoot . '/vendor_bundled/vendor/autoload.php')) {
			$errors++;
			$this->printMessageError(
				'vendor_bundled/vendor/autoload.php not available, did you run composer for tiki?'
			);
		} else {
			require_once $this->tikiRoot . '/vendor_bundled/vendor/autoload.php';
			require_once $this->tikiRoot . '/lib/setup/twversion.class.php';
		}

		if (! is_dir($this->cacheFolder) && is_writable(dirname($this->cacheFolder))) {
			mkdir($this->cacheFolder); // attempt to create folder if do not exists
		}
		if (! is_writable($this->cacheFolder)) {
			// actually only a warning
			$this->printMessageError(
				$this->cacheFolder . ' not writable, will not be able to cache previous db version'
			);
		}

		if (! is_writable($this->tikiRoot . '/db')) {
			$errors++;
			$this->printMessageError($this->tikiRoot . '/db' . ' not writable, can not configure tiki');
		}

		if ($errors > 0) {
			$this->printMessageError('Environment errors, please fix them and run the command again');
			throw new \Exception('Errors');
		}
	}

	/**
	 * Load options from command line
	 *
	 * @throws Exception
	 */
	protected function parseCommandLine()
	{
		$options = $this->getOpts();

		$this->previousMajor = $this->getOption($options, 'm', 'major');
		$this->verbose = $this->getOption($options, 'v', 'verbose') === false ? true : false;
		$this->ignorePreferenceChanges = $this->getOption($options, 'p', 'preferences') === false ? false : true;
		$this->useInnoDB = strtolower($this->getOption($options, 'e', 'engine')) === 'myisam' ? false : true;

		$this->oldDbRaw = $this->getOption($options, null, 'db1');
		$result = $this->parseDbRaw($this->oldDbRaw);
		$this->oldDb = $result;

		if ($result === null) {
			$this->printMessageError('Wrong value for db1, check the the right format below');
			$this->usage();
			throw new Exception('Wrong db1');
		}

		$this->newDbRaw = $this->getOption($options, null, 'db2');
		$result = $this->parseDbRaw($this->newDbRaw);
		$this->newDb = $result;

		if ($result === null) {
			$this->printMessageError('Wrong value for db2, check the right format below');
			$this->usage();
			throw new Exception('Wrong db2');
		}
	}

	/**
	 * Parse the raw db format
	 *
	 * @param $raw
	 * @return array|null
	 */
	protected function parseDbRaw($raw)
	{
		$parts = explode('@', $raw);
		if (count($parts) != 2) {
			return null;
		}

		$credentials = explode(':', $parts[0]);
		if (count($credentials) != 2) {
			return null;
		}

		$hostAndDb = explode(':', $parts[1]);
		if (count($hostAndDb) != 2) {
			return null;
		}

		$result = [
			'user' => $credentials[0],
			'pass' => $credentials[1],
			'host' => $hostAndDb[0],
			'dbs' => $hostAndDb[1],
		];

		foreach ($result as $k => $v) {
			if (empty($v)) {
				return null;
			}
		}

		return $result;
	}

	/**
	 * Backup the tiki config (if exists)
	 */
	protected function backupLocalConfig()
	{
		if (file_exists($this->tikiRoot . '/db/local.php')) {
			$this->localConfig = $this->tikiRoot . '/db/schema_update_' . uniqid() . '_local.php';
			rename($this->tikiRoot . '/db/local.php', $this->localConfig);
			$this->printMessage(
				'File: ' . $this->tikiRoot . '/db/local.php' . "\n" . '    renamed as ' . $this->localConfig
			);
		}
	}

	/**
	 * Restore the tiki config (if was backup)
	 */
	protected function restoreLocalConfig()
	{
		if (! empty($this->localConfig) && file_exists($this->localConfig)) {
			rename($this->localConfig, $this->tikiRoot . '/db/local.php');
			$this->printMessage(
				'File: ' . $this->tikiRoot . '/db/local.php' . "\n" . '    restored from ' . $this->localConfig
			);
		}
	}

	/**
	 * Write a basic Tiki configuration file
	 *
	 * @param array $dbConfig
	 */
	protected function writeLocalConfig($dbConfig)
	{
		$TWV = new TWVersion();

		$local = '<?php' . "\n"
			. '$db_tiki = "mysqli";' . "\n"
			. '$dbversion_tiki = "' . $TWV->getBaseVersion() . '";' . "\n"
			. '$host_tiki = "' . $dbConfig['host'] . '";' . "\n"
			. '$user_tiki = "' . $dbConfig['user'] . '";' . "\n"
			. '$pass_tiki = "' . $dbConfig['pass'] . '";' . "\n"
			. '$dbs_tiki = "' . $dbConfig['dbs'] . '";' . "\n"
			. '$client_charset = "utf8";' . "\n";

		file_put_contents($this->tikiRoot . '/db/local.php', $local);
	}

	/**
	 * Prepare the db to load tiki info (drop and create)
	 *
	 * @param $dbConfig
	 * @return PDO
	 */
	protected function prepareDb($dbConfig)
	{
		$db = new PDO('mysql:host=' . $dbConfig['host'], $dbConfig['user'], $dbConfig['pass']);
		$db->query('DROP DATABASE IF EXISTS `' . $dbConfig['dbs'] . '`;');
		$db->query(
			'CREATE DATABASE `' . $dbConfig['dbs'] . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;'
		);
		$db->query('USE `' . $dbConfig['dbs'] . '`');
		return $db;
	}

	/**
	 * Loads the a SQL file from a major into Tiki database (so we can run upgrade)
	 *
	 * @param PDO $dbConnection
	 * @return bool
	 * @throws Exception
	 */
	protected function bootstrapDbWithPreviousMajor($dbConnection)
	{
		$TWV = new TWVersion();
		$velements = explode('.', $TWV->getBaseVersion());
		$major = (int)$velements[0];

		$sql = '';

		if (! empty($this->previousMajor)) {
			$tryMajor = (int)$this->previousMajor;
			$sql = $this->loadDbFileByMajor($tryMajor);
		} else {
			$tryMajor = $major - 1;
			while ($tryMajor >= 12) {
				$sql = $this->loadDbFileByMajor($tryMajor);
				if (! empty($sql)) {
					break;
				}
				$tryMajor--;
			}
		}

		$this->printMessage('Loading the database for major version: ' . $tryMajor);

		if (! empty($sql)) {
			if ($this->runSQL($sql, $dbConnection) !== false) {
				return true;
			} else {
				$err = $dbConnection->errorInfo();
				$this->printMessageError('Error running the database load: ' . json_encode($err));
			}
		} else {
			$this->printMessageError('Could not retrieve valid SQL');
		}

		$this->printMessageError(
			'Failed to load the db for previous Major, last attempt done for version ' . $tryMajor . ' currently at ' . $major
		);

		throw new \Exception('Fail to load old db');
	}

	/**
	 * Attempts to load a given major from cache or from the redirect in tiki website
	 *
	 * @param $major
	 * @return bool|string
	 */
	protected function loadDbFileByMajor($major)
	{
		$cachedDbFile = $this->cacheFolder . '/ci_' . $major . '.sql';
		if (file_exists($cachedDbFile)) {
			$sql = file_get_contents($cachedDbFile);
			return $sql;
		}

		$dbContent = file_get_contents(sprintf(self::DB_URL_TEMPLATE, $major));
		/** @noinspection SyntaxError */
		if (! empty($dbContent) && strpos($dbContent, 'CREATE TABLE `tiki_schema`') !== false) { //check that looks like a sql file
			$sql = $dbContent;
			file_put_contents($cachedDbFile, $dbContent);
			return $sql;
		}

		// TODO: try to install old version of Tiki to get the db generated, instead of rely om pre generated files

		return '';
	}

	/**
	 * Execute a set of statements contained in one SQL string
	 *
	 * @see \Installer::runFile for original code
	 *
	 * @param string $sql
	 * @param PDO $dbConnection
	 * @return bool
	 */
	protected function runSQL($sql, $dbConnection)
	{
		// split the file into several queries?
		$statements = preg_split("#(;\s*\n)|(;\s*\r\n)#", $sql);

		$status = true;
		foreach ($statements as $statement) {
			if (trim($statement)) {
				if (preg_match('/^\s*(?!-- )/m', $statement)) {// If statement is not commented
					if ($this->useInnoDB) {
						// Convert all MyISAM statments to InnoDB
						$statement = str_ireplace("MyISAM", "InnoDB", $statement);
					} else {
						$statement = str_ireplace("InnoDB", "MyISAM", $statement);
					}

					if ($dbConnection->exec($statement) === false) {
						$err = $dbConnection->errorInfo();
						$this->printMessageError('Error running the database load: ' . json_encode($err));
						$this->printMessage($statement);
						$status = false;
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Calls the Tiki console to execute a db update
	 *
	 * @throws Exception
	 */
	protected function runDatabaseUpdate()
	{
		$phpFinder = new PhpExecutableFinder();

		$process = new Process(
			[
				$phpFinder->find(),
				'console.php',
				'database:update',
			]
		);
		$process->setWorkingDirectory($this->tikiRoot);

		$process->run();

		echo $process->getOutput() . $process->getErrorOutput();

		if ($process->getExitCode() !== 0) {
			$this->printMessageError('Error while running the database update');
			throw new Exception('Error db update');
		}
	}

	/**
	 * Calls the Tiki console to execute a clean db install
	 *
	 * @throws Exception
	 */
	protected function runDatabaseInstall()
	{
		$phpFinder = new PhpExecutableFinder();

		$process = new Process(
			[
				$phpFinder->find(),
				'console.php',
				'database:install',
				'--useInnoDB',
				$this->useInnoDB ? '1' : '0',
			]
		);
		$process->setWorkingDirectory($this->tikiRoot);

		$process->run();

		echo $process->getOutput() . $process->getErrorOutput();

		if ($process->getExitCode() !== 0) {
			$this->printMessageError('Error while running the database install');
			throw new Exception('Error db install');
		}
	}

	/**
	 * Cleans the db from the things that should vary in a normal Tiki installation to help in the compare
	 *
	 * @param PDO $dbConnection
	 * @param array $dbConfig
	 * @param string $whatDb OLD|NEW
	 */
	protected function scrubDbCleanThingsThatShouldChange($dbConnection, $dbConfig, $whatDb)
	{
		// clean index rebuild related tables and tables marked as unused
		$statement = $dbConnection->prepare(
			"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND (TABLE_NAME LIKE 'index_%' OR TABLE_NAME LIKE 'zzz_unused_%')"
		);
		$result = $statement->execute([':db' => $dbConfig['dbs']]);
		if ($result === false) {
			$this->printMessageError('Error retrieving list of tables: ' . json_encode($dbConnection->errorInfo()));
		} else {
			foreach ($statement->fetchAll() as $info) {
				$dbConnection->exec("DROP TABLE IF EXISTS `" . $info['TABLE_NAME'] . "`");
			}
		}

		$dbConnection->exec("DELETE FROM `tiki_preferences` WHERE name LIKE 'unified_%'");

		$dbConnection->exec("DROP TABLE IF EXISTS `index_pref_en`");

		// set a well defined date for some records
		$dbConnection->exec("UPDATE `tiki_schema` SET `install_date` = '2001-01-01 01:01:01'");

		// remove messages from action log (are not part of the schema)
		$dbConnection->exec("DELETE FROM `tiki_actionlog`");

		// reload tiki_menu_options in the upgraded tiki to account for the case where an old entry is removed
		$dbConnection->exec("CREATE TABLE  `tiki_menu_options_tmp` AS SELECT * FROM `tiki_menu_options` ORDER BY menuId,type,name,url,position");
		$dbConnection->exec("ALTER TABLE `tiki_menu_options_tmp` CHANGE COLUMN optionId optionId int NULL");
		$dbConnection->exec("UPDATE  `tiki_menu_options_tmp` SET optionId=NULL");
		$dbConnection->exec("DELETE FROM `tiki_menu_options`");
		$dbConnection->exec("ALTER TABLE `tiki_menu_options` AUTO_INCREMENT = 1");
		$dbConnection->exec("INSERT INTO `tiki_menu_options` SELECT * FROM `tiki_menu_options_tmp`");
		$dbConnection->exec("DROP TABLE `tiki_menu_options_tmp`");

		// reload tiki_actionlog_conf in the upgraded tiki to account for the case where an old entry is removed
		$dbConnection->exec("CREATE TABLE  `tiki_actionlog_conf_tmp` AS SELECT * FROM `tiki_actionlog_conf` ORDER BY action,objectType,status");
		$dbConnection->exec("ALTER TABLE `tiki_actionlog_conf_tmp` CHANGE COLUMN id id int NULL");
		$dbConnection->exec("UPDATE  `tiki_actionlog_conf_tmp` SET id=NULL");
		$dbConnection->exec("DELETE FROM `tiki_actionlog_conf`");
		$dbConnection->exec("ALTER TABLE `tiki_actionlog_conf` AUTO_INCREMENT = 1");
		$dbConnection->exec("INSERT INTO `tiki_actionlog_conf` SELECT * FROM `tiki_actionlog_conf_tmp`");
		$dbConnection->exec("DROP TABLE `tiki_actionlog_conf_tmp`");
	}

	/**
	 * Execute the DB comparision between the instance that was upgraded and the instance that did the clean install
	 *
	 * @throws Exception
	 */
	protected function runDbCompare()
	{
		$outputFile = tempnam(sys_get_temp_dir(), 'dbdiff_');

		$argv = $GLOBALS['argv'];
		$fakeArgv = [
			$argv[0],
			sprintf('--server1=%s:%s@%s', $this->oldDb['user'], $this->oldDb['pass'], $this->oldDb['host']),
			sprintf('--server2=%s:%s@%s', $this->newDb['user'], $this->newDb['pass'], $this->newDb['host']),
			'--type=all',
			'--include=all',
			sprintf('--template=%s', __DIR__ . '/dbdiff/tiki.tmpl'),
			'--nocomments=true',
			sprintf('server1.%s:server2.%s', $this->oldDb['dbs'], $this->newDb['dbs']),
			sprintf('--output=%s', $outputFile),
		];
		$GLOBALS['argv'] = $fakeArgv;

		$dbdiff = new DBDiff\DBDiff;

		$errorLevel = error_reporting();
		ob_start();
		try {
			error_reporting($errorLevel & ~E_NOTICE); // DBDiff returns some notices of undefined offsets
			$dbdiff->run();
			error_reporting($errorLevel);
		} catch (\Exception $e) {
			error_reporting($errorLevel);
			ob_end_flush();
			throw $e;
		}

		$output = ob_get_clean();

		if ($this->verbose) {
			echo $output;
		}

		$GLOBALS['argv'] = $argv;

		$originalResult = trim(file_get_contents($outputFile));
		unlink($outputFile);

		if ($this->ignorePreferenceChanges) {
			$result = $this->filterPreferencesChanges($originalResult);
		} else {
			$result = $originalResult;
		}

		if (empty($result)) {
			$this->printMessage("\n*** Database upgrade validated with success! ***\n");
			return;
		}

		$this->printMessageError("\n*** Issues found while validating database upgrade, see below ***\n");
		$this->printMessageError('== Result of the db Analysis - missing statements ==');
		echo $result . "\n";
		$this->printMessageError('====================================================' . "\n");

		throw new Exception('DB compare error');
	}

	protected function filterPreferencesChanges($results)
	{
		$parts = explode("\n", $results);
		$result = array_filter(
			$parts,
			function ($item) {
				/** @noinspection SyntaxError */
				if (strncmp($item, 'DELETE FROM `tiki_preferences`', 30) === 0
					|| strncmp($item, 'INSERT INTO `tiki_preferences`', 30) === 0) {
					return false;
				}
				return true;
			}
		);
		return implode("\n", $result);
	}

	/**
	 * Print a normal message
	 *
	 * @param $message
	 * @param null $outputPath
	 */
	protected function printMessage($message, $outputPath = null)
	{
		echo "\033[0;32m" . $message . "\033[0m" . PHP_EOL;
		if (! empty($outputPath)) {
			file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * Print an error message
	 *
	 * @param $message
	 * @param null $outputPath
	 */
	protected function printMessageError($message, $outputPath = null)
	{
		echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
		if (! empty($outputPath)) {
			file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * Get the options from command line
	 */
	protected function getOpts()
	{
		$shortOpts = 'm:vpe:';
		$longOpts = [
			'major:',
			'verbose',
			'preferences',
			'engine:',
			'db1:',
			'db2:',
		];
		$options = getopt($shortOpts, $longOpts);

		return $options;
	}

	/**
	 * Helper to get a value of an command line option both using the short format and the long format
	 *
	 * @param $options
	 * @param null $short
	 * @param null $long
	 * @return null
	 */
	protected function getOption($options, $short = null, $long = null)
	{
		if (! empty($long) && array_key_exists($long, $options)) {
			return $options[$long];
		}

		if (! empty($short) && array_key_exists($short, $options)) {
			return $options[$short];
		}

		return null;
	}
}

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
	die("Please run from a shell");
}

$checker = new CheckSchemaUpgrade();
$errors = $checker->execute();
if ($errors > 0) {
	exit(1);
}
exit(0);
