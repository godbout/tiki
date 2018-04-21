<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class that contains helpers functions for wiki plugins
 */
class WikiPlugin_Helper
{
	/**
	 * Check if analytics code should be used.
	 * If $user is anonymous the analytics is always displayed.
	 *
	 * @param array $prefs
	 * @return boolean
	 */
	public static function showAnalyticsCode($prefs)
	{
		global $user;

		if ($user && ! empty($prefs['group_option'])) {
			if ($prefs['group_option'] == 'included' && empty($prefs['groups'])) {
				return true;
			}

			if ($prefs['group_option'] == 'excluded' && empty($prefs['groups'])) {
				return false;
			}

			$userlib = TikiLib::lib('user');
			$userGroups = $userlib->get_user_groups($user);
			$availableGroups = explode(',', $prefs['groups']);
			$validGroups = array_intersect($userGroups, $availableGroups);

			if (($prefs['group_option'] == 'included' && empty($validGroups)) ||
				($prefs['group_option'] == 'excluded' && ! empty($validGroups))) {
				return false;
			}
		}

		return true;
	}
}
