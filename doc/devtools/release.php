<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// ** This is the main script to release Tiki **
//
// To get the Tiki release HOWTO, try:
//    php doc/devtools/release.php --howto
//
// You can also get a detailed help on this script with:
//    php doc/devtools/release.php --help
//

define('TOOLS', __DIR__);
define('ROOT', realpath(TOOLS . '/../..'));
define('TEMP_DIR', 'temp');

define('CHANGELOG_FILENAME', 'changelog.txt');
define('CHANGELOG', ROOT . '/' . CHANGELOG_FILENAME);
define('COPYRIGHTS_FILENAME', 'copyright.txt');
define('COPYRIGHTS', ROOT . '/' . COPYRIGHTS_FILENAME);
define('SF_TW_MEMBERS_URL', 'http://sourceforge.net/p/tikiwiki/_members');
define('DEV_TW_MEMBERS_URL', 'http://dev.tiki.org/getTikiUser.php');
define('README_FILENAME', 'README');
define('README', ROOT . '/' . README_FILENAME);
define('LICENSE_FILENAME', 'license.txt');

define('PIPELINE_STATUS_PASSED', 'passed');
define('PIPELINE_STATUS_FAILED', 'failed');
define('PIPELINES_FETCH_AMOUNT', 25);

// Display all errors and warnings, including strict level
define('ERROR_REPORTING_LEVEL', E_ALL | E_STRICT);
error_reporting(ERROR_REPORTING_LEVEL);

chdir(ROOT . '/');

require_once ROOT . '/lib/setup/third_party.php';
require_once ROOT . '/doc/devtools/vcscommons.php';

if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    error("You need PHP version 5 or more to run this script\n");
}
$phpCommand = isset($_SERVER['_']) ? $_SERVER['_'] : 'php';
$phpCommandArguments = implode(' ', $_SERVER['argv']);

if (! ($options = get_options()) || $options['help']) {
    display_usage();
}

$vcs = 'svn';
if ($options['use-git']) {
    $vcs = 'git';
}

require_once TOOLS . '/' . $vcs . 'tools.php';


if ($options['devmode']) {
    $options['no-commit'] = true;
    $options['no-check-vcs'] = true;
    $options['no-first-update'] = true;
    $options['debug-packaging'] = true;
}
if ($options['howto']) {
    display_howto();
}


if (! check_bin_version()) {
    error("You need the VCS '" . getBinName() . "' program at least at version " . getMinVersion() . "\n");
}

if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
    error("Uncommitted changes exist in the working folder.\n");
}

include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();

if ($options['only-secdb']) {
    updateSecdb($TWV->version);
    exit;
}

$script = $_SERVER['argv'][0];
$version = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
$subrelease = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : '';

if (! preg_match("/^\d+\.\d+$/", $version)) {
    error("Version number should be in X.X format.\n");
}

$isPre = strpos($subrelease, 'pre') === 0;
if ($isPre) {
    $subrelease = substr($subrelease, 3);
    $pre = 'pre';
} else {
    $pre = '';
}
$splitedversion = explode('.', $version);
$mainversion = $splitedversion[0];

$check_version = $version . $subrelease;
if ($TWV->version !== $check_version && ! $options['devmode']) {
    error("The version in the code " . strtolower($TWV->version) . " differs from the version provided to the script $check_version.\nThe version should be modified in lib/setup/twversion.class.php to match the released version.");
}

echo color("\nTiki release process started for version '$version" . ($subrelease ? " $subrelease" : '') . "'\n", 'cyan');
if ($isPre) {
    echo color("The script is running in 'pre-release' mode, which means that no tag will be created.\n", 'yellow');
}

if (! $options['no-first-update'] && important_step('Update working copy to the last revision')) {
    echo "Update in progress...";
    update_working_copy('.');

    if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
        error("\rUncommitted changes exist in the working folder.\n");
    }
    $revision = get_revision('.');
    info("\r>> Checkout updated to revision $revision.");
}

if (empty($subrelease)) {
    $branch = $vcs == 'svn' ? "branches/$mainversion.x" : "$mainversion.x";
    $tag = "tags/$version";
    $packageVersion = $version;
    if (! empty($pre)) {
        $packageVersion .= ".$pre";
    }
    $secdbVersion = $version;
} else {
    $branch = $vcs == 'svn' ? "branches/$mainversion.x" : "$mainversion.x";
    $tag = "tags/$version$subrelease";
    $packageVersion = "$version.$pre$subrelease";
    $secdbVersion = "$version$subrelease";
}

if (! $options['no-readme-update'] && important_step("Update '" . README_FILENAME . "' file")) {
    update_readme_file($secdbVersion, $version);
    info('>> ' . README_FILENAME . ' file updated.');
    important_step('Commit updated ' . README_FILENAME . ' file', true, "[REL] Update " . README_FILENAME . " file for $secdbVersion");
}

if (! $options['no-lang-update'] && important_step("Update language files")) {
    passthru("$phpCommand get_strings.php");
    $removeFiles = glob('lang/*/language.php.old');
    foreach ($removeFiles as $rf) {
        unlink($rf);
    }
    unset($removeFiles);
    info('>> Language files updated and temporary files removed.');
    important_step('Commit updated language files', true, "[REL] Update language.php files for $secdbVersion");
}

if (! $options['no-changelog-update'] && important_step("Update '" . CHANGELOG_FILENAME . "' file (using final version number '$version')")) {
    if ($ucf = update_changelog_file($version)) {
        if ($ucf['nbCommits'] == 0) {
            info('>> Changelog updated (last commits were already inside)');
        } else {
            if ($ucf['sameFinalVersion']) {
                info(">> There were already some commits for the same final version number in the changelog. Merging them with the new ones.");
            }
            info(">> Changelog updated with {$ucf['nbCommits']} new commits (revision {$ucf['firstRevision']} to {$ucf['lastRevision']}), excluding duplicates, merges and release-related commits.");
        }
        important_step("Commit new " . CHANGELOG_FILENAME, true, "[REL] Update " . CHANGELOG_FILENAME . " for $secdbVersion");
    } else {
        error('Changelog update failed.');
    }
    unset($ucf);
}

$nbCommiters = 0;
if (! $options['no-copyright-update'] && important_step("Update '" . COPYRIGHTS_FILENAME . "' file (using final version number '$version')")) {
    if ($ucf = update_copyright_file($mainversion . '.0')) {
        info(
            "\r>> Copyrights updated: "
            . ($ucf['newContributors'] == 0 ? 'No new contributor, ' : "+{$ucf['newContributors']} contributor(s), ")
            . ($ucf['newCommits'] == 0 ? 'No new commit' : "+{$ucf['newCommits']} commit(s)")
        );
        important_step("Commit new " . COPYRIGHTS_FILENAME, true, "[REL] Update " . COPYRIGHTS_FILENAME . " for $secdbVersion");
    } else {
        error('Copyrights update failed.');
    }
}

