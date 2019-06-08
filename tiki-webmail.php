<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Cypht Integration
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

require_once("tiki-setup.php");

$access->check_feature('feature_webmail');
$access->check_permission_either(['tiki_p_use_webmail', 'tiki_p_use_group_webmail']);
$access->check_user($user);

$_SESSION['cypht']['preference_name'] = 'cypht_user_config';

define('VENDOR_PATH', $tikipath.'/vendor_bundled/vendor/');
define('APP_PATH', VENDOR_PATH.'jason-munro/cypht/');
define('DEBUG_MODE', false);

// TODO: make these dynamic
define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D');
define('SITE_ID', 'Tiki-Integration');

/* get includes */
require_once APP_PATH.'lib/framework.php';
require_once $tikipath.'/cypht/integration/classes.php';

if (empty($_SESSION['cypht']['request_key'])) {
  $_SESSION['cypht']['request_key'] = Hm_Crypt::unique_id();
}
$_SESSION['cypht']['username'] = $user;

TikiLib::lib('header')->add_css("
.inline-cypht * { box-sizing: content-box; }
.inline-cypht { position: relative; }
");

/* get configuration */
$config = new Tiki_Hm_Site_Config_File(APP_PATH.'hm3.rc');

/* process the request */
$dispatcher = new Hm_Dispatch($config);

$smarty->assign('output_data', '<div class="inline-cypht"><input type="hidden" id="hm_page_key" value="'.Hm_Request_Key::generate().'" />'
	. $dispatcher->output
	. "</div>");
$smarty->assign('mid', 'tiki-webmail.tpl');
$smarty->display('tiki.tpl');
