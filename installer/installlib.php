<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}


require_once __DIR__ . '/../lib/setup/twversion.class.php';
require_once 'Patch.php';

/**
 * @see Patch
 */
class Installer extends TikiDb_Bridge implements SplSubject
{
	static $instance = null; // Singleton instance
	private $observers;

	public $scripts = [];
	public $executed = [];

	public $queries = [
		'currentStmt' => '',
		'currentFile' => '',
		'executed' => 0,
		'total' => 0,

		'successful' => [],
		'failed' => []
	];

	public $useInnoDB = true;

	private function __construct()
	{
		$this->observers = new SplObjectStorage();
		$this->buildPatchList();
		$this->buildScriptList();
	}

	/**
	 * Get the instance (creating one if necessary)
	 * @return Installer
	 */
	static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function cleanInstall() // {{{
	{
		if ($image = $this->getBaseImage()) {
			$this->runFile($image);
			$this->buildPatchList();
			$this->buildScriptList();
		} else {
			// No image specified, standard install
			$this->runFile(__DIR__ . '/../db/tiki.sql');
			if ($this->isMySQLFulltextSearchSupported()) {
				$this->runFile(__DIR__ . '/../db/tiki_fulltext_indexes.sql');
			}
			if ($this->useInnoDB) {
				$this->runFile(__DIR__ . '/../db/tiki_innodb.sql');
			} else {
				$this->runFile(__DIR__ . '/../db/tiki_myisam.sql');
			}
			$this->buildPatchList();
			$this->buildScriptList();

			// Base SQL file contains the distribution tiki patches up to this point
			foreach (Patch::getPatches([Patch::NOT_APPLIED]) as $patchName => $patch) {
				if (preg_match('/_tiki$/', $patchName)) {
					$patch->record();
				}
			}
		}

		$this->update();
	} // }}}

	function update() // {{{
	{
		// Mark InnoDB usage for updates
		if (strcasecmp($this->getCurrentEngine(), "InnoDB") == 0) {
			$this->useInnoDB = true;
		} else {
			$this->useInnoDB = false;
		}

		if (! $this->tableExists('tiki_schema')) {
			// DB too old to handle auto update

			if (file_exists(__DIR__ . '/../db/custom_upgrade.sql')) {
				$this->runFile(__DIR__ . '/../db/custom_upgrade.sql');
			} else {
				// If 1.9
				if (! $this->tableExists('tiki_minichat')) {
					$this->runFile(__DIR__ . '/../db/tiki_1.9to2.0.sql');
				}

				$this->runFile(__DIR__ . '/../db/tiki_2.0to3.0.sql');
			}
		}

		$this->assureDefaultCharSetIsAlignedWithTikiSchema();

		$TWV = new TWVersion;
		$dbversion_tiki = $TWV->version;

		// If a Mysql data file exists, use that. Very fast
		//	If data file is missing or the batch loader is not available, use the single insert method
		$secdb = __DIR__ . '/../db/tiki-secdb_' . $dbversion_tiki . '_mysql.sql';
		$secdbData = __DIR__ . '/../db/tiki-secdb_' . $dbversion_tiki . '_mysql.data';
		if (file_exists($secdbData)) {
			// A MySQL datafile exists
			$truncateTable = true;
			$rc = $this->runDataFile($secdbData, 'tiki_secdb', $truncateTable);
			if ($rc == false) {
				// The batch loader failed
				if (file_exists($secdb)) {
					// Run single inserts
					$this->runFile($secdb, false);
				}
			}
		} elseif (file_exists($secdb)) {
			// Run single inserts
			$this->runFile($secdb, false);
		}
		foreach (Patch::getPatches([Patch::NOT_APPLIED]) as $patchName => $patch) {
			try {
				$this->installPatch($patchName);
			} catch (Exception $e) {
				if ($e->getCode() != 2) {
					throw $e;
				}
			}
		}

		foreach ($this->scripts as $script) {
			$this->runScript($script);
		}
	} // }}}