if (! $options['no-check-db'] && important_step("Check Database related files and upgrade scripts")) {
    $error_msg = '';
    check_database_files_and_upgrade($mainversion, $error_msg) or error($error_msg . "If you want to disable this checks use --no-check-db\n");
    info('>> Current database scripts successfully passed the check.');
}

if (! $options['no-check-php'] && important_step("Check syntax of all PHP files")) {
    $error_msg = '';
    $dir = '.';
    check_php_syntax($dir, $error_msg, $options['no-check-php-warnings']) or error($error_msg);
    info('>> Current PHP code successfully passed the syntax check.');
}

if (! $options['no-check-smarty'] && important_step("Check syntax of all Smarty templates")) {
    $error_msg = '';
    require_once ROOT . '/lib/core/TikiDb.php';
    require_once ROOT . '/lib/core/TikiDb/Bridge.php';
    require_once ROOT . '/lib/language/Language.php';
    check_smarty_syntax($error_msg);
    info('>> Current Smarty code successfully passed the syntax check.');
}

if (! $options['no-secdb'] && important_step("Update SecDB file(s) 'db/tiki-secdb_{$version}_mysql.sql'")) {
    if (updateSecdb($TWV->version)) {
        important_step("Commit SecDB file changes", true, "[REL] SecDB for $secdbVersion");
    }
}

if ($isPre) {
    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
        echo color("\nMake sure these tarballs are tested by at least 3 different people.\n\n", 'cyan');
    } else {
        echo color("This was the last step.\n", 'cyan');
    }
} else {
    if (! $options['no-tagging']) {
        $tagAlreadyExists = tag_exists($tag, true);
        if ($tagAlreadyExists && important_step("The Tag '$tag' already exists: Delete the existing tag in order to create a new one")) {
            $commit_msg = "[REL] Deleting tag '$tag' in order to create a new one";
            if ($options['no-commit']) {
                print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
            } else {
                delete_tag($tag, $commit_msg);
                $tagAlreadyExists = false;
                info(">> Tag '$tag' deleted.");
            }
        }
        if (! $tagAlreadyExists) {
            update_working_copy('.');
            $revision = get_revision(ROOT);
            if (important_step("Tag release using branch '$branch' at revision $revision")) {
                $commit_msg = '[REL] Tagging release';
                if ($options['no-commit']) {
                    print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
                } else {
                    create_tag($tag, $commit_msg, $branch, $revision);
                    info(">> Tag '$tag' created.");
                }
            }
        }
    }

    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
    } else {
        info("This was the last step.\n");
    }
}

// Helper functions


/**
 *
 * Will remove old secdb files and generate a new one based on working copy files.
 *
 * @param $version string The current tiki version to use eg. 17.0 or 21.0RC1
 * @return bool true on success
 */
function updateSecdb($version)
{
    // first unset any preexisting files.
    echo(">>");
    $vcs = preg_match('/' . getBinName() . '$/', $version);

    // if we are not creating a release skip deleting old files.
    if (! $vcs) {
        $files = glob(ROOT . '/db/tiki-secdb_*_mysql.sql');
        foreach ($files as $file) {
            $file = escapeshellarg($file);
            delete_file($file);
        }
        echo(' Removed ' . count($files) . ' old secdb files.');

        $excludes = [];
    } else {
        $excludes = array_keys(files_differ(ROOT));
    }

    $file = "/db/tiki-secdb_{$version}_mysql.sql";

    if (! $fp = @fopen(ROOT . $file, 'w')) {
        error('The SecDB file "' . ROOT . $file . '" is not writable or can\'t be created.');

        return false;
    }
    $queries = [];
    build_secdb_queries(ROOT, $version, $queries, $excludes);

    if (! empty($queries)) {
        sort($queries);
        fwrite($fp, "start transaction;\n");
        fwrite($fp, "DELETE FROM `tiki_secdb`;\n");
        // This index was originally created with a size limit that would raise an error on some versions,
        // notably on 18.0. Since this file is executed before any patch in installer/schema, the fix had to
        // be done here. It's a quick operation because table is empty, so no harm in leaving this here forever.
        fwrite($fp, "ALTER TABLE `tiki_secdb` DROP PRIMARY KEY, ADD PRIMARY KEY (`filename`(171),`tiki_version`(20));\n\n");
        foreach ($queries as $q) {
            fwrite($fp, "$q\n");
        }
        fwrite($fp, "commit;\n");
    }
    fclose($fp);

    echo(" $file was generated.\n");
    if (! $vcs) {
        $file = escapeshellarg(ROOT . $file); // escape file name for use in command line.
        add($file);
    }

    return true;
}

/**
 * Similar to md5_check_dir in tiki-admin_security.php but creates the sql queries for /db/tiki-secdb_{$version}_mysql.sql
 *
 * @param string $dir
 * @param string $version
 * @param array $queries queries returned
 * @param array $excludes files to exclude when doing secdb on an svn or checkout
 */
function build_secdb_queries($dir, $version, &$queries, $excludes = [])
{
    $d = dir($dir);
    $link = null;

    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_link($entry)) {
            continue; // if is a symlink we should not run any hash
        }
        if (is_dir($entry)) {
            // do not descend and no CVS/Subversion files
            if ($e != '..' && $e != '.' && $e != 'CVS' && $e != '.git' && $e != '.gitignore' && $e != '.svn' && $entry != ROOT . '/temp' && $entry != ROOT . '/vendor_custom' && $entry != ROOT . '/_custom') {
                build_secdb_queries($entry, $version, $queries, $excludes);
            }
        } else {
            if (preg_match('/\.(sql|css|tpl|js|php)$/', $e) && realpath($entry) != __FILE__ && $entry != './db/local.php') {
                $file = '.' . substr($entry, strlen(ROOT));

                if (in_array($entry, $excludes)) {
                    continue;
                }

                // Escape filename. Since this requires a connection to MySQL (due to the charset), do so conditionally to reduce the risk of connection failure.
                if (! preg_match('/^[a-zA-Z!-9\/ _+.-@]+$/', $file)) {
                    if (! $link) {
                        $link = mysqli_connect();

                        if (mysqli_connect_errno()) {
                            global $phpCommand, $phpCommandArguments;
                            error(
                                "SecDB step failed because some filenames (e.g. {$file}) need escaping but no MySQL connection has been found (" . mysqli_connect_error() . ")."
                                . "\nTry this command line instead (replace HOST, USER and PASS by a valid MySQL host, user and password) :"
                                . "\n\n\t" . $phpCommand
                                . " -d mysqli.default_host=HOST -d mysqli.default_user=USER -d mysqli.default_pw=PASS "
                                . $phpCommandArguments . "\n"
                            );
                        }
                    }
                    $file = @mysqli_real_escape_string($link, $file);
                }

                if (is_readable($entry)) {
                    $hash = md5_file($entry);
                    $queries[] = "INSERT INTO `tiki_secdb` (`filename`, `md5_value`, `tiki_version`) VALUES('$file', '$hash', '$version');";
                }
            }
        }
    }
    $d->close();
}

