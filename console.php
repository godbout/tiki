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

set_error_handler("custom_error_handler"); // attempt to throw exceptions that we can catch
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

// we autoload if autoloading is available, otherwise we continue so Tiki can throw its regular errors.
if (include('vendor_bundled/vendor/autoload.php')) {

	// Set MultiTiki status before Tiki is initialized
	$input = new ArgvInput();
	$_SERVER['TIKI_VIRTUAL'] = $input->getParameterOption(['--site']) ?: null;
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
} catch (Throwable $e) {
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
} catch (Throwable $e) {
	$output->writeln('<comment>A error was encountered while running a command</comment>');
	if ($e instanceof Exception) {
		$console->renderException($e, $output);
	} else {
		$output->write('<error>' . $e->getMessage() . '</error> on line ' . $e->getLine() . ' of ' . $e->getFile());
	}
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
	$output->writeln('<comment>An unexpected error interrupted console initialization</comment>');
	if ($e instanceof Exception) {
		$console->renderException($e, $output);
	} else {
		$output->write('<error>' . $e->getMessage() . '</error> on line ' . $e->getLine() . ' of ' . $e->getFile());
	}
}

/**
 * Errors while using the console can be difficult because they normally end with the command providing no input.
 * Here we attempt to provide some feedback to the user so failed commands are not as cryptic.
 *
 * IF the error is not fatal, then we log it in PHP's error log (like it would have been done without this error handling)
 *
 * @param $number int Error number (type of error) provided
 * @param $message string Error Message provided
 * @param $file string The file name that the error occurred on
 * @param $line string The line number that the error occurred on
 *
 * @throws ErrorException When a fatal error is encountered
 */
function custom_error_handler($number, $message, $file, $line) : void
{
	// Determine if this error is one of the enabled ones in php config (php.ini, .htaccess, etc)
	$error_is_enabled = (bool)($number & ini_get('error_reporting') );

	// Fatal Errors
	// throw an Error Exception, to be handled by whatever Exception handling logic is available in this context
	if (in_array($number, [E_USER_ERROR, E_RECOVERABLE_ERROR]) && $error_is_enabled) {
		throw new ErrorException($message, 0, $number, $file, $line);
	}

	// Non-Fatal Errors (ERROR/WARNING/NOTICE)
	// Log the error if it's enabled, otherwise just ignore it
	if ($error_is_enabled) {
		error_log($message . ' on line ' . $line . ' of ' . $file, 0);
	}
}
