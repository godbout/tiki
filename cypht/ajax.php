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

require_once("../tiki-setup.php");

define('VENDOR_PATH', $tikipath.'/vendor_bundled/vendor/');
define('APP_PATH', VENDOR_PATH.'jason-munro/cypht/');
define('DEBUG_MODE', false);

define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D'); // Cypht uses for asset cache busting but we run the assets through Tiki pipeline, so no need to generate a unique key here
define('SITE_ID', 'Tiki-Integration');

/* get includes */
require APP_PATH.'lib/framework.php';
require_once $tikipath.'/cypht/integration/classes.php';

/* get configuration */
$config = new Tiki_Hm_Site_Config_File(APP_PATH.'hm3.rc');

/* process the request */
$dispatcher = new Hm_Dispatch($config);
echo $dispatcher->output;