/**
 *
 * Deletes a directory and its contents.
 *
 * @param string $dir directory to delete.
 * @return string|bool returns the filename that an error occurred in, false otherwise
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                @chmod($dir . "/" . $object, 0777);
                if (filetype($dir . "/" . $object) === 'dir') {
                    $error = rrmdir($dir . "/" . $object);
                    if ($error) {
                        return $error;
                    }
                } elseif (! @unlink($dir . "/" . $object)) {
                    return 'Could not delete ' . $dir . "/" . $object . "\n";
                }
            }
        }
        reset($objects);
        @unlink($dir . '/.DS_store');
        if (! @rmdir($dir)) {
            return 'Could not delete ' . $dir . "\n";
        }
    }

    return false;
}


/**
 *
 * Recursivley deletes specific files or directories
 *
 * @param string $src Directory to search through
 * @param array $files An array of file names to delete.
 */
function removeFiles($src, $files)
{
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $full = $src . '/' . $file;
            if (is_dir($full)) {
                $flag = false;

                foreach ($files as $delfile) {
                    if (basename($full) === $delfile) {
                        rrmdir($full);
                        $flag = true;

                        break;
                    }
                }
                if (! $flag) {
                    removeFiles($full, $files);
                }
            } else {
                foreach ($files as $delfile) {
                    if (basename($full) === $delfile) {
                        @chmod($full, 0777);
                        unlink($full);

                        break;
                    }
                }
            }
        }
    }
    closedir($dir);
}


/**
 *
 * Recursively sets permissions. Files get 775 and directories 664.
 *
 * @param string $src The directory to set permissions for
 */
function setPermissions($src)
{
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $full = $src . '/' . $file;
            if (is_dir($full)) {
                setPermissions($full);
                chmod($full, 0755);
            } else {
                if (is_link($full)) {
                    continue;
                }
                chmod($full, 0664);
            }
        }
    }
    closedir($dir);
}


/**
 *
 * Prepares and generates the release packages.
 *
 * @param string $releaseVersion Version of tiki that is being released.
 */
function build_packages($releaseVersion)
{
    global $options;

    $workDir = $_SERVER['HOME'] . "/tikipack";
    $fileName = 'tiki-' . $releaseVersion;
    $relDir = $workDir . '/' . $releaseVersion;    // where the tiki dir and tarballs go
    $sourceDir = $relDir . '/' . $fileName;        // the svn export

    echo "Seting up $workDir directory\n";
    if (! is_dir($workDir)) {
        if (! mkdir($workDir)) {
            error('Cant make ' . $workDir . "\n");
            die();
        }
    }

    // remove previous files if they exist.
    if (is_dir($relDir)) {
        echo "Removing previous files\n";
        $shellout = rrmdir($relDir);
        if ($shellout) {
            die($shellout . "\n");
        }
    }
    if (! mkdir($relDir)) {
        error('Cant make ' . $relDir . "\n");
        die();
    }

    // create an export in tikipack to work with
    echo "Exporting working copy into $sourceDir\n";
    $shellout = export(ROOT, $sourceDir);
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }


    if (! is_file($sourceDir . '/vendor_bundled/composer.json')) {
        echo 'composer.json not found. Aborting.' . "\n";
        die();
    }

    if (is_file($workDir . '/composer.phar')) {
        if (! unlink($workDir . '/composer.phar')) {
            echo "Can't delete tikipack/composer.phar. Aborting." . "\n";
            die();
        }
    }

    echo "Downloading composer.phar" . "\n";
    $checksum = file_get_contents('https://composer.github.io/installer.sig');
    $composerInstaller = $workDir . '/composer-setup.php';
    if (! file_put_contents($composerInstaller, file_get_contents('http://getcomposer.org/installer'))) {
        echo "Can't create tikipack/composer-setup.php. Aborting." . "\n";
        die();
    }

    if ($checksum !== hash_file('sha384', $composerInstaller)) {
        echo "Invalid composer installer checksum. Aborting." . "\n";
        unlink($composerInstaller);
        die();
    }

    $shellout = shell_exec('php ' . escapeshellarg($composerInstaller) . ' --quiet --install-dir=' . $workDir . ' 2>&1');

    if ($shellout) {
        echo "Composer installer failed. Aborting." . "\n";
        unlink($composerInstaller);
        die();
    }

    // tidy up
    unlink($composerInstaller);

    echo 'Installing dependencies through composer' . "\n";
    $shellout = shell_exec('php ' . escapeshellarg($workDir . '/composer.phar') . ' install -d ' . escapeshellarg($sourceDir . '/vendor_bundled') . ' --prefer-dist --no-dev 2>&1');
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    if (strpos($shellout, 'Fatal error:') !== false ||
        strpos($shellout, 'Installation failed,') !== false ||
        // symfony/dependency-injection comes in quite late in the list and is required - sometimes no error is reported even though it didn't work
        strpos($shellout, 'symfony/dependency-injection') === false
    ) {
        echo 'Vendor bundled packages installation Failed. Exiting' . "\n";
        die();
    }

    echo "Removing development files\n";
    $shellout = rrmdir($sourceDir . '/tests');
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/db/convertscripts');
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/doc/devtools');
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/bin');
    if ($shellout) {
        die($shellout . "\n");
    }

    removeFiles($sourceDir, ['.gitignore']);

    echo "Removing language file comments\n";
    foreach (scandir($sourceDir . '/lang') as $strip) {
        if (is_file($sourceDir . '/lang/' . $strip . '/language.php')) {
            $shellout = shell_exec('php ' . escapeshellarg(__DIR__ . '/stripcomments.php') . ' ' . escapeshellarg($sourceDir . '/lang/' . $strip . '/language.php') . ' 2>&1');
        }
        if ($shellout) {
            die($shellout . "\n");
        }
    }

    echo "Setting file permissions\n";
    setPermissions($sourceDir);

    $relDir = escapeshellarg($relDir);

    echo "Creating $fileName.tar.gz\n";
    $shellout = shell_exec("cd $relDir; tar -pczf " . escapeshellarg($fileName . ".tar.gz") . ' ' . escapeshellarg($fileName) . " --exclude '*.DS_Store' 2>&1");
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    echo "Creating $fileName.tar.bz2\n";
    $shellout = shell_exec("cd $relDir; tar -pcjf " . escapeshellarg($fileName . ".tar.bz2") . ' ' . escapeshellarg($fileName) . " --exclude '*.DS_Store' 2>&1");
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    echo "Creating $fileName.tar.xz\n";
    $shellout = shell_exec("cd $relDir; tar -pcJf " . escapeshellarg($fileName . ".tar.xz") . ' ' . escapeshellarg($fileName) . " --exclude '*.DS_Store' 2>&1");
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    echo "Creating $fileName.zip\n";
    $shellout = shell_exec("cd $relDir; zip -ry " . escapeshellarg($fileName . ".zip") . ' ' . escapeshellarg($fileName) . ' -x "*.DS_Store" -9 2>&1');
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    echo "Creating $fileName.7z\n";
    $shellout = shell_exec("cd $relDir; 7za a " . escapeshellarg($fileName . ".7z") . ' ' . escapeshellarg($fileName) . ' -xr!*.DS_Store -mx=9 2>&1');
    if (strpos($shellout, 'command not found')) {
        error("7za not installed. Archive creation failed.\n");
    }
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    echo color("\nTo upload the 'tarballs', copy-paste and execute the following line (and change '\$SF_LOGIN' by your SF.net login):\n", 'yellow');
    echo color("    cd $relDir; scp $fileName.* \$SF_LOGIN@frs.sourceforge.net:/home/pfs/project/t/ti/tikiwiki/\$RELEASEFOLDER\$\n", 'yellow');

    info(">> Packages files have been built in ~/tikipack/$releaseVersion\n");
}