	/**
	 * @param $patch
	 * @param $force true if the patch should be applied even if already marked as applied
	 * @throws Exception Code 1 if unknown patch, 2 if application attempt fails, 3 if patch was already installed and $force is false
	 */
	function installPatch($patch, $force = false) // {{{
	{
		if (! $force && isset(Patch::$list[$patch]) && Patch::$list[$patch]->isApplied()) {
			throw new Exception('Patch already applied', 3);
		}

		$schema = __DIR__ . "/schema/$patch.sql";
		$script = __DIR__ . "/schema/$patch.php";
		$profile = __DIR__ . "/schema/$patch.yml";

		$pre = "pre_$patch";
		$post = "post_$patch";
		$standalone = "upgrade_$patch";

		if (file_exists($script)) {
			require $script;
			$status = true;
		}

		global $dbs_tiki;
		$local_php = TikiInit::getCredentialsFile();
		if (is_readable($local_php)) {
			require($local_php);
			unset($db_tiki, $host_tiki, $user_tiki, $pass_tiki);
		}

		if (function_exists($standalone)) {
			$status = $standalone($this);
			if (is_null($status)) {
				$status = true;
			}
		} else {
			if (function_exists($pre)) {
				$pre($this);
			}

			if (file_exists($profile)) {
				$status = $this->applyProfile($profile);
			} else {
				try {
					$status = $this->runFile($schema);
				} catch (Exception $e) {
				}
			}

			if (function_exists($post)) {
				$post($this);
			}
		}
		if (! isset($status)) {
			if (array_key_exists($patch, Patch::$list)) {
				throw new LogicException('Patch not found');
			} else {
				throw new Exception('No such patch', 1);
			}
		} elseif (! $status) {
			throw new Exception('Patch application failed', 2);
		} else {
			Patch::$list[$patch]->record();
		}
	} // }}}

	/**
	 * @param $script
	 */
	function runScript($script) // {{{
	{
		$file = __DIR__ . "/script/$script.php";

		if (file_exists($file)) {
			require $file;
		}

		if (function_exists($script)) {
			$script($this);
		}

		$this->executed[] = $script;
	} // }}}


	private function applyProfile($profileFile)
	{
		// By the time a profile install is requested, the installation should be functional enough to work
		require_once 'tiki-setup.php';
		$directory = dirname($profileFile);
		$profile = substr(basename($profileFile), 0, -4);

		$profile = Tiki_Profile::fromFile($directory, $profile);

		$tx = $this->begin();

		$installer = new Tiki_Profile_Installer;
		$ret = $installer->install($profile);

		$tx->commit();

		return $ret;
	}

	/**
	 * Batch insert from a mysql data file
	 *
	 * @param $file				MySQL export file
	 * @param $targetTable		Target table
	 * @param $clearTable=true	Flag saying if the target table should be truncated or not
	 * @return bool
	 */
	function runDataFile($file, $targetTable, $clearTable = true) // {{{
	{
		if (! is_file($file) || ! $command = file_get_contents($file)) {
			print('Fatal: Cannot open ' . $file);
			exit(1);
		}

		if ($clearTable == true) {
			$statement = 'truncate table ' . $targetTable;
			$this->query($statement);
		}

		// LOAD DATA INFILE doesn't like single \ directory separators. Replace with \\
		$inFile = str_replace('\\', '\\\\', $file);

		$status = true;
		$statement = 'LOAD DATA INFILE "' . $inFile . '" INTO TABLE ' . $targetTable;
		if ($this->query($statement) === false) {
			$status = false;
		}
		return $status;
	}

