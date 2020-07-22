<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Cypht_Controller
{
	function action_ajax($input)
	{
		global $tikipath, $tikiroot, $logslib;

		define('VENDOR_PATH', $tikipath.'/vendor_bundled/vendor/');
		define('APP_PATH', VENDOR_PATH.'jason-munro/cypht/');
		define('WEB_ROOT', $tikiroot.'vendor_bundled/vendor/jason-munro/cypht/');
		define('DEBUG_MODE', false);

		define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D'); // Cypht uses for asset cache busting but we run the assets through Tiki pipeline, so no need to generate a unique key here
		define('SITE_ID', 'Tiki-Integration');

		$session_prefix = $input->hm_session_prefix->text() ?? 'cypht';

		/* get includes */
		require APP_PATH.'lib/framework.php';
		require_once $tikipath.'/lib/cypht/integration/classes.php';

		/* get configuration */
		$config = new Tiki_Hm_Site_Config_File(APP_PATH.'hm3.rc', $session_prefix);

		/* process the request */
		$dispatcher = new Hm_Dispatch($config);

		if(! empty($_SESSION[$session_prefix]['user_data']['debug_mode_setting'])) {
			$msgs = Hm_Debug::get();
			foreach ($msgs as $msg) {
				$logslib->add_log('cypht', $msg);
			}
		}

		// either html or already json encoded, so skip broker/accesslib output and do it here
		echo $dispatcher->output;
		exit;
	}
}