/**
 * @param $dir
 * @param $entries
 * @param $regexp_pattern
 * @return bool
 */
function get_files_list($dir, &$entries, $regexp_pattern)
{
    $d = dir($dir);
    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_dir($entry)) {
            // do not descend and no CVS/Subversion files
            if ($e != '..' && $e != '.' && $e != 'CVS' && $e != '.git' && $e != '.gitignore' && $e != '.svn' && $entry != './temp/templates_c' && $entry != './vendor_bundled/vendor') {
                if (! get_files_list($entry, $entries, $regexp_pattern)) {
                    return false;
                }
            }
        } elseif (preg_match($regexp_pattern, $e) && realpath($entry) != __FILE__) {
            $entries[] = $entry;
        }
    }
    $d->close();

    return true;
}

/**
 * @param $alreadyDone
 * @param $toDo
 * @param $message
 */
function display_progress_percentage($alreadyDone, $toDo, $message)
{
    $onePercent = ceil($toDo / 100);
    if ($alreadyDone % $onePercent === 0 || $alreadyDone == $toDo) {
        $percentage = ($alreadyDone >= $toDo - $onePercent) ? 100 : min(100, $alreadyDone / $onePercent);
        printf("\r$message", $percentage);
    }
}

function zone_is_empty()
{
    // dummy function to keep smarty happy
}

/**
 * @param $error_msg
 */
function check_smarty_syntax(&$error_msg)
{
    global $tikidomain, $prefs;
    $tikidomain = '';
    // Initialize $prefs with some variables needed by the tra() function and smarty autosave plugin
    $prefs = [
        'lang_use_db' => 'n',
        'language' => 'en',
        'site_language' => 'en',
        'feature_ajax' => 'n'
    ];

    // Load Tiki Smarty
    $prefs['smarty_compilation'] = 'always';
    $prefs['smarty_security'] = 'y';
    $prefs['maxRecords'] = 25;
    $prefs['log_tpl'] = 'y';
    $prefs['feature_sefurl_filter'] = 'y';
    $prefs['site_layout'] = 'basic';
    require_once 'vendor_bundled/vendor/smarty/smarty/libs/Smarty.class.php';
    require_once 'lib/init/smarty.php';
    require_once 'lib/init/initlib.php';
    // needed in Smarty_Tiki
    define('TIKI_PATH', getcwd());
    require_once 'lib/smarty_tiki/prefilter.tr.php';
    require_once 'lib/smarty_tiki/prefilter.jq.php';
    require_once 'lib/smarty_tiki/prefilter.log_tpl.php';
    $smarty = new Smarty_Tiki();
    set_error_handler('check_smarty_syntax_error_handler');

    $templates_dir = TIKI_PATH . '/templates';

    $errors_found = false;
    $entries = [];
    get_files_list($templates_dir, $entries, '/\.tpl$/');

    $nbEntries = count($entries);
    for ($i = 0; $i < $nbEntries; $i++) {
        display_progress_percentage($i, $nbEntries, '%d%% of files passed the Smarty syntax check');

        if (strpos($entries[$i], 'tiki-mods.tpl') === false) {
            $template_file = substr($entries[$i], strlen($templates_dir) + 1);

            try {
                $_tpl = $smarty->createTemplate($template_file, null, null, null, false);
                $_tpl->compileTemplateSource();
            } catch (Exception $e) {
                echo color("\nError: " . $e->getMessage(), 'red') . "\n";
                $errors_found = true;
            }
        }
    }
    restore_error_handler();

    echo "\n";

    if ($errors_found) {
        die('Fix the Smarty errors and try again please.');
    }
}


/**
 * @param $errno
 * @param $errstr
 * @param string $errfile
 * @param int $errline
 * @param array $errcontext
 */
function check_smarty_syntax_error_handler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
{
    if (strpos($errstr, 'filemtime(): stat failed for') === false) {    // smarty seems to emit these for every file
        echo "\n" . color($errstr, 'red') . "\n";
    }

    return true;
}

/**
 * @param $dir
 * @param $error_msg
 * @param $hide_php_warnings
 * @return bool
 */
