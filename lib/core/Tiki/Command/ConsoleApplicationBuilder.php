<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Command\CommandUnavailableException as UnavailableException;

/**
 * Builds the Tiki console application
 *
 * @package Tiki\Command
 */
class ConsoleApplicationBuilder
{
	/**
	 * When command checks fail, the command will be listed with a 'not available' message.
	 * WILL have validation error messages displayed when called directly.
	 * WILL be available for 'command not found suggestions'.
	 */
	protected const ACTION_NOT_AVAILABLE = 1;
	/**
	 * When command checks fail, the command will be hidden from being listed.
	 * WILL have validation error messages displayed when called directly.
	 * WILL be available for 'command not found suggestions'.
	 */
	protected const ACTION_NOT_PUBLISHED = 2;
	/**
	 * The command will not be listed, even when passing command checks
	 * WILL have validation error messages displayed when called directly.
	 * WILL be available for 'command not found suggestions'.
	 */
	protected const ACTION_UNLISTED = 3;

	/**
	 * When command checks fail, the command will be unregistered (not present).
	 * WILL NOT have validation errors when called (will appear as though does not exist)
	 * WILL NOT be available for 'command not found suggestions'
	 */
	protected const ACTION_NOT_CALLABLE = 4;

	protected static $lastInstance;

