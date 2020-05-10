<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if ($prefs['feature_theme_control'] == 'y') {
	include('tiki-tc.php');
}
if ($prefs['feature_banning'] == 'y') {
	global $user;
	if ($msg = TikiLib::lib('tiki')->check_rules($user, $section)) {
		if (isset($ajaxRequest) && $ajaxRequest) {
			Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
		} else {
			Feedback::errorPage($msg);
		}
	}
}