function check_php_syntax(&$dir, &$error_msg, $hide_php_warnings)
{
    global $phpCommand;
    $checkPhpCommand = $phpCommand . (ERROR_REPORTING_LEVEL > 0 ? ' -d error_reporting=' . (int)ERROR_REPORTING_LEVEL : '');

    $entries = [];
    get_files_list($dir, $entries, '/\.php$/');

    $nbEntries = count($entries);
    for ($i = 0; $i < $nbEntries; $i++) {
        display_progress_percentage($i, $nbEntries, '%d%% of files passed the PHP syntax check');
        $return_var = 0;
        $output = null;
        exec("$checkPhpCommand -l {$entries[$i]} 2>&1", $output, $return_var);
        $fullOutput = implode("\n", $output);

        if (strpos($fullOutput, 'Segmentation fault') !== false) {
            // If php -l command segfaults, wait and retry (it seems to happen quite often on some environments for this command)
            echo "\r[Retrying due to a Segfault...]";
            sleep(1);
            $i--;
        } elseif ($return_var !== 0) {
            // Handle PHP errors
            $fullOutput = trim($fullOutput);
            $error_msg = ($fullOutput == '') ? "\nPHP Parsing error in '{$entries[$i]}' ($return_var)\n" : "\n$fullOutput";

            return false;
        } elseif (! $hide_php_warnings && ($nb_lines = count($output)) > 1 && ! preg_match(THIRD_PARTY_LIBS_PATTERN, $entries[$i])) {
            // Handle PHP warnings / notices (this just displays a yellow warning, it doesn't return false or an error_msg)
            // and exclude some third party libs when displaying warnings from the PHP syntax check, because we can't fix it directly by the way.
            echo "\r";
            foreach ($output as $k => $line) {
                // Remove empty lines and last line (because in case of a simple warning, the last line simply says 'No syntax errors...')
                if (trim($line) == '' || $k == $nb_lines - 1) {
                    continue;
                }
                echo color("$line\n", 'yellow');
            }
            display_progress_percentage($i, $nbEntries, '%d%% of files passed the PHP syntax check');
        }
        unset($output, $return_var);
    }

    echo "\n";

    return true;
}

/**
 * Check the CI Pipeline for the result of the specific checks regarding the DB structure
 *
 * @param string $mainversion
 * @param string $error_msg
 * @return bool
 */
function check_database_files_and_upgrade($mainversion, &$error_msg)
{
    $gitlabUrl = 'https://gitlab.com';
    $gitlabRepo = $gitlabUrl . '/tikiwiki/tiki';

    $branchToCheck = $mainversion . '.x';

    $pipeline = gitlabGetLastFinishedPipelineByBranch($gitlabRepo, $branchToCheck);

    if (empty($pipeline)) {
        echo color('Could not retrieve pipeline information for branch ' . $branchToCheck . "\n", 'red');
        echo color(
            'You can check manually using ' . $gitlabRepo . '/pipelines/?scope=branches&format=json' . "\n",
            'yellow'
        );
        $error_msg .= 'Information about the CI pipeline could not be retrieved' . "\n";

        return false;
    }

    echo color(
        'Checking jobs for branch ' . $branchToCheck . ', pipeline: ' . $gitlabUrl . $pipeline['url'] . "\n",
        'yellow'
    );

    $jobs = gitlabGetJobStatusByPipeline($gitlabRepo, $pipeline['id']);

    if (empty($pipeline)) {
        echo color('Could not retrieve jobs information for pipeline ' . $pipeline['id'] . "\n", 'red');
        echo color('You can check manually using ' . $gitlabUrl . $pipeline['url'] . "\n", 'yellow');
        $error_msg .= 'Information about jobs in the CI pipeline could not be retrieved' . "\n";

        return false;
    }

    $checkList = [
        'schema-naming-convention' => '1.1.2.1. Check _tiki.sql suffixes',
        'db-upgrade-' => '1.1.2.2. Structure',
        'schema-sql-drop' => '1.1.2.3. Drop Table',
        'sql-engine' => '1.1.2.4. MyISAM',
        'sql-engine-conversion' => '1.1.2.5. InnoDB',
    ];

    $allOk = true;

    foreach ($checkList as $checkPrefix => $checkName) {
        foreach ($jobs['tiki-check'] as $jobName => $job) {
            if (strpos($jobName, $checkPrefix) === 0) {
                echo color(
                    $checkName . ': ' . $job['status'] . ', job: ' . $jobName . ', url: ' . $gitlabUrl . $job['url'] . "\n",
                    $job['status'] == PIPELINE_STATUS_PASSED ? 'green' : 'red'
                );
                if ($job['status'] != PIPELINE_STATUS_PASSED) {
                    $error_msg .= 'Issues with job ' . $jobName . ' in the CI Pipeline' . "\n";
                    $allOk = false;
                }
            }
        }
    }

    return $allOk;
}

/**
 * Lookup the ID of the last finished pipeline run for
 * a given branch with status 'passed' or 'failed'.
 *
 * @param string $repoUrl Url of the repo in gitlab
 * @param string $branch Branch to use for filtering
 * @param integer $page Page associated with the cycle (recursive lookup)
 * @return array|bool The result or false if error
 */
function gitlabGetLastFinishedPipelineByBranch($repoUrl, $branch, $page = 1)
{
    $lastPipelineByBranch = $repoUrl . '/pipelines/?scope=finished&format=json&per_page=' . PIPELINES_FETCH_AMOUNT . '&page=' . $page;

    if (getenv('TEST_GITLAB_PIPELINE')) {
        $lastPipelineByBranch = getenv('TEST_GITLAB_PIPELINE'); // to allow fake the answer while testing
    }

    $content = file_get_contents($lastPipelineByBranch);
    $jsonContent = json_decode($content, true);
    if (empty($jsonContent)) {
        return false;
    }

    $pipeline = array_filter(
        $jsonContent['pipelines'],
        function ($pipeline) use ($branch) {
            return $pipeline['ref']['name'] === $branch &&
                ($pipeline['details']['status']['text'] === PIPELINE_STATUS_PASSED ||
                    $pipeline['details']['status']['text'] === PIPELINE_STATUS_FAILED);
        }
    );

    if (empty($pipeline)) {
        if ($page >= 50) {
            return false;
        }

        return gitlabGetLastFinishedPipelineByBranch($repoUrl, $branch, ++$page);
    }

    $pipeline = reset($pipeline);

    return ['id' => $pipeline['id'], 'url' => $pipeline['path']];
}

/**
 * Returns the list of stages and the jobs for each of the stages
 *
 * @param string $repoUrl Url of the repo in gitlab
 * @param string $pipelineId Pipeline ID from where to retrieve the list of jobs
 * @return array|bool The result or false if error
 */
function gitlabGetJobStatusByPipeline($repoUrl, $pipelineId)
{
    $pipelineJobsUrl = $repoUrl . '/pipelines/' . $pipelineId . '?format=json';

    if (getenv('TEST_GITLAB_JOBS')) {
        $pipelineJobsUrl = getenv('TEST_GITLAB_JOBS'); // to allow fake the answer while testing
    }

    $content = file_get_contents($pipelineJobsUrl);
    $jsonContent = json_decode($content, true);
    if (empty($jsonContent)) {
        return false;
    }

    $stages = [];
    foreach ($jsonContent['details']['stages'] as $stage) {
        $jobs = [];
        foreach ($stage['groups'] as $group) {
            foreach ($group['jobs'] as $job) {
                $jobs[$job['name']] = ['status' => $job['status']['text'], 'url' => $job['status']['details_path']];
            }
        }
        $stages[$stage['name']] = $jobs;
    }

    return $stages;
}

