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

if (empty($_SESSION['cypht']['username']) || $_SESSION['cypht']['username'] != $user) {
  unset($_SESSION['cypht']);
  $headerlib = TikiLib::lib('header');
  $headerlib->add_js('
document.cookie = "hm_reload_folders=1";
for(var i =0; i < sessionStorage.length; i++){
    var key = sessionStorage.key(i);
    if (key.indexOf(window.location.pathname) > -1) {
        sessionStorage.removeItem(key);
    }
}
  ');
}

if (empty($_SESSION['cypht']['preference_name']) || $_SESSION['cypht']['preference_name'] != 'cypht_user_config'
  || (! empty($_SESSION['cypht']['username']) && $_SESSION['cypht']['username'] != $user)) {
  // resetting the session on purpose - could be coming from PluginCypht
  $_SESSION['cypht'] = [];
  $_SESSION['cypht']['preference_name'] = 'cypht_user_config';
}

define('VENDOR_PATH', $tikipath.'/vendor_bundled/vendor/');
define('APP_PATH', VENDOR_PATH.'jason-munro/cypht/');
define('WEB_ROOT', $tikiroot.'vendor_bundled/vendor/jason-munro/cypht/');
define('DEBUG_MODE', false);

define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D'); // Cypht uses for asset cache busting but we run the assets through Tiki pipeline, so no need to generate a unique key here
define('SITE_ID', 'Tiki-Integration');

/* get includes */
require_once APP_PATH.'lib/framework.php';
require_once $tikipath.'/lib/cypht/integration/classes.php';

if (empty($_SESSION['cypht']['request_key'])) {
  $_SESSION['cypht']['request_key'] = Hm_Crypt::unique_id();
}
$_SESSION['cypht']['username'] = $user;

/* get configuration */
$config = new Tiki_Hm_Site_Config_File(APP_PATH.'hm3.rc');

/* process the request */
$dispatcher = new Hm_Dispatch($config);

if(! empty($_SESSION['cypht']['user_data']['debug_mode_setting'])) {
  $msgs = Hm_Debug::get();
  foreach ($msgs as $msg) {
    $logslib->add_log('cypht', $msg);
  }
}

$smarty->assign('output_data', '<div class="inline-cypht"><input type="hidden" id="hm_page_key" value="'.Hm_Request_Key::generate().'" />'
	. $dispatcher->output
	. "</div>");
$smarty->assign('mid', 'tiki-webmail.tpl');
$smarty->display('tiki.tpl');

