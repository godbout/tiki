<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Search ResultSet transformer which takes care of tracker field permissions.
 * The way this works is via special field in the search index: field_permissions.
 * It stores special tracker field permissions as JSON structure inside the index.
 * Each tracker item stores the list of fields with special permissions and the
 * list of users and groups having access to that field.
 * Once ResultSet is populated after search results are retrieved, this transformer
 * makes sure only visible fields are left in the search result.
 */

class Search_Formatter_Transform_FieldPermissionEnforcer
{
	private $user;
	private $groups;

	function __construct()
	{
		global $user;

		$this->user = $user;
		$this->groups = Perms::get()->getGroups();
	}

	function __invoke($entry)
	{
		if (Perms::get()->admin) {
			return $entry;
		}

		if (! empty($entry['tracker_id'])) {
			$perms = Perms::get(['type' => 'tracker', 'object' => $entry['trackerId']]);
			if ($perms->admin_trackers) {
				return $entry;
			}
		}

		if (!empty($entry['field_permissions'])) {
			$fieldPermissions = json_decode($entry['field_permissions'], true);
			if (empty($fieldPermissions)) {
				return $entry;
			}
			foreach ($fieldPermissions as $permName => $allowed) {
				if (! in_array($this->user, $allowed['allowed_users']) && ! array_intersect($this->groups, $allowed['allowed_groups'])) {
					foreach ($allowed['perm_names'] as $permName) {
						unset($entry[$permName]);
						$entry['ignored_fields'][] = $permName;
					}
				}
			}
		}
		return $entry;
	}
}