/**
 * @return array|bool
 */
function get_options()
{
    if ($_SERVER['argc'] <= 1) {
        return false;
    }

    $argv = [];
    $options = [
        'howto' => false,
        'help' => false,
        'http-proxy' => false,
        'mirror-uri' => false,
        'no-commit' => false,
        'no-check-vcs' => false,
        'no-check-db' => false,
        'no-check-php' => false,
        'no-check-php-warnings' => false,
        'no-check-smarty' => false,
        'no-first-update' => false,
        'no-readme-update' => false,
        'no-lang-update' => false,
        'no-changelog-update' => false,
        'no-copyright-update' => false,
        'no-secdb' => false,
        'no-packaging' => false,
        'no-tagging' => false,
        'force-yes' => false,
        'debug-packaging' => false,
        'only-secdb' => false,
        'devmode' => false,
        'use-git' => false,
    ];

    // Environment variables provide default values for parameter options. e.g. export TIKI_NO_SECDB=true
    $prefix = "TIKI-";
    foreach ($options as $option => $optValue) {
        $envOption = $prefix . $option;
        $envOption = str_replace("-", "_", $envOption);
        if (isset($_ENV[$envOption])) {
            $envValue = $_ENV[$envOption];
            $options[$option] = $envValue;
        }
    }

    foreach ($_SERVER['argv'] as $arg) {
        if (substr($arg, 0, 2) == '--') {
            if (($opt = substr($arg, 2)) != '' && isset($options[$opt])) {
                $options[$opt] = true;
            } elseif (substr($arg, 2, 11) == 'http-proxy=') {
                if (($proxy = substr($arg, 13)) != '') {
                    $options[substr($arg, 2, 10)] = stream_context_create(
                        [
                            'http' => [
                                'proxy' => 'tcp://' . $proxy,
                                'request_fulluri' => true
                            ]
                        ]
                    );
                } else {
                    $options[substr($arg, 2, 10)] = true;
                }
            } elseif (substr($arg, 2, 15) == 'mirror-uri=') {
                if (($uri = substr($arg, 17)) != '') {
                    $options[substr($arg, 2, 14)] = $uri;
                }
            } else {
                error("Unknown option $arg. Try using --help option.\n");
            }
        } else {
            $argv[] = $arg;
        }
    }
    $_SERVER['argv'] = $argv;
    unset($argv);

    if ($options['http-proxy'] === true) {
        error("The --http-proxy option need a value. Use it this way: --http-proxy=HOST_DOMAIN:PORT_NUMBER");
    }

    if ($_SERVER['argc'] == 2) {
        $_SERVER['argv'][] = '';
    }

    return $options;
}

/**
 * @param $msg
 * @param bool $increment_step
 * @param bool $commit_msg
 * @return bool
 */
function important_step($msg, $increment_step = true, $commit_msg = false)
{
    global $options;
    static $step = 0;

    // Auto-Skip the step if this is a commit step and if there is nothing to commit
    if ($commit_msg && ! has_uncommited_changes('.')) {
        return false;
    }

    // Increment step number if needed
    if ($increment_step) {
        $step++;
    }

    if ($commit_msg && $options['no-commit']) {
        print "Skipping actual commit ('$commit_msg') because no-commit = true\n";

        return false;
    }

    if ($options['force-yes']) {
        important("\n$step) $msg...");
        $do_step = true;
    } else {
        important("\n$step) $msg?");

        $prompt = '[Y/n/q/?] ';
        if (function_exists('readline')) {
            // readline function requires php readline extension...
            $c = readline($prompt);
        } else {
            echo $prompt;
            $c = rtrim(fgets(STDIN), "\n");
        }

        switch (strtolower($c)) {
            case 'y':
            case '':
                $do_step = true;

                break;
            case 'n':
                info(">> Skipping step $step.");
                $do_step = false;

                break;
            case 'q':
                die;

                break;
            default:
                if ($c != '?') {
                    info(color(">> Unknown answer '$c'.", 'red'));
                }
                info(">> You have to type 'y' (Yes), 'n' (No) or 'q' (Quit) and press Enter.");

                return important_step($msg, false);
        }
    }

    if ($commit_msg && $do_step && ($revision = commit($commit_msg))) {
        info(">> Commited revision $revision.");
    }

    return $do_step;
}

/**
 * @param $newVersion
 * @return array|bool
 */
