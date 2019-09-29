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
 * Class CheckSqlEngineConversion is a helper to check differences between install a InnoDB Tiki and upgrade from MyISAM
 */
class CheckSqlEngineConversion
{
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
	 * @var bool If it outputs the execution of db diff
	 */
	protected $verbose = false;

	/**
	 * CheckSchemaUpgrade constructor.
	 */
	public function __construct()
	{
		$this->tikiRoot = dirname(dirname(__DIR__));
	}

	/**
	 * Execute check
	 */
	public function execute()
	{
		$resultValue = 0;

		$this->printMessage('Check sql engine conversion started: ' . date('c'));

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
			$this->printMessage('Loading database 1 as MyISAM');
			$this->writeLocalConfig($this->oldDb);
			$dbConnectionOld = $this->prepareDb($this->oldDb);
			$this->runDatabaseInstall(false);
			$this->printMessage('Updating database 1 from MyISAM to InnoDB');
			$this->runSqlEngineConversionScript($dbConnectionOld);

			$this->scrubDbCleanThingsThatShouldChange($dbConnectionOld, $this->oldDb, self::DB_OLD);

			//
			// Run clean db install
			//
			$this->printMessage('Installing database 2 with version ' . $tikiVersion->getVersion());
			$this->writeLocalConfig($this->newDb);
			$dbConnectionNew = $this->prepareDb($this->newDb);
			$this->runDatabaseInstall(true);

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
			'php check_sql_engine_conversion.php [-v] --db1=<user:pass@host:db> --db2=<user:pass@host:db>'
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

		$this->verbose = $this->getOption($options, 'v', 'verbose') === false ? true : false;

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
			$this->localConfig = $this->tikiRoot . '/db/sql_engine_conversion_' . uniqid() . '_local.php';
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
			. '$client_charset = "utf8mb4";' . "\n";

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
			'CREATE DATABASE `' . $dbConfig['dbs'] . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
		);
		$db->query('USE `' . $dbConfig['dbs'] . '`');
		return $db;
	}

	/**
	 * Executes the conversion script from MyISAM to InnoDB
	 * @param $dbConnection
	 * @return bool
	 * @throws Exception
	 */
	protected function runSqlEngineConversionScript($dbConnection)
	{
		$sqlFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'tiki_convert_myisam_to_innodb.sql';

		$sql = file_get_contents($sqlFile);

		if (! empty($sql)) {
			if ($this->runSQL($sql, $dbConnection) !== false) {
				return true;
			} else {
				$err = $dbConnection->errorInfo();
				$this->printMessageError('Error running the conversion script: ' . json_encode($err));
			}
		} else {
			$this->printMessageError('Could not retrieve valid SQL');
		}

		throw new \Exception('Fail to run conversion script from MyISAM to InnoDB');
	}

	/**
	 * Execute a set of statements contained in one SQL string
	 *
	 * @see \Installer::runFile for original code
	 *
	 * @param string $sql
	 * @param PDO $dbConnection
	 * @param bool $convertToInnoDB if should run a automated conversion as part of the script from MyISAM to InnoDB
	 * @return bool
	 */
	protected function runSQL($sql, $dbConnection, $convertToInnoDB = false)
	{
		// split the file into several queries?
		$statements = preg_split("#(;\s*\n)|(;\s*\r\n)#", $sql);

		$status = true;
		foreach ($statements as $statement) {
			if (trim($statement)) {
				if (preg_match('/^\s*(?!-- )/m', $statement)) {// If statement is not commented
					if ($convertToInnoDB) {
						// Convert all MyISAM statments to InnoDB
						$statement = str_ireplace("MyISAM", "InnoDB", $statement);
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
	 * Calls the Tiki console to execute a clean db install
	 *
	 * @param bool $useInnoDB If the db should be bootstrap with InnoDB (true) or MyISAM (false)
	 * @throws Exception
	 */
	protected function runDatabaseInstall($useInnoDB)
	{
		$phpFinder = new PhpExecutableFinder();

		$process = new Process(
			[
				$phpFinder->find(),
				'console.php',
				'database:install',
				'--useInnoDB',
				$useInnoDB ? '1' : '0',
			]
		);
		$process->setWorkingDirectory($this->tikiRoot);
		$process->setTimeout($this->getProcessTimeout());

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
			'--type=schema',
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

		$result = trim(file_get_contents($outputFile));
		unlink($outputFile);

		if (empty($result)) {
			$this->printMessage("\n*** Database engine change validated with success! ***\n");
			return;
		}

		$this->printMessageError("\n*** Issues found while validating database engine change, see below ***\n");
		$this->printMessageError('== Result of the db Analysis =======================' . "\n");
		echo $result . "\n";
		$this->printMessageError('====================================================' . "\n");

		throw new Exception('DB compare error');
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
		$shortOpts = 'v';
		$longOpts = [
			'verbose',
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

	/**
	 * Return the Timeout Value for Symfony Process
	 * Either get the value from a ENV (set as part of the CI process) or assume the default value
	 *
	 * @return float
	 */
	protected function getProcessTimeout()
	{
		$defaultTimeoutForProcess = 120; // 2 minutes

		if (isset($_SERVER['TIKI_CI_PROCESS_TIMEOUT'])) {
			return (float)$_SERVER['TIKI_CI_PROCESS_TIMEOUT'];
		}

		return (float)$defaultTimeoutForProcess;
	}
}

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
	die("Please run from a shell");
}

$checker = new CheckSqlEngineConversion();
$errors = $checker->execute();
if ($errors > 0) {
	exit(1);
}
exit(0);
