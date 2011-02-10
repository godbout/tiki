<?php

// (c) Copyright 2002-2010 by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
$access->check_script($_SERVER["SCRIPT_NAME"],basename(__FILE__));

if ( !isset($_REQUEST['mobile_mode']) || $_REQUEST['mobile_mode'] === 'y' ) {

	require_once 'lib/mobileesp/mdetect.php';

	$uagent_info = new uagent_info();

	if (( isset($_REQUEST['mobile_mode']) && $_REQUEST['mobile_mode'] === 'y' ) ||
			$uagent_info->DetectIphoneOrIpod() ||
			$uagent_info->DetectIpad() ||
			$uagent_info->DetectAndroid() ||
			$uagent_info->DetectBlackBerry() ||
			$uagent_info->DetectOperaMobile() ||
			$uagent_info->DetectPalmWebOS()) {		// supported by jquery.mobile

		$prefs['mobile_mode'] = 'y';
		$prefs['feature_jquery_ui'] = 'n';
		$prefs['feature_fullscreen'] = 'n';
		$prefs['feature_syntax_highlighter'] = 'n';

		$prefs['mobile_perspectives'] = unserialize($prefs['mobile_perspectives']);
		if (count($prefs['mobile_perspectives']) > 0) {
			global $perspectivelib; require_once 'lib/perspectivelib.php';

			if (!in_array($perspectivelib->get_current_perspective( $prefs ), $prefs['mobile_perspectives'])) {
				$_SESSION['current_perspective'] = $prefs['mobile_perspectives'][0];
			}
		}
	} else {
		$prefs['mobile_mode'] = 'n';
	}
}