function update_changelog_file($newVersion)
{
    $handle = false;
    if (! is_readable(CHANGELOG) || ! is_writable(CHANGELOG) || ! ($handle = @fopen(CHANGELOG, "r"))) {
        error('The changelog file "' . CHANGELOG . '" is not readable or writable.');
    }

    $majorVersion = substr($newVersion, 0, strpos($newVersion, '.'));
    $parseLogs = $sameFinalVersion = $skipBuffer = false;
    $lastReleaseMajorNumber = -1;
    $lastReleaseNumber = '';
    $minRevision = $currentParsedRevision = 0;
    $lastReleaseLogs = [];
    $versionMatches = [];
    $newChangelog = '';
    $newChangelogEnd = '';

    if ($handle) {
        while (! feof($handle)) {
            $buffer = fgets($handle);
            if (empty($buffer)) {
                continue;
            }

            if (preg_match('/^Version (\d+)\.(\d+)/', $buffer, $versionMatches)) {
                $versionString = $versionMatches[1] . '.' . $versionMatches[2];
                if ((float)$lastReleaseNumber < (float)$versionString) {
                    $lastReleaseNumber = $versionString;
                    if ($lastReleaseNumber === $newVersion) {
                        // The changelog file already contains log for the same final version
                        $sameFinalVersion = true;
                        $skipBuffer = true;
                    }
                    $parseLogs = true;
                    $lastReleaseMajorNumber = $versionMatches[1];
                }
            }
            if ($parseLogs) {
                $matches = [];
                if (preg_match('/^(\d+ | ) \|/', $buffer, $matches)) {
                    $skipBuffer = false;
                    if ($minRevision == 0) {
                        $minRevision = (int)$matches[1];
                    }
                    $currentParsedRevision = (int)$matches[1];
                } elseif (! $skipBuffer && $currentParsedRevision > 0 && $buffer[0] != '-') {
                    if (isset($lastReleaseLogs[$currentParsedRevision])) {
                        $lastReleaseLogs[$currentParsedRevision] .= $buffer;
                    } else {
                        $lastReleaseLogs[$currentParsedRevision] = $buffer;
                    }
                }
            }
            if ($lastReleaseMajorNumber != -1 && $lastReleaseMajorNumber < $majorVersion) {
                $newChangelogEnd .= generate_changelog_version_header($lastReleaseNumber);
                $newChangelogEnd .= "Changelog for Tiki version " . $lastReleaseNumber . ", or older, available at:\n";
                $newChangelogEnd .= "https://sourceforge.net/p/tikiwiki/code/HEAD/tree/tags/" . $lastReleaseNumber . "/changelog.txt\n\n";

                break; // truncate the rest of the file
            }
            if (! $skipBuffer) {
                if ($lastReleaseMajorNumber == -1) {
                    $newChangelog .= $buffer;
                } else {
                    $newChangelogEnd .= $buffer;
                }
            }
        }
        fclose($handle);
    }

    $newChangelog .= generate_changelog_version_header($newVersion);

    $return = ['nbCommits' => 0, 'sameFinalVersion' => $sameFinalVersion];
    $matches = [];

    if ($minRevision === 0) { // failed to get the last rev from the old file contents
        $minRevision = get_tag_revision($lastReleaseNumber);
    }
    if ($minRevision != 0) {
        if (preg_match_all('/^([A-Za-z0-9]+).\|.*\n\n(.*)\-{46}/Ums', get_logs('.', $minRevision), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $logEntry) {
                // Do not keep merges and release-related logs
                $commitFlag = substr(trim($logEntry[2]), 0, 5);
                if ($commitFlag == '[MRG]' || $commitFlag == '[REL]') {
                    continue;
                }

                // Add log entries only if they were not already listed (same revision number or same log message) in the previous version
                if (! isset($lastReleaseLogs[$logEntry[1]]) && ! in_array("\n" . $logEntry[2], $lastReleaseLogs)) {
                    $newChangelog .= str_replace("\n\n", "\n", $logEntry[0]) . "\n";

                    $lastReleaseLogs[] = "\n" . $logEntry[2];
                    if ($return['nbCommits'] == 0) {
                        $return['firstRevision'] = $logEntry[1];
                    }
                    $return['lastRevision'] = $logEntry[1];
                    $return['nbCommits']++;
                }
            }
        }
    }

    return file_put_contents(CHANGELOG, $newChangelog . $newChangelogEnd) ? $return : false;
}

/**
 * Generate the header for a given version, used in the changelog
 * @param string $version
 * @return string
 */
function generate_changelog_version_header($version)
{
    $majorVersion = substr($version, 0, strpos($version, '.'));
    $releaseNotesURL = '<http://doc.tiki.org/Tiki' . $majorVersion . '>';

    $versionHeader = <<<EOS
Version $version
$releaseNotesURL
------------------

----------------------------------------------

EOS;

    return $versionHeader;
}

/**
 * @param $newVersion
 * @return array|bool
 */
function update_copyright_file($newVersion)
{
    if (! is_readable(COPYRIGHTS) || ! is_writable(COPYRIGHTS)) {
        error('The copyright file "' . COPYRIGHTS . '" is not readable or writable.');
    }
    global $nbCommiters, $options;
    $nbCommiters = 0;
    $contributors = [];

    $repositoryUri = empty($options['mirror-uri']) ? TIKIVCS : $options['mirror-uri']; //
    if (strpos($repositoryUri, '/') === 0) {
        $repositoryUri = 'file://' . $repositoryUri;
    }
    $repositoryInfo = get_revision($repositoryUri);

    $oldContributors = parse_copyrights();
    get_contributors_data($repositoryUri, $contributors, 1, $repositoryInfo);
    ksort($contributors);

    $totalContributors = count($contributors);
    $now = gmdate('Y-m-d');

    $copyrights = <<<EOS
Tiki Copyright
----------------

The following list attempts to gather the copyright holders for Tiki
as of version $newVersion.

Accounts listed below with commits have contributed source code to CVS or SVN.
Please note that even more people contributed on various other aspects (documentation,
bug reporting, testing, etc.)

This is how we implement the Tiki Social Contract.
http://tiki.org/Social+Contract

List of members of the Community
As of $now, the community has:
  * $totalContributors members on SourceForge.net,
  * $nbCommiters of those people who made at least one code commit

This list is automatically generated and alphabetically sorted
from subversion repository by the following script:
  doc/devtools/release.php

Counting the commits is not as trivial as it may sound. If your number of commits
seems incorrect, it could be that the script is not detecting them all. This
has been reported especially for commits early on in the project. Nonetheless,
the list provides a general idea.

====================================================================

EOS;

    $return = ['newCommits' => 0, 'newContributors' => 0];
    foreach ($contributors as $author => $infos) {
        if (isset($oldContributors[$author])) {
            if ($oldContributors[$author] != $infos) {
                // Quickfix to keep old dates which may be different due to which time zone is used
                if (isset($oldContributors[$author]['First Commit'])) {
                    $infos['First Commit'] = $oldContributors[$author]['First Commit'];
                    if (isset($oldContributors[$author]['Number of Commits']) && isset($oldContributors[$author]['Number of Commits'])
                        && isset($infos['Number of Commits']) && $oldContributors[$author]['Number of Commits'] == $infos['Number of Commits']) {
                        $infos['Last Commit'] = $oldContributors[$author]['Last Commit'];
                    }
                }
                if (isset($infos['Number of Commits'])) {
                    if (isset($oldContributors[$author]['Number of Commits'])) {
                        $return['newCommits'] += ($infos['Number of Commits'] - $oldContributors[$author]['Number of Commits']);
                    }
                }
            }
        } else {
            $return['newContributors']++;
        }
        $copyrights .= "\nNickname: $author";
        $orderedKeys = ['Name', 'First Commit', 'Last Commit', 'Number of Commits', 'SF Role'];
        foreach ($orderedKeys as $k) {
            if (empty($infos[$k]) || ($k == 'Name' && $infos[$k] == $author)) {
                continue;
            }
            $copyrights .= "\n$k: " . $infos[$k];
        }
        $copyrights .= "\n";
    }

    return file_put_contents(COPYRIGHTS, $copyrights) ? $return : false;
}

/**
 * @return array|bool
 */
