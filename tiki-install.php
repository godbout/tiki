<?php
/**
 * Tiki's Installation script.
 *
 * Used to install a fresh Tiki instance, to upgrade an existing Tiki to a newer version and to test sendmail.
 *
 * @package TikiWiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */
// $Id$

$in_installer = 1;
define('TIKI_IN_INSTALLER', 1);
if (! isset($title)) {
	$title = 'Tiki Installer';
}
if (! isset($content)) {
	$content = 'No content specified. Something went wrong.<br/>Please tell your administrator.<br/>If you are the administrator, you may want to check for / file a bug report.';
}
if (! isset($dberror)) {
	$dberror = false;
}

// Show all errors
error_reporting(-1);
ini_set('display_errors', 1);

// Check that PHP version is sufficient

if (version_compare(PHP_VERSION, '7.2.0', '<')) {
	$title = 'PHP 7.2 is required';
	$content = '<p>Please contact your system administrator ( if you are not the one ;) ). Your version: ' . PHP_VERSION . ' <br /> <br /> ' . '</p>';
	createPage($title, $content);
}

require_once('lib/init/initlib.php');
$tikipath = __DIR__ . '/';
TikiInit::appendIncludePath($tikipath);

require_once('db/tiki-db.php');	// to set up multitiki etc if there

$lockFile = 'db/' . $tikidomainslash . 'lock';

// if tiki installer is locked (probably after previous installation) display notice
if (file_exists($lockFile)) {
	$title = 'Tiki Installer Disabled';
	$td = empty($tikidomain) ? '' : '/' . $tikidomain;
	$content = '
							<p class="under-text">As a security precaution, the Tiki Installer has been disabled. To re-enable the installer:</p>
								<ol class="installer-ordered-list-style">
									<li class="installer-ordered-list"><p>Use your file manager application to find the directory where you have unpacked your Tiki and remove the <span class="text-danger font-weight-bold">lock</span> file which was created in the <span class="text-danger font-weight-bold">db</span> folder.</p></li>
									<li class="installer-ordered-list"><p>Re-run <strong ><a class="text-yellow-inst" href="tiki-install.php'  . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '" title="Tiki Installer">tiki-install.php' . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '</a></strong>.</p></li>
								</ol>
							';
	createPage($title, $content);
}

if (!empty($db) && ! $db->getOne("SELECT COUNT(*) FROM `information_schema`.`character_sets` WHERE `character_set_name` = 'utf8mb4';")) {
	die(tr('Your database does not support the utf8mb4 character set required in Tiki19 and above. You need to upgrade your mysql or mariadb installation.'));
}

$tikiroot = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$session_params = session_get_cookie_params();
session_set_cookie_params($session_params['lifetime'], $tikiroot);
unset($session_params);
session_start();

$rootcheck = empty($tikiroot) || $tikiroot === '/' ? '' : $tikiroot;
$refered = isset($_SERVER['HTTP_REFERER']) ? strpos($_SERVER['HTTP_REFERER'], $rootcheck . '/tiki-install.php') : false;
if (! $refered || ($refered && ! isset($_POST['install_step']))) {
	unset($_SESSION['accessible']);
}
// Were database details defined before? If so, load them
if (file_exists('db/' . $tikidomainslash . 'local.php')) {
	include 'db/' . $tikidomainslash . 'local.php';

	// In case of replication, ignore it during installer.
	unset($shadow_dbs, $shadow_user, $shadow_pass, $shadow_host);

	// check for provided login details and check against the old, saved details that they're correct
	if (isset($_POST['dbuser'], $_POST['dbpass'])) {
		if (($_POST['dbuser'] == $user_tiki) && ($_POST['dbpass'] == $pass_tiki)) {
			$_SESSION['accessible'] = true;
			unset($_POST['dbuser']);
			unset($_POST['dbpass']);
		} else {
			$_SESSION['installer_auth_failure'] = isset($_SESSION['installer_auth_failure']) ? $_SESSION['installer_auth_failure'] + 1 : 1;

			// If there are too many failures during a single session, lock the installer as a precaution
			if ($_SESSION['installer_auth_failure'] >= 20) {
				touch($lockFile);
			}
		}
	}
} else {
	// No database info found, so it's a first-install and thus installer is accessible
	$_SESSION['accessible'] = true;
}

if (isset($_SESSION['accessible'])) {
	// allowed to access installer, include it
	$logged = true;
	$admin_acc = 'y';
	include_once 'installer/tiki-installer.php';
} else {
	// Installer knows db details but no login details were received for this script.
	// Thus, display a form.
	$title = 'Tiki Installer Security Precaution';
	$content = '
							<p class="text-info mt-lg-3 mx-3">You are attempting to run the Tiki Installer. For your protection, this installer can be used only by a site administrator.To verify that you are a site administrator, enter your <strong><em>database</em></strong> credentials (database username and password) here.</p>
						
							<p class="text-info mx-3">If you have forgotten your database credentials, find the directory where you have unpacked your Tiki and have a look inside the <strong class="text-yellow-inst">db</strong> folder into the <strong class="text-yellow-inst">local.php</strong> file.</p>
							<form method="post" action="tiki-install.php" class="text-center">
								<input type="hidden" name="enterinstall" value="1">
								<p><label for="dbuser" class="sr-only">Database username</label> <input type="text" id="dbuser" name="dbuser" class="col-6 offset-3 form-control text-center" placeholder="Database username"/></p>
								<p><label for="dbpass" class="sr-only">Database password</label> <input type="password" id="dbpass" name="dbpass" class="col-6 offset-3 form-control text-center" placeholder="Database password"/></p>
								<p><input type="submit" class="btn btn-primary" value=" Validate and Continue " /></p>
							</form>
							<p>&nbsp;</p>';
	createPage($title, $content);
}


/**
 * creates the HTML page to be displayed.
 *
 * Tiki may not have been installed when we reach here, so we can't use our templating system yet.
 *
 * @param string $title   page Title
 * @param mixed  $content page Content
 */
function createPage($title, $content)
{
	echo <<<END
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta name="robots" content="noindex, nofollow">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" rel="stylesheet" href="themes/base_files/css/tiki_base.css" />
		<link type="text/css" rel="stylesheet" href="themes/default/css/default.css" />
		<link type="text/css" rel="stylesheet" href="themes/css/tiki-install.css" />
		<title>$title</title>
	</head>
    <body class="installer-body">
         <header class="header-main">
            <img alt="Site Logo" src="img/tiki/Tiki_WCG_light.png" class="logo-box" />
            <div class="text-box">
                <div class="heading-text">
                    <h2 class="main-text">$title</h2>
                </div>
			<div class="row mb-2">
				<div class="col" id="col1">
					<div class="text-center">
                    $content
                </div>
            </div>
			</div>
			<div style="position:fixed;bottom:1.5em;right:1.5em;z-index:1;">
				<a href="http://tiki.org" target="_blank" title="Powered by Tiki Wiki CMS Groupware"><img src="img/tiki/tikibutton.png" alt="Powered by Tiki Wiki CMS Groupware" /></a>
			</div>
		</div>
	</body>
</html>
END;
	die;
}
