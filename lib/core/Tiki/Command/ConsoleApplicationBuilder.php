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
use TikiInit;

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
	 * When you need to register a new command, just add it to the right group, or create a new group if
	 * you need to test a new/different condition to register / not register a command.
	 *
	 * @return array the list of commands, grouped by test
	 */
	protected static function listOfRegisteredConsoleCommands() : array
	{
		return [
			'checkVendorsLoaded' => [
				'action' => [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_CALLABLE],
				'commands' => [
					new ConfigureCommand,
					new InstallerLockCommand,
					new ScssCompileCommand,
					new EnglishUpdateCommand,
					new VCSUpdateCommand,
					new FixBOMandUnixCommand,
					new GetStringsCommand,
				],
			],
			'checkIsVCS' => [
				'action' => [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE],
				'commands' => [
					new VCSUpdateCommand,
					new FixSVNKeyIdsCommand,
					new SemiAutoMergeCommand,
				],
			],
			'checkIsDatabaseAvailable' => [
				'action' => [
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
				],
				'commands' => [
					new CacheClearCommand,
					new CacheGenerateCommand,
					new BackupDBCommand,
					new BackupFilesCommand,
					new ProfileBaselineCommand,
					new PluginApproveRunCommand,
					new PluginListRunCommand,
					new PluginRefreshRunCommand,
					new PatchCommand,
				],
			],
			'checkTikiSetupComplete' => [
				'action' => [
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
				],
				'commands' => [
					new PreferencesGetCommand,
					new PreferencesSetCommand,
					new PreferencesDeleteCommand,
					new PreferencesExportCommand,
				],
			],
			'checkDatabaseUpToUpdate' => [
				'action' => [
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
				'commands' => [
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
					new UsersListCommand,
					new UsersPasswordCommand,
					new StatsCommand,
				],
			],
			'checkConfigurationIsAvailable' => [
				'action' => [
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
				'commands' => [
					new InstallCommand,
					new MultiTikiListCommand,
					new MultiTikiMoveCommand,
				],
			],
			'checkConfigurationAndDatabaseIsAvailable' => [
				'action' => [
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
				'commands' => [
					new UpdateCommand,
				],
			],
			'checkIsOCRAvailable' => [
				'action' => [
					UnavailableException::CHECK_DATABASE => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
				'commands' => [
					new OCRFileCommand,
					new OCRAllCommand,
					new OCRStatusCommand,
					new OCRSetCommand,
				],
			],
			'checkProfileInfoExists' => [
				'action' => [UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_PUBLISHED],
				'commands' => [
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
			],
			'checkForLocalRedactDb' => [
				'action' => [
					UnavailableException::CHECK_INSTALLED => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
				],
				'commands' => [
					new RedactDBCommand,
				],
			],
			'checkIsDevModeAndDatabase' => [
				'action' => [
					UnavailableException::CHECK_VCS => self::ACTION_NOT_CALLABLE,
					UnavailableException::CHECK_DEV => self::ACTION_NOT_PUBLISHED,
					UnavailableException::CHECK_DEFAULT => self::ACTION_NOT_AVAILABLE,
					],
				'commands' => [
					new VendorSecurityCommand,
				],
			],
		];
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
	 *    checkIsInstalled -> checkIsDatabaseAvailable -> checkTikiSetupComplete -> checkDatabaseUpToUpdate
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
				'You mist be running Tiki as a VCS see: https://dev.tiki.org/Get-code',
				UnavailableException::CHECK_VCS
			);
		}
	}

	/**
	 * Checks if the the development vendor files are installed.
	 *
	 * @throws CommandUnavailableException If development files are not installed.
	 */
	protected function checkIsDevMode () : void
	{
		$this->checkIsVCS();

		// check to see if something from the dev-mode packages has been auto-loaded
		if (! class_exists('PHPUnit\Framework\TestCase')) {
			throw new UnavailableException(
				'You need to be running in dev mode. To Fix run: ./temp/composer.phar update --prefer-dist --working-dir="vendor_bundled"',
				UnavailableException::CHECK_DEV
			);
		}
	}

	/**
	 * Check that Tiki is installed.
	 * WARNING: This NO LONGER checks if the database is working.
	 *
	 * @throws CommandUnavailableException When Tiki is not installed.
	 * @see checkIsDatabaseAvailable()
	 */
	protected function checkIsInstalled() : void
	{
		if (! IS_INSTALLED) {
			throw new UnavailableException(
				'Tiki must be installed first. See http://doc.tiki.org/Installation for more information.',
				UnavailableException::CHECK_INSTALLED
			);
		}
	}

	/**
	 * Check if the Tiki database is working.
	 * Since Tiki must be installed before a connection can be made, we also check if Tiki is installed first.
	 *
	 * @throws CommandUnavailableException When the database can not be initialized.
	 */
	protected function checkIsDatabaseAvailable() : void
	{
		// we want to provide the right feedback, so lets check pre-requirements first.
		$this->checkIsInstalled();
		if (! DB_STATUS) {
			throw new UnavailableException(
				'Cannot initiate Database. Is your database down?',
				UnavailableException::CHECK_DATABASE
			);
		}
	}

	/**
	 * Checks if tiki-setup has completed successfully, without any database errors.
	 * The database has previously been loaded, and much of tiki-check is likely to hae completed. However
	 * it is likely that critical changes were made to Tiki that causes severe database errors.
	 *
	 * @throws CommandUnavailableException WHen Tiki-setup.php did not complete. (core database errors)
	 */
	protected function checkTikiSetupComplete() : void
	{
		$this->checkIsDatabaseAvailable();
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
	protected function checkDatabaseUpToUpdate() : void
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
	 * Check if db configuration is available
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkConfigurationIsAvailable() : void
	{
		$this->checkIsInstalled();
		$local_php = TikiInit::getCredentialsFile();
		if (is_readable($local_php)) {
			// TikiInit::getCredentialsFile will reset all globals below, requiring $local_php again to restore the environment.
			global $api_tiki, $db_tiki, $dbversion_tiki, $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki, $tikidomain, $dbfail_url;
			require $local_php;
		}
		if (! (is_file($local_php) || TikiInit::getEnvironmentCredentials())) {
			throw new UnavailableException('Credentials file (local.php) not available. ', 310);
		}
	}

	/**
	 * Check if db configuration is available
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkConfigurationAndDatabaseIsAvailable() : void
	{
		$this->checkConfigurationIsAvailable();
		$this->checkIsDatabaseAvailable();
	}

	/**
	 * Check if the profile info.ini file exists
	 *
	 * @throws CommandUnavailableException if the info.ini file is not present
	 */
	protected function checkProfileInfoExists() : void
	{
		$this->checkIsDatabaseAvailable();
		if (! file_exists(TIKI_PATH . '/profiles/info.ini')) {
			throw new UnavailableException('The /profiles/info.ini file does not exist', 311);
		}
	}

	/**
	 * Checks if the db configuration for redact exists and a "redact" vhost is being used.
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkForLocalRedactDb() : void
	{
		$this->checkIsDatabaseAvailable();
		if (! isset($_SERVER['TIKI_VIRTUAL']) || $_SERVER['TIKI_VIRTUAL'] !== 'redact' || ! is_file(TIKI_PATH . '/db/redact/local.php')) {
			throw new UnavailableException('The /profiles/info.ini file does not exist', 312);
		}
	}

	/**
	 * Check if OCR is available
	 *
	 * @throws CommandUnavailableException if OCR is unavailable
	 */
	protected function checkIsOCRAvailable() : void
	{
		$this->checkDatabaseUpToUpdate();

		global $prefs;

		if (! empty($prefs['ocr_enable']) && $prefs['ocr_enable'] !== 'y') {
			throw new UnavailableException('You need to enable your Tiki OCR preference before continuing.', 313);
		}
	}

	/**
	 * Checks if the the composer development vendor files are installed.
	 *
	 * @throws CommandUnavailableException
	 */
	protected function checkIsDevModeAndDatabase() : void
	{
		$this->checkIsDevMode();
		$this->checkIsDatabaseAvailable();
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
	public function create( bool $returnLastInstance = false) : Application
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
		 * @var  $condition string The name of the check method to be executed
		 * @var  $CommandGroupDefinition array A group of commands that get evaluated under the same checks
		 */
		foreach (self::listOfRegisteredConsoleCommands() as $condition => $CommandGroupDefinition) {
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
				 * @see listOfRegisteredConsoleCommands() Status codes defined here
				 */
				foreach ($CommandGroupDefinition['action'] as $code => $check) {
					if (($errorCode <= $code) && ($code <= $actionCode)) {
						$actionCode = $code;
					}
				}
			}
			/** $actionStatus True when available, error message when not available */
			$actionStatus = $CommandGroupDefinition['action'][$actionCode];
			// if actions are 'not callable', then dont't evaluate the rest of the commands in this group.
			if ($availableStatus !== true && $actionStatus === self::ACTION_NOT_CALLABLE) {
				break;
			}

			/** @var $command ConfigureCommand A single command that will be evaluated for how-if it is added to the console */
			foreach ($CommandGroupDefinition['commands'] as $command) {
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