function parse_copyrights()
{
    if (! $copyrights = @file(COPYRIGHTS)) {
        return false;
    }

    $return = [];
    $curNickname = '';

    foreach ($copyrights as $line) {
        if (empty($line)) {
            continue;
        }
        if (substr($line, 0, 10) == 'Nickname: ') {
            $curNickname = rtrim(substr($line, 10));
            $return[$curNickname] = [];
        } elseif ($curNickname != '' && ($pos = strpos($line, ':')) !== false) {
            $return[$curNickname][substr($line, 0, $pos)] = rtrim(substr($line, $pos + 2));
        }
    }

    return $return;
}

/**
 * @param $path
 * @param $contributors
 * @param $minRevision
 * @param $maxRevision
 * @param int $step
 * @return mixed
 */
function get_contributors_data($path, &$contributors, $minRevision, $maxRevision, $step = 20000)
{
    global $nbCommiters;
    if (empty($contributors)) {
        get_contributors_sf_data($contributors);
        info(">> Retrieved members list from Sourceforge.");
    }

    get_contributors($path, $contributors, $minRevision, $maxRevision, $step);
    $nbCommiters = array_filter($contributors, function ($contributor) {
        // Get count contributors with commits
        return count($contributor) > 2;
    });

    return $contributors;
}

/**
 * @param $contributors
 */
function get_contributors_sf_data(&$contributors)
{
    global $options;
    $matches = [];

    if (! function_exists('iconv')) {
        error("PHP 'iconv' function is not available on this system. Impossible to get SF.net data.");
    }

    $html = $options['http-proxy'] ? file_get_contents(SF_TW_MEMBERS_URL, 0, $options['http-proxy']) : file_get_contents(SF_TW_MEMBERS_URL);

    if (! empty($html) && preg_match('/(<table.*<\/\s*table>)/sim', $html, $matches)) {
        $usersInfo = [];
        if (preg_match_all('/<tr[^>]*>' . str_repeat('\s*<td[^>]*>(.*)<\/td>\s*', 3) . '<\/\s*tr>/Usim', $matches[0], $usersInfo, PREG_SET_ORDER)) {
            foreach ($usersInfo as $k => $userInfo) {
                $userInfo = array_map('trim', array_map('strip_tags', $userInfo));
                $user = strtolower($userInfo['2']);
                if (empty($user)) {
                    continue;
                }
                $contributors[$user] = [
                    'Name' => html_entity_decode(iconv("ISO-8859-15", "UTF-8", $userInfo['1']), ENT_COMPAT, 'UTF-8'),
                    'SF Role' => $userInfo['3']
                ];
            }
        }
    } else {
        error('Impossible to get SF.net users information. If you need to use a web proxy, try the --http-proxy option.');
        die;
    }
}

/**
 * @param $releaseVersion
 * @param $mainVersion
 * @return bool
 */
function update_readme_file($releaseVersion, $mainVersion)
{
    if (! is_readable(README) || ! is_writable(README)) {
        error('The README file "' . README . '" is not readable or writable.');
        die;
    }

    $year = gmdate('Y');
    $copyrights_file = COPYRIGHTS_FILENAME;
    $license_file = LICENSE_FILENAME;

    $majorVersion = substr($mainVersion, 0, strpos($mainVersion, '.'));
    $release_notes_url = 'http://doc.tiki.org/Tiki' . $majorVersion;
    // Changed from Tiki 12 to point to http://doc.tiki.org/Tiki12 instead of http://tiki.org/ReleaseNotes30

    $readme = <<<EOF
Tiki! The wiki with a lot of features!
Version $releaseVersion


DOCUMENTATION

* The documentation for $mainVersion version is ever evolving at http://doc.tiki.org.
  You're encouraged to contribute.

* It is highly recommended that you refer to the online documentation:
* http://doc.tiki.org/Installation for a setup guide

* Notes about this release are accessible from $release_notes_url
* Tiki has an active IRC channel, #tikiwiki on irc.freenode.net

INSTALLATION

* There is a file INSTALL in this directory with notes on how to setup and
  configure Tiki. Again, see http://doc.tiki.org/Installation for the latest install help.

UPGRADES

* Read the online instructions if you want to upgrade your Tiki from a previous release http://doc.tiki.org/Upgrade

COPYRIGHT

Copyright (c) 2002-$year, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
Tiki was started under the name tikiwiki by Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
All Rights Reserved. See $copyrights_file for details and a complete list of authors.
Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See $license_file for details.

... Have fun!

Note to Tiki developers: update this text through release.php.
EOF;

    return (bool)file_put_contents(README, $readme);
}

function display_usage()
{
    echo "Usage: php doc/devtools/release.php [ Options ] <version-number> [ <subrelease> ]
Examples:
	php doc/devtools/release.php 2.0 preRC3
	php doc/devtools/release.php 2.0 RC3
	php doc/devtools/release.php 2.0

Options:
	--howto			    : display the Tiki release HOWTO
	--help			    : display this help
	--http-proxy=HOST:PORT	: use a http proxy to get copyright data on sourceforge
	--mirror-uri=URI	: use another repository URI to update the copyrights file (to avoid retrieving data from sourceforge, which is usually slow)
	--no-commit		        : do not commit any changes back to SVN or GIT
	--no-check-vcs		    : do not check if there are uncommitted changes on the checkout used for the release
	--no-check-db		    : do not check database scripts and database upgrades
	--no-check-php		    : do not check syntax of all PHP files
	--no-check-php-warnings	: do not display PHP warnings and notices during the PHP syntax check
	--no-check-smarty	    : do not check syntax of all Smarty templates
	--no-first-update	    : do not vcs update the checkout used for the release as the first step
	--no-readme-update	    : do not update the '" . README_FILENAME . "' file
	--no-lang-update	    : do not update lang/*/language.php files
	--no-changelog-update	: do not update the '" . CHANGELOG_FILENAME . "' file
	--no-copyright-update	: do not update the '" . COPYRIGHTS_FILENAME . "' file
	--no-secdb		        : do not update SecDB footprints
	--only-secdb		    : only generate a secdb database
	--no-packaging		    : do not build packages files
	--no-tagging		    : do not tag the release on the remote vcs repository
	--force-yes		        : disable the interactive mode (same as replying 'y' to all steps)
	--debug-packaging	    : display debug output while in packaging step
	--devmode               : equivalent to no-commit + no-check-vcs + no-first-update
	--use-git               : use git instead oof snv
Notes:
	Subreleases begining with 'pre' will not be tagged.
";
    die;
}

function display_howto()
{
    echo <<<EOS
--------------------------
   HOWTO release Tiki
--------------------------

Please see: https://dev.tiki.org/How+to+release

EOS;
    shell_exec('open ' . escapeshellarg('https://dev.tiki.org/How+to+release'));
    exit;
}
