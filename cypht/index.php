<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * GIT VERSION: 10772
 *
 * Some of the following constants are automatically filled in when
 * the build process is run. If you change them in site/index.php
 * and rerun the build process your changes will be lost
 *
 * APP_PATH   absolute path to the php files of the app
 * DEBUG_MODE flag to enable easier debugging and development
 * CACHE_ID   unique string to bust js/css browser caching for a new build
 * SITE_ID    random site id used for page keys
 */

//require_once("../tiki-setup.php");

define('APP_PATH', '../vendor_bundled/vendor/jason-munro/cypht/');
define('DEBUG_MODE', false);
define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D');
define('SITE_ID', 'Tiki-Integration');
//define('JS_HASH', 'sha512-QIl5BiTUr8WUImxFg9R52It3HMDEjVTvrGsZQYuFsDJtSnzcKZL/SQSt9J+FXP0dzwxduiU5gRF2AX3hlsfZfA==');
//define('CSS_HASH', 'sha512-TJDJviwXjCmX7hA7LUQA4w1LUnYCVWmuMJQx+3DUrdtIC9fFzSbEvI78c/6sjO4JcEODnt6YHIrikjZaC07FTA==');

/* show all warnings in debug mode */
if (DEBUG_MODE) {
    error_reporting(E_ALL | E_STRICT);
}

/* config file location */
define('CONFIG_FILE', APP_PATH.'hm3.rc');

/* don't let anything output content until we are ready */
ob_start();

/* set default TZ */
date_default_timezone_set( 'UTC' );

/* get includes */
require APP_PATH.'lib/framework.php';

/* get configuration */
$config = new Hm_Site_Config_File(CONFIG_FILE);

/* setup ini settings */
if (!$config->get('disable_ini_settings')) {
    require APP_PATH.'lib/ini_set.php';
}

/* process the request */
new Hm_Dispatch($config);

/* log some debug stats about the page */
if (DEBUG_MODE) {
    Hm_Debug::load_page_stats();
    Hm_Debug::show();
}