	/**
	 * @param $file
	 * @return bool
	 */
	function runFile($file, $convertFormat = true) // {{{
	{
		if (! is_file($file) || ! $command = file_get_contents($file)) {
			throw new Exception('Fatal: Cannot open ' . $file);
		}

		// split the file into several queries?
		$statements = preg_split("#(;\s*\n)|(;\s*\r\n)#", $command);
		$statements = array_filter($statements, function ($st) {
			return trim($st) && preg_match('/^\s*(?!-- )/m', $st);
		});

		$this->queries['currentFile'] = basename($file);
		$this->queries['total'] += count($statements);

		$status = true;
		foreach ($statements as $statement) {
			if ($this->useInnoDB && $convertFormat) {
				// Convert all MyISAM statments to InnoDB
				$statement = str_ireplace("MyISAM", "InnoDB", $statement);
			}

			if ($this->query($statement, [], -1, -1, true, $file) === false) {
				$status = false;
			}

			$this->queries['executed'] += 1;
			$this->queries['currentStmt'] = $statement;
			$this->notify();
		}

		$this->queries['currentFile'] = '';
		$this->queries['currentStmt'] = '';

		return $status;
	} // }}}

	/**
	 * @param null $query
	 * @param array $values
	 * @param $numrows
	 * @param $offset
	 * @param bool $reporterrors
	 * @param string $patch
	 * @return bool
	 */
	function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = true, $patch = '') // {{{
	{
		$error = '';
		$result = $this->queryError($query, $error, $values);

		if ($result && empty($error)) {
			$this->queries['successful'][] = $query;
			return $result;
		} else {
			$this->queries['failed'][] = [$query, $error, substr(basename($patch), 0, -4)];
			return false;
		}
	} // }}}

	/**
	 * @throws Exception In case of filesystem access issue
	 */
	function buildPatchList()
	{
		$patches = [];
		foreach (['sql', 'yml', 'php' /* "php" for standalone PHP scripts */] as $extension) {
			$files = glob(__DIR__ . '/schema/*_*.' . $extension); // glob() does not portably support brace expansion, hence the loop
			if ($files === false) {
				throw new Exception('Failed to scan patches');
			}
			foreach ($files as $file) {
				$filename = basename($file);
				$patches[] = substr($filename, 0, -4);
			}
		}
		$patches = array_unique($patches);

		$installed = [];
		if ($this->tableExists('tiki_schema')) {
			$installed = $this->table('tiki_schema')->fetchColumn('patch_name', []);
		}

		if (empty($installed)) {
			// Erase initial error
			$this->queries['failed'] = [];
		}

		Patch::$list = [];
		sort($patches);
		foreach ($patches as $patchName) {
			if (in_array($patchName, $installed)) {
				$status = Patch::ALREADY_APPLIED;
			} else {
				$status = Patch::NOT_APPLIED;
			}
			$patch = new Patch($patchName, $status);
			$patch->optional = substr($patchName, 0, 8) == 'optional'; // Ignore patches starting with "optional". These patches have drawbacks and should be manually run by informed administrators.
			Patch::$list[$patchName] = $patch;
		}
	}


	function buildScriptList() // {{{
	{
		$files = glob(__DIR__ . '/script/*.php');
		if (empty($files)) {
			return;
		}
		foreach ($files as $file) {
			if (basename($file) === "index.php") {
				continue;
			}
			$filename = basename($file);
			$this->scripts[] = substr($filename, 0, -4);
		}
	} // }}}

	/**
	 * @param $tableName
	 * @return bool
	 */
	function tableExists($tableName) // {{{
	{
		$list = $this->listTables();
		return in_array($tableName, $list);
	} // }}}

	function isInstalled() // {{{
	{
		return $this->tableExists('tiki_preferences');
	} // }}}

	/**
	 * @return bool
	 */
	function requiresUpdate() // {{{
	{
		return count(Patch::getPatches([Patch::NOT_APPLIED])) > 0;
	} // }}}
	function checkInstallerLocked() // {{{
	{
		$iniFile = __DIR__ . '/../db/lock';


		if (! is_readable($iniFile)) {
			return 1;
		}
	}
	private function getBaseImage() // {{{
	{
		$iniFile = __DIR__ . '/../db/install.ini';

		$ini = [];
		if (is_readable($iniFile)) {
			$ini = parse_ini_file($iniFile);
		}

		$direct = __DIR__ . '/../db/custom_tiki.sql';
		$fetch = null;
		$check = null;

		if (isset($ini['source.type'])) {
			switch ($ini['source.type']) {
				case 'local':
					$direct = $ini['source.file'];
					break;
				case 'http':
					$fetch = $ini['source.file'];
					if (isset($ini['source.md5'])) {
						$check = $ini['source.md5'];
					}
					break;
			}
		}

		if (is_readable($direct)) {
			return $direct;
		}

		if (! $fetch) {
			return;
		}

		$cacheFile = __DIR__ . '/../temp/cache/sql' . md5($fetch);

		if (is_readable($cacheFile)) {
			return $cacheFile;
		}

		$read = fopen($fetch, 'r');
		$write = fopen($cacheFile, 'w+');

		if ($read && $write) {
			while (! feof($read)) {
				fwrite($write, fread($read, 1024 * 100));
			}

			fclose($read);
			fclose($write);

			if (! $check || $check == md5_file($cacheFile)) {
				return $cacheFile;
			} else {
				unlink($cacheFile);
			}
		}
	} // }}}

	/**
	 * Use this if the default for a preference changes to preserve the old default behaviour on upgrades
	 *
	 * @param string $prefName
	 * @param string $oldDefault
	 */
	function preservePreferenceDefault($prefName, $oldDefault)
	{

		if ($this->tableExists('tiki_preferences')) {
			$tiki_preferences = $this->table('tiki_preferences');
			$hasValue = $tiki_preferences->fetchCount(['name' => $prefName]);

			if (empty($hasValue)) {	// old value not in database so was on default value
				$tiki_preferences->insert(['name' => $prefName, 'value' => $oldDefault]);
			}
		}
	}

	public function attach(SplObserver $observable)
	{
		if (method_exists($observable, 'update')) {
			$this->observers->attach($observable);
		} else {
			throw new Exception('Observable should implement `update` method');
		}
	}

	public function detach(SplObserver $observable)
	{
		$this->observers->detach($observable);
	}

	public function notify()
	{
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}

	/**
	 * Compares the charset encoding of the database with the one from tiki_schema, column patch_name (used as reference)
	 *
	 * If they are different, attempts to update teh default charset and collation from the database to
	 * match the one from tiki_schema, as it should be the reference for the encoding of that tiki database.
	 * The key case for both charset not to match is when the tiki db was restored to a new db but the encoding
	 * of that new db was not set to the right values. That will then cause that new tables won't be created with
	 * the right encoding (aligned with the rest of the tiki tables)
	 */
	protected function assureDefaultCharSetIsAlignedWithTikiSchema()
	{
		$databaseInfoResult = $this->query(
			'SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = DATABASE()'
		);
		if (! $databaseInfoResult || ! $databaseInfo = $databaseInfoResult->fetchRow()) {
			return;
		}

		$tableInfoResult = $this->query(
			'SELECT TABLE_SCHEMA, CHARACTER_SET_NAME, COLLATION_NAME from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "tiki_schema" AND COLUMN_NAME="patch_name"'
		);
		if (! $tableInfoResult || ! $tableInfo = $tableInfoResult->fetchRow()) {
			return;
		}

		if (! $databaseInfo || ! $tableInfo) { // if we cant retrieve the info we can not do anything
			return;
		}

		if ($databaseInfo['DEFAULT_CHARACTER_SET_NAME'] === $tableInfo['CHARACTER_SET_NAME']
			&& $databaseInfo['DEFAULT_COLLATION_NAME'] === $tableInfo['COLLATION_NAME']) {
			// all OK, charset and collation are aligned
			return;
		}

		// Info is not aligned, forcing to align the default values for the database with tiki_schema
		// Someone may have restored the db without setting the right default values for teh database for instance.
		switch ($tableInfo['CHARACTER_SET_NAME']) {
			case 'utf8':
				$this->query(
					'ALTER DATABASE `' . $tableInfo['TABLE_SCHEMA'] . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci'
				);
				break;
			case 'utf8mb4':
				$this->query(
					'ALTER DATABASE `' . $tableInfo['TABLE_SCHEMA'] . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
				);
				break;
			default:
				// we will only attempt to align for some char sets, other configuration needs to be done manually
				break;
		}
	}
}