	/**
	 * List of commands registered on the console
	 *
	 * When you need to register a new command, just add it to the right group.
	 *
	 * You can also create another group if you need to set specific conditions for when commands
	 * will be registered (300x series commands).
	 *
	 * Within the 200 series commands the convention is for unavailable commands to be listed one 'tear'
	 * above the highest registered state. So if there is no database function, you can see commands that
	 * are available when the database is running, but not those available when the database is installed, etc.
	 * Within the 300 series commands (feature specific commands) convention dictates that commands
	 * are not listed until the feature-specific condition is met. So for example, profile commands
	 * are not shown unless profiles are configured.
	 *
	 * The groups are (roughly) in order of least Tiki functioning needed to greatest. This is to ensure
	 * that potential errors in later commands won't prevent more basic commands from being called.
	 * So the 100x 200x and 300x series checks should be performed in order.
	 *
	 * Actions can be set by specifying an error code, along with the action to take.
	 * Actions are applied against any lessor error code.
	 * What error codes are produced is partly determined by the order in which checks are performed.
	 *
	 * @return array the list of commands, grouped by test
	 */
	protected static function registeredCommands(): array
	{
		return [[
			'condition'	=> 'checkVendorsLoaded',
			'actions'	=> [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED,],
			'commands'	=> [
				new ConfigureCommand,
				new InstallerLockCommand,
				new ScssCompileCommand,
				new EnglishUpdateCommand,
				new VCSUpdateCommand,
				new FixBOMandUnixCommand,
				new GetStringsCommand,
				],
			],[
			'condition'	=> 'checkIsVCS',
			'actions'	=> [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_CALLABLE],
			'commands'	=> [
				new VCSUpdateCommand,
				new FixSVNKeyIdsCommand,
				new SemiAutoMergeCommand,
				new DevConfigureCommand,
				],
			],[
			'condition'	=> 'checkIsDevMode',
			'actions'	=> [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED,],
			'commands'	=> [
				new DevFixStyleCommand,
				new DevUnInstallCommand,
				],
			],[
			'condition'	=> 'checkIsDbRunning',
			'actions'	=> [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,],
			'commands'	=> [
					new InstallCommand,
					new MultiTikiListCommand,
					new MultiTikiMoveCommand,
				],
			],[
			'condition'	=> 'checkIsDatabaseInstalled',
			'actions'	=> [
					UnavailableException::CHECK_RUNNING => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
			'commands'	=> [
				new CacheClearCommand,
				new CacheGenerateCommand,
				new BackupDBCommand,
				new BackupFilesCommand,
				new ProfileBaselineCommand,
				new PluginApproveRunCommand,
				new PluginListRunCommand,
				new PluginRefreshRunCommand,
				new PatchCommand,
				new UpdateCommand,
				],
			],[
			'condition'	=> 'checkTikiSetupComplete',
			'actions'	=> [
				UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
			],
			'commands'	=> [
				new PreferencesGetCommand,
				new PreferencesSetCommand,
				new PreferencesDeleteCommand,
				new PreferencesExportCommand,
				],
			],[
			'condition'	=> 'checkDatabaseUpToDate',
			'actions'	=> [
				UnavailableException::CHECK_VCS => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
			'commands'	=> [
				new PackageDisableCommand,
				new PackageEnableCommand,
				new DailyReportSendCommand,
				new FakerCommentsCommand,
				new FakerTrackerCommand,
				new GalleryMigrateCommand,
				new GoalCheckCommand,
				new FilesBatchuploadCommand,
				new FilesCheckCommand,
				new FilesCopyCommand,
				new FilesDeleteoldCommand,
				new FilesIndexCommand,
				new FilesMoveCommand,
				new IndexRebuildCommand,
				new IndexOptimizeCommand,
				new IndexCatchUpCommand,
				new ListExecuteCommand,
				new MailInPollCommand,
				new MailQueueSendCommand,
				new NotificationDigestCommand,
				new ObjectsNotifyMaintainersCommand,
				new PackageClearCacheCommand,
				new PackageInstallCommand,
				new PackageListCommand,
				new PackageRemoveCommand,
				new PackageUpdateCommand,
				new ProfileForgetCommand,
				new ProfileInstallCommand,
				new ProfileExport\Init,
				new RecommendationBatchCommand,
				new RefreshRssCommand,
				new RssClearCacheCommand,
				new SchedulerRunCommand,
				new ThemeInstallCommand,
				new ThemeRemoveCommand,
				new ThemeUpdateCommand,
				new SchedulerHealCommand,
				new TrackerExportCommand,
				new TrackerImportCommand,
				new SitemapGenerateCommand,
				new TikiInfoCommand,
				new TrackerClearCommand,
				new AdminIndexRebuildCommand,
				new UserCreateCommand,
				new UsersListCommand,
				new UsersPasswordCommand,
				new StatsCommand,
				],
			],[
			'condition'	=> 'checkIsOCRAvailable',
			'actions'	=> [
				UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_TIKI_SETUP => self::ACTION_NOT_AVAILABLE,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED,
				],
			'commands'	=> [
				new OCRFileCommand,
				new OCRAllCommand,
				new OCRStatusCommand,
				new OCRSetCommand,
				],
			],[
			'condition'	=> 'checkProfileInfoExists',
			'actions'	=> [
				UnavailableException::CHECK_RUNNING => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_AVAILABLE,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED,
				],
			'commands'	=> [
				new ProfileExport\ActivityRuleSet,
				new ProfileExport\ActivityStreamRule,
				new ProfileExport\Article,
				new ProfileExport\ArticleTopic,
				new ProfileExport\ArticleType,
				new ProfileExport\AllModules,
				new ProfileExport\Calendar,
				new ProfileExport\Category,
				new ProfileExport\FileGallery,
				new ProfileExport\Forum,
				new ProfileExport\Goal,
				new ProfileExport\GoalSet,
				new ProfileExport\Group,
				new ProfileExport\IncludeProfile,
				new ProfileExport\Menu,
				new ProfileExport\Module,
				new ProfileExport\Preference,
				new ProfileExport\RatingConfig,
				new ProfileExport\RatingConfigSet,
				new ProfileExport\RecentChanges,
				new ProfileExport\Rss,
				new ProfileExport\Tracker,
				new ProfileExport\TrackerField,
				new ProfileExport\TrackerItem,
				new ProfileExport\WikiPage,
				new ProfileExport\Finalize,
				],
			],[
			'condition'	=> 'checkForLocalRedactDb',
			'actions'	=> [
				UnavailableException::CHECK_RUNNING => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_AVAILABLE,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED,
				],
			'commands' => [new RedactDBCommand,],
			],[
			'condition'	=> 'checkIsDevModeAndDatabase',
			'actions'	=> [
				UnavailableException::CHECK_VCS => self::ACTION_NOT_CALLABLE,
				UnavailableException::CHECK_DEV => self::ACTION_NOT_PUBLISHED,
				UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
			'commands'	=> [
				new VendorSecurityCommand,
				],
			]];
	}

	/*
	 * 1. State Checks (100x)
	 *    These do not require Tiki being installed or settings being loaded.
	 *    They reflect the technologies behind Tiki, or resources available.
	 *    checkVendorsLoaded -> checkIsVCS -> checkIsDevMode
	 *
	 * 2. Installation Checks (200x)
	 *    Standard Tiki checks that test the install status of Tiki
	 *    These are independent from the first checks. They generally test the state Tiki.
	 *    checkIsDbRunning -> checkIsDatabaseInstalled -> checkTikiSetupComplete -> checkDatabaseUpToDate
	 *
	 * 3. Feature checks (300x)
	 *    Check for aspects related to a feature of subset of Tiki.
	 *    These checks rely on one or a combination of the standard checks and in addition check the specialized need.
	 *
	 *    When creating a new command, be sure to run checks for the minimum State and Installation requirements so
	 *    the appropriate errors can be displayed to end users.
	 */

	/**
	 * For commands that we always want to register
	 * Minimum entry point up to now is running setup.sh but not installing Tiki.
	 * If vendor files have not been loaded, console.php will die, so this is a dummy check.
	 *
	 * @return void
	 */
	protected function checkVendorsLoaded() : void
	{
	}

	/**
	 * Check if Tiki is being run as Git or SVN.
	 *
	 * @throws CommandUnavailableException When SVN or GIT is not available.
	 */
	protected function checkIsVCS() : void
	{
		if (! (is_dir('.svn') || is_dir('.git'))) {
			throw new UnavailableException(
				'You must be running Tiki as a VCS see: https://dev.tiki.org/Get-code',
				UnavailableException::CHECK_VCS
			);
		}
	}

	/**
	 * Checks if the the development vendor files are installed.
	 *
	 * @throws CommandUnavailableException If development files are not installed.
	 */
	protected function checkIsDevMode() : void
	{
		$this->checkIsVCS();

		// check to see if something from the dev-mode packages has been auto-loaded
		if (! class_exists(\PHPUnit\Framework\TestCase::class)) {
			throw new UnavailableException(
				'You need to be running in dev mode. To Fix run: php console.php dev:configure',
				UnavailableException::CHECK_DEV
			);
		}
	}

	/**
	 * Check that the database is running. Does not require a Tiki database structure, just that MariaDB/MySQL connects.
	 *
	 * @throws CommandUnavailableException When Tiki is not installed, or complete database failure
	 */
	protected function checkIsDbRunning() : void
	{
		if (! DB_RUNNING) {
			throw new UnavailableException(
				'Your database must be running and have valid credentials in the local.php file. See http://doc.tiki.org/Installation for more information.',
				UnavailableException::CHECK_RUNNING
			);
		}
	}

	/**
	 * Check if the Tiki database is working.
	 * Since the database must be running before the database is installed, we also check for that.
	 *
	 * @throws CommandUnavailableException When the database can not be initialized.
	 */
	protected function checkIsDatabaseInstalled() : void
	{
		// we want to provide the right feedback, so lets check pre-requirements first.
		$this->checkIsDbRunning();
		if (! DB_STATUS) {
			throw new UnavailableException(
				'Cannot initiate Database. Probably because the database needs updating.',
				UnavailableException::CHECK_INSTALLED
			);
		}
	}

	/**
	 * Checks if tiki-setup.php has completed successfully, without any database errors.
	 * The database has previously been loaded, and much of tiki-check is likely to hae completed. However
	 * it is likely that critical changes were made to Tiki that causes severe database errors.
	 *
	 * @throws CommandUnavailableException When tiki-setup.php did not complete. (core database errors)
	 */
	protected function checkTikiSetupComplete() : void
	{
		$this->checkIsDatabaseInstalled();
		if (! DB_TIKI_SETUP) {
			throw new UnavailableException(
				'Database errors prevented tiki-setup from completing. Try running php console.php database:update',
				UnavailableException::CHECK_TIKI_SETUP
			);
		}
	}

	/**
	 * Check if the database does not require an update.
	 * We previously checked for severe database errors (checkTikiSetupComplete). But feature specific
	 * database errors might be present if this check fails.
	 *
	 * @throws CommandUnavailableException When the Tiki database needs updating. (feature specific database errors)
	 */
	protected function checkDatabaseUpToDate() : void
	{
		// we want to provide the right feedback, so lets check pre-requirements first.
		$this->checkTikiSetupComplete();

		if (! DB_SYNCHRONAL) {
			throw new UnavailableException(
				'The database needs to be updated. Solved by: php console.php database:update',
				UnavailableException::CHECK_UPDATED
			);
		}
	}

	/**
	 * Check if the profile info.ini file exists
	 *
	 * @throws CommandUnavailableException if the info.ini file is not present
	 */
	protected function checkProfileInfoExists() : void
	{
		if (! file_exists(TIKI_PATH . '/profiles/info.ini')) {
			throw new UnavailableException('The /profiles/info.ini file does not exist', 311);
		}
		$this->checkIsDatabaseInstalled();
	}

	/**
	 * Checks if the db configuration for redact exists and a "redact" vhost is being used.
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkForLocalRedactDb() : void
	{
		if (! isset($_SERVER['TIKI_VIRTUAL']) || $_SERVER['TIKI_VIRTUAL'] !== 'redact' || ! is_file(TIKI_PATH . '/db/redact/local.php')) {
			throw new UnavailableException('The /profiles/info.ini file does not exist', 312);
		}
		$this->checkIsDatabaseInstalled();
	}

	/**
	 * Check if OCR is available
	 *
	 * @throws CommandUnavailableException if OCR is unavailable
	 */
	protected function checkIsOCRAvailable() : void
	{

		// we check if the database is running first so we can safely check preferences
		$this->checkIsDatabaseInstalled();
		global $prefs;

		if (! empty($prefs['ocr_enable']) && $prefs['ocr_enable'] !== 'y') {
			throw new UnavailableException('You need to enable your Tiki OCR preference before continuing.', 313);
		}
		$this->checkDatabaseUpToDate();
	}

	/**
	 * Checks if the the composer development vendor files are installed.
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkIsDevModeAndDatabase() : void
	{
		$this->checkIsDevMode();
		$this->checkIsDatabaseInstalled();
	}

	/**
	 * Creates a console application
	 *
	 * Iterates over all commands in the list, and registers / doesn't register the commands in accordance with the result
	 * of the check function and the action configured for the command group
	 *
	 * @param boolean $returnLastInstance
	 * @return Application
	 */
	public function create(bool $returnLastInstance = false) : Application
	{
		if ($returnLastInstance && self::$lastInstance instanceof self) {
			return self::$lastInstance;
		}

		/** @var Application Console application that commands are added to, and finally returned. */
		$console = new Application;
		$console->setAutoExit(false);
		$console->setName('Tiki Console Tool');

		$commandCalled = $_SERVER['argv'][1] ?? false;

		/**
		 * @var  $condition	string	The name of the check method to be executed
		 * @var  $actions	array	List of actions that should apply to a group of commands
		 * @var  $commands	array	List of commands
		 */

		foreach (self::registeredCommands() as list('condition' => $condition, 'actions' => $actions, 'commands' => $commands)) {

			/** The code that will determine the action status of the command */
			$actionCode = UnavailableException::CHECK_DEFAULT;
			try {
				$this->$condition();
				/** true of the command is available, and error message if the command is unavailable */
				$availableStatus = true;
			} catch (UnavailableException $e) {
				$availableStatus = $e->getMessage();
				$errorCode = $e->getCode();
				/**
				 * Now we find the right status to apply.
				 * @see registeredCommands() Status codes defined here
				 */
				foreach ($actions as $code => $check) {
					if (($errorCode <= $code) && ($code <= $actionCode)) {
						$actionCode = $code;
					}
				}
			}
			/** $actionStatus True when available, error message when not available */
			$actionStatus = $actions[$actionCode];
			// if actions are 'not callable', then dont't evaluate the rest of the commands in this group.
			if ($availableStatus !== true && $actionStatus === self::ACTION_NOT_CALLABLE) {
				break;
			}

			/** @var $command ConfigureCommand A single command that will be evaluated for how-if it is added to the console */
			foreach ($commands as $command) {
				if ($actionStatus === self::ACTION_UNLISTED) {
					$command->setHidden(true);
				}
				if ($availableStatus !== true) {
					$command->setCode(function (InputInterface $input, OutputInterface $output) use ($availableStatus) {
							$output->writeln('Command not available at this stage.');
							$output->writeln('<error>' . $availableStatus . '</error>');
					});
					if ($command->getHelp()) {
						$command->setHelp('<fg=cyan>Command not available:</> ' . $command->getHelp());
					} else {
						$command->setHelp(
							'<fg=cyan>Command not available:</> ' .
							$command->getDescription() .
							"\n" .
							'<fg=cyan>Error preventing command execution:</> ' .
							$availableStatus
						);
					}
					$command->setDescription('<fg=cyan>Unavailable:</> ' . $command->getDescription());
					if ($actionStatus === self::ACTION_NOT_PUBLISHED) {
						$command->setHidden(true);
					}
				}
				$console->add($command);
				// If the command exactly matches one that was requested, stop processing further commands as they will not be used anyhow.
				if ($commandCalled === $command->getName()) {
					break 2;
				}
			}
		}

		self::$lastInstance = $console;

		return $console;
	}
}
