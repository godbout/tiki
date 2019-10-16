#!/usr/bin/php
<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tiki\Command\ConsoleSetupException;
use Tiki\Command\ConsoleApplicationBuilder;

/**
 * Sets up the environment that the console will run in.
 * Initializes Tiki to the greatest capacity available,
 * and sets constants to define what state Tiki is being run in.
 */


if (http_response_code() !== false) {
	die('Only available through command-line.');
}
	/** Present if we are in the Tiki console. */
const TIKI_CONSOLE = 1;


declare(ticks=1); // how often to check for signals
if (function_exists('pcntl_signal')) {
	$exit = function () {
		error_reporting(
			0
		); // Disable error reporting, misleading backtrace on kill
		exit;
	};

	pcntl_signal(SIGTERM, $exit);
	pcntl_signal(SIGHUP, $exit);
	pcntl_signal(SIGINT, $exit);
}

try {
	$bypass_siteclose_check = true;
	require_once 'tiki-setup.php';

	if (Installer::getInstance()->requiresUpdate()) {
		throw new ConsoleSetupException('Database Needs updating', 1004);
	}
	/**
	 * @var int The code representing different stages of Tki functioning. Each builds on the next.
	 * Auto-loading is always present. Most codes show progressive stages of tiki-setup.php being loaded.
	 *          * 1001 - Tiki is not installed (we are running in auto-load only mode)
	 *          * 1002 - Database not initialised (but Tiki is installed)
	 *          * 1003 - Tiki-Setup stopped by Database errors - probably because the database needs updating (and database initialized)
	 *			* 1004 - Tiki-Setup completed successfully (but the database is not up to date)
	 *			* 1100 - The database is up to date (and Tiki-setup completed successfully)
	 */
	$statusCode = 1100; // this code denotes everything works perfectly :)
} catch (ConsoleSetupException $e) {
	$statusCode = $e->getCode();
} catch (Exception $e) {
	$statusCode = 1001;
	$exceptionToRender = $e;
}

/**
 * Define our constants, based on what error (if any) was thrown
 * @see $statusCode For explanations on what these constants these do.
 */
define('IS_INSTALLED', $statusCode > 1001);
define('DB_STATUS', $statusCode > 1002);
define('DB_TIKI_SETUP', $statusCode > 1003);
define('DB_SYNCHRONAL', $statusCode > 1004);

$input = new ArgvInput();
$_SERVER['TIKI_VIRTUAL'] = $input->getParameterOption(['--site']) ?: null;

if (DB_TIKI_SETUP) {
	$asUser = $input->getParameterOption(['--as-user']) ?: 'admin';
	if (TikiLib::lib('user')->user_exists($asUser)) {
		$permissionContext = new Perms_Context($asUser);
	}
}

$output = new ConsoleOutput();
$console = new ConsoleApplicationBuilder();
$console = $console->create();
$console->setAutoExit(false);
try {
	$console->run(null, $output);
} catch (Exception $e) {
	$output->write('<comment>A error was encountered while running a command</comment>');
	$console->renderException($e, $output);
}
$output->writeln('');

if ($input->getFirstArgument() === null) {
	$output->write('Tiki Status: ');
	$output->write('<options=bold>Autoloading</>->');
	$output->write((IS_INSTALLED ? '<options=bold>' : '<fg=red>') . 'Installed</>->');
	$output->write((DB_STATUS ? '<options=bold>' : '<fg=red>') . 'Database-Running</>->');
	$output->write((DB_TIKI_SETUP ? '<options=bold>' : '<fg=red>') . 'Tiki-Initialized</>->');
	$output->writeln((DB_SYNCHRONAL ? '<options=bold>' : '<fg=red>') . 'Database-in-Sync</>');
	$output->writeln('');
}
if (isset($exceptionToRender)) {
	$output->write('<comment>An unexpected error interrupted console initialization</comment>');
	$console->renderException($exceptionToRender, $output);
}

