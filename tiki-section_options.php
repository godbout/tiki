<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
global $prefs;
if ($prefs['feature_theme_control'] == 'y' && (! isset($banningOnly) || ! $banningOnly)) {
	include('tiki-tc.php');
}
if ($prefs['feature_banning'] == 'y') {
	global $user;
	if ($msg = TikiLib::lib('tiki')->check_rules($user, $section)) {
		if (isset($ajaxRequest) && $ajaxRequest) {
			Services_Utilities::modalException(tr('You are banned from %0', $section));
		} else {
			Feedback::errorPage($msg);
		}
	}
}
