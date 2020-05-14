<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Group_Controller
{
	/**
	 * Filters for $input->replaceFilters() used in the Services_Utilities()->setVars method
	 *
	 * @var array
	 */
	private $filters = [
		'checked' => 'groupname',
		'items' => 'groupname',
		'name' => 'groupname',
		'group' => 'groupname',
		'desc' => 'striptags',
		'home' => 'pagename',
		'groupstracker' => 'int',
		'userstracker' => 'int',
		'registrationUsersFieldIds' => 'digitscolons',
		'userChoice' => 'word',
		'defcat' => 'int',
		'theme' => 'themename',
		'color' => 'striptags',
		'usersfield' => 'int',
		'groupfield' => 'int',
		'expireAfter' => 'int',
		'anniversary' => 'digits',    // format MMDD or DD - NB: this is not an integer
		'prorateInterval' => 'word',
		'user' => 'username'
	];

	/**
	 * Admin groups "perform with checked" but with no action selected
	 *
	 * @param $input
	 * @throws Services_Exception
	 * @throws Exception
	 */
	public function action_no_action()
	{
		Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
	}

	/**
	 * Admin groups "perform with checked" and list item action to remove selected groups
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_remove_groups($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$util = new Services_Utilities();
		$userlib = TikiLib::lib('user');
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'checked');
			$extras = [];
			if ($util->itemsCount > 0) {
				$warnings = array_filter($util->items, function ($item) use ($userlib) {
					$groups = array_map(function ($g) {
						return $g['groupName'];
					}, $userlib->get_included_container_groups($item, false));
					return ! empty($groups);
				});
				if (! empty($warnings)) {
					$extras["warning"] = tr('Some categories are managed by this group. Remove it will be irreversible.');
				}

				if (count($util->items) === 1) {
					$msg = tra('Delete the following group?');
				} else {
					$msg = tra('Delete the following groups?');
				}
				return $util->confirm($msg, tra('Delete'), ["warning" => $extras]);
			} else {
				Services_Utilities::modalException(tra('No groups were selected. Please select one or more groups.'));
			}
			//after confirm submit - perform action and return success feedback
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			//filter out Admins group so it can't be deleted. Anonymous and Registered are protected from deletion in
			//in the remove groups function
			$fitems = array_diff($util->items, ['Admins']);
			$notDeleted = array_intersect($util->items, ['Admins']);

			$logslib = TikiLib::lib('logs');
			$deleted = [];
			foreach ($fitems as $group) {
				$result = $userlib->remove_group($group);
				if ($result) {
					$logslib->add_log('admingroups', 'removed group ' . $group);
					$deleted[] = $group;
				} else {
					$notDeleted[] = $group;
				}
			}
			//prepare and send feedback
			if (count($notDeleted) > 0) {
				if (count($notDeleted) === 1) {
					$msg1 = tr('The following group cannot be deleted:');
				} else {
					$msg1 = tr('The following groups cannot be deleted:');
				}
				$feedback1 = [
					'tpl' => 'action',
					'mes' => $msg1,
					'items' => $notDeleted,
				];
				Feedback::error($feedback1);
			}
			if (count($deleted) > 0) {
				if (count($deleted) === 1) {
					$msg2 = tr('The following group has been deleted:');
				} else {
					$msg2 = tr('The following groups have been deleted:');
				}
				$feedback2 = [
					'tpl' => 'action',
					'mes' => $msg2,
					'items' => $deleted,
				];
				Feedback::success($feedback2);
			}
			//return to page
			return Services_Utilities::refresh($this->extra['referer']);
		}
	}

	/**
	 * Process add group form
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_new_group($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters);
			if (! empty($input['name'])) {
				$newGroupName = trim($input->name->groupname());
				$userlib = TikiLib::lib('user');
				if ($userlib->group_exists($newGroupName)) {
					Services_Utilities::modalException(tra('Group already exists'));
				} else {
					$msg = tr('Create the group %0?', $newGroupName);
					return $util->confirm($msg, tra('Create'));
				}
			} else {
				Services_Utilities::modalException(tra('Group name cannot be empty'));
				return;
			}

			if (! empty($input['isTplGroup']) && ! empty($input["include_groups"])) {
				Services_Utilities::modalException(tra('Template Group cannot inherit from other groups'));
			}


			//after confirm submit - perform action and return feedback
		} elseif ($util->checkCsrf()) {
			//set parameters
			$util->setDecodedVars($input, $this->filters);
			$params = $this->prepareParameters($util->extra);
			$userlib = TikiLib::lib('user');
			//add group and inclusions
			$newGroupId = $userlib->add_group(
				$params['name'],
				$params['desc'],
				$params['home'],
				$params['userstracker'],
				$params['groupstracker'],
				$params['registrationUsersFieldIds'],
				$params['userChoice'],
				$params['defcat'],
				$params['theme'],
				$params['usersfield'],
				$params['groupfield'],
				'n',
				$params['expireAfter'],
				$params['emailPattern'],
				$params['anniversary'],
				$params['prorateInterval'],
				$params['color'],
				$params['isRole'],
				$params['isTplGroup']
			);
			if (isset($util->extra['include_groups'])) {
				foreach ($util->extra['include_groups'] as $include) {
					if ($util->extra['name'] != $include) {
						$userlib->group_inclusion($util->extra['name'], $include);
					}
				}
				$groups = $userlib->get_group_info($util->extra['include_groups']);

				$templateGroups = array_filter($groups, function ($item) {
					return $item["isTplGroup"] == "y";
				});
				foreach ($templateGroups as $templateGroup) {
					$categories = TikiLib::lib('categ')->get_managed_categories($templateGroup["id"]);
					$managedIds = array_unique(array_map(function ($item) {
						return $item["categId"];
					}, $categories));

					foreach ($managedIds as $managedId) {
						TikiLib::lib('categ')->manage_sub_categories($managedId);
					}
				}
			}

			$logslib = TikiLib::lib('logs');
			$logslib->add_log('admingroups', 'created group ' . $util->extra['name']);
			//prepare feedback
			if ($newGroupId) {
				$feedback1 = [
					'tpl' => 'action',
					'mes' => tr('Group %0 (ID %1) successfully created', $util->extra['name'], $newGroupId),
				];
				Feedback::success($feedback1);
			} else {
				$feedback2 = [
					'tpl' => 'action',
					'mes' => tr('Group %0 not created', $util->extra['name']),
				];
				Feedback::error($feedback2);
			}
			//return to page - take off query and anchor to ensure return to the first tab
			return Services_Utilities::refresh($util->extra['referer'], 'queryAndAnchor');
		} else {
			//post CSRF error through js. can't just throw a services exception since the form started as a non-modal
			//but confirmation is modal and js takes over after the confirmation is submitted
			return ['error' => 'CSRF'];
		}
	}

	/**
	 * Process modify group form
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_modify_group($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$userlib = TikiLib::lib('user');
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters);

			$children = $userlib->get_group_children_with_permissions($input['olgroup']);


			if (! empty($input['name']) && isset($input['olgroup'])) {
				$newGroupName = trim($input['name']);
				$userlib = TikiLib::lib('user');
				$users = $userlib->get_group_users($input['name']);
				if (! empty($users) && $input["isRole"] == "on") {
					Services_Utilities::modalException(tra('Role groups can\'t have users.'));
				}
				if (! empty($input['isTplGroup']) && ! empty($input["include_groups"]) && ! empty($input["include_groups"][0])) {
					Services_Utilities::modalException(tra('Template Group cannot inherit from other groups'));
				}
				if (! empty($input['include_groups'])) {
					$permissions = $userlib->get_group_permissions($input['olgroup']);
					if (! empty($permissions)) {
						$groups = $userlib->get_group_info($input['include_groups']->asArray());
						foreach ($groups as $group) {
							if ($group["isTplGroup"] == "y") {
								Services_Utilities::modalException(tr('Template Group children cannot have permission: %0', $group["groupName"]));
							}
						}
					}
				}
				$extras = [];
				$oldIncluded = $userlib->get_included_groups($input['olgroup'], false);
				$oldIncludes = array_diff($oldIncluded, $input['include_groups'] ? $input['include_groups']->toArray() : []);
				$oldGroups = $userlib->get_group_info(array_values($oldIncludes));
				$parentGroupsIds = array_map(function ($item) {
					return $item["id"];
				}, array_filter($oldGroups, function ($item) {
					return $item["isTplGroup"] == "y";
				}));

				if (! empty($parentGroupsIds)) {
					$extras["warning"] = tr('Some categories are managed by this group. Remove it will be irreversible.');
				}



				if (! empty($input['isTplGroup']) && $children["cant"] > 0) {
					$names = [];
					foreach ($children["data"] as $child) {
						$names[] = $child["groupName"];
					}
					Services_Utilities::modalException(tr('Template Group children cannot have permission: %0', implode(",", $names)));
				}
				if (! empty($input['isTplGroup']) && ! empty($users)) {
					Services_Utilities::modalException(tra('Template Group cannot have associated users'));
				}
				if ($input['olgroup'] !== $newGroupName && $userlib->group_exists($newGroupName)) {
					Services_Utilities::modalException(tra('Group already exists'));
				} else {
					$msg = tr('Modify the group %0?', $newGroupName);
					return $util->confirm($msg, tra('Modify'), $extras);
				}
			} else {
				Services_Utilities::modalException(tra('Group name cannot be empty'));
				return;
			}
			//after confirm submit - perform action and return success feedback
		} elseif ($util->checkCsrf()) {
			//set parameters
			$util->setDecodedVars($input, $this->filters);
			$params = $this->prepareParameters($util->extra);
			$success = $userlib->change_group(
				$params['olgroup'],
				$params['name'],
				$params['desc'],
				$params['home'],
				$params['userstracker'],
				$params['groupstracker'],
				$params['usersfield'],
				$params['groupfield'],
				$params['registrationUsersFieldIds'],
				$params['userChoice'],
				$params['defcat'],
				$params['theme'],
				'n',
				$params['expireAfter'],
				$params['emailPattern'],
				$params['anniversary'],
				$params['prorateInterval'],
				$params['color'],
				$params['isRole'],
				$params['isTplGroup'],
				$params['include_groups']
			);



			$logslib = TikiLib::lib('logs');
			$logslib->add_log('admingroups', 'modified group ' . $params['olgroup'] . ' to ' . $params['name']);
			//prepare feedback
			if ($success) {
				$feedback1 = [
					'tpl' => 'action',
					'mes' => tr('Group %0 successfully modified', $params['name']),
				];
				Feedback::success($feedback1);
			} else {
				$feedback2 = [
					'tpl' => 'action',
					'mes' => tr('Group %0 not modified', $params['name']),
				];
				Feedback::error($feedback2);
			}
			//return to page - strip query and anchor so that we return to the group listing
			return Services_Utilities::refresh($util->extra['referer'], 'queryAndAnchor');
		} else {
			//post CSRF error through js. can't just throw a services exception since the form started as a non-modal
			//but confirmation is modal and js takes over after the confirmation is submitted
			return ['error' => 'CSRF'];
		}
	}

	/**
	 * Process add user to group action
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_add_user($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'user');
			$userlib = TikiLib::lib('user');
			$group = $userlib->get_group_info($input['group']);
			if ($group["isRole"] == "y") {
				Services_Utilities::modalException(tra('Role groups can\'t have users.'));
			}
			if ($util->itemsCount > 0) {
				if ($util->itemsCount === 1) {
					$msg = tr('Add the following user to group %0?', $input['group']);
				} else {
					$msg = tr('Add the following users to group %0?', $input['group']);
				}
				return $util->confirm(
					$msg,
					tra('Add'),
					[
						'fields' => [
							[
								'label' => tr('Please confirm this operation by typing your password'),
								'field' => 'input',
								'type' => 'password',
								'name' => 'confirmpassword',
								'placeholder' => tr('Password')
							]
						]
					]
				);
			} else {
				Services_Utilities::modalException(tra('One or more users must be selected'));
			}
			//after confirm submit - perform action and return success feedback
		} elseif ($util->checkCsrf()) {
			$userlib = TikiLib::lib('user');
			$pass = $input->offsetGet('confirmpassword');
			$user = isset($_SESSION['u_info']['login']) ? $_SESSION['u_info']['login'] : '';
			$ret = $userlib->validate_user($user, $pass);
			if (! $ret[0]) {
				Services_Utilities::modalException(tra('Invalid password'));
			}

			$util->setDecodedVars($input, $this->filters);
			$logslib = TikiLib::lib('logs');
			foreach ($util->items as $user) {
				$userlib->assign_user_to_group($user, $util->extra['group']);
				$logslib->add_log('admingroups', 'added ' . $user . ' to ' . $util->extra['group']);
			}
			//prepare and send feedback
			if (count($util->items) > 0) {
				if (count($util->items) === 1) {
					$msg = tr('The following user was added to group %0:', $util->extra['group']);
				} else {
					$msg = tr('The following users were added to group %0:', $util->extra['group']);
				}
				$feedback = [
					'tpl' => 'action',
					'mes' => $msg,
					'items' => $util->items,
				];
				Feedback::success($feedback);
			}
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}


	/**
	 * Process ban user from group action
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_ban_user($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'user');
			if ($util->itemsCount > 0) {
				if ($util->itemsCount === 1) {
					$msg = tr('Ban the following user from group %0?', $input['group']);
				} else {
					$msg = tr('Ban the following users from group %0?', $input['group']);
				}
				return $util->confirm(
					$msg,
					tra('Ban'),
					[
						'fields' => [
							[
								'label' => tr('Please confirm this operation by typing your password'),
								'field' => 'input',
								'type' => 'password',
								'name' => 'confirmpassword',
								'placeholder' => tr('Password')
							]
						]
					]
				);
			} else {
				Services_Utilities::modalException(tra('One or more users must be selected'));
			}
			//after confirm submit - perform action and return success feedback
		} elseif ($util->checkCsrf()) {
			$userlib = TikiLib::lib('user');
			$pass = $input->offsetGet('confirmpassword');
			$user = isset($_SESSION['u_info']['login']) ? $_SESSION['u_info']['login'] : '';
			$ret = $userlib->validate_user($user, $pass);
			if (! $ret[0]) {
				Feedback::error(tra('Invalid password.'));
				return Services_Utilities::closeModal();
			}

			$util->setDecodedVars($input, $this->filters);
			$logslib = TikiLib::lib('logs');
			foreach ($util->items as $user) {
				$userlib->ban_user_from_group($user, $util->extra['group']);
				$logslib->add_log('admingroups', 'banned ' . $user . ' from ' . $util->extra['group']);
			}
			//prepare and send feedback
			if ($util->itemsCount > 0) {
				if ($util->itemsCount === 1) {
					$msg = tr('The following user was banned from group %0:', $util->extra['group']);
				} else {
					$msg = tr('The following users were banned from group %0:', $util->extra['group']);
				}
				$feedback = [
					'tpl' => 'action',
					'mes' => $msg,
					'items' => $util->items,
				];
				Feedback::success($feedback);
			}
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Process unban user from group action
	 *
	 * @param $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 */
	function action_unban_user($input)
	{
		Services_Exception_Denied::checkGlobal('admin');
		$util = new Services_Utilities();
		//first pass - show confirm modal popup
		if ($util->notConfirmPost()) {
			$util->setVars($input, $this->filters, 'user');
			if ($util->itemsCount > 0) {
				if ($util->itemsCount === 1) {
					$msg = tr('Unban the following user from group %0?', $input['group']);
				} else {
					$msg = tr('Unban the following users from group %0?', $input['group']);
				}
				return $util->confirm($msg, tra('Unban'));
			} else {
				Services_Utilities::modalException(tra('One or more users must be selected'));
			}
			//after confirm submit - perform action and return success feedback
		} elseif ($util->checkCsrf()) {
			$util->setDecodedVars($input, $this->filters);
			$userlib = TikiLib::lib('user');
			$logslib = TikiLib::lib('logs');
			foreach ($util->items as $user) {
				$userlib->unban_user_from_group($user, $util->extra['group']);
				$logslib->add_log('admingroups', 'unbanned ' . $user . ' from ' . $util->extra['group']);
			}
			//prepare and send feedback
			if ($util->itemsCount > 0) {
				if (count($util->items) === 1) {
					$msg = tr('The following user was unbanned from group %0:', $util->extra['group']);
				} else {
					$msg = tr('The following users were unbanned from group %0:', $util->extra['group']);
				}
				$feedback = [
					'tpl' => 'action',
					'mes' => $msg,
					'items' => $util->items,
				];
				Feedback::success($feedback);
			}
			//return to page
			return Services_Utilities::refresh($util->extra['referer']);
		}
	}

	/**
	 * Utility to prepare parameters for add_group and change group userlib functions
	 *
	 * @param array $extra
	 * @return array
	 * @throws Exception
	 */
	private function prepareParameters(array $extra)
	{
		$extra = new JitFilter($extra);
		$extra->replaceFilters($this->filters);
		$extra = $extra->asArray();
		$extra['home'] = isset($extra['home']) ? $extra['home'] : '';
		$extra['theme'] = isset($extra['theme']) ? $extra['theme'] : '';
		$extra['color'] = isset($extra['color']) ? $extra['color'] : '';
		$extra['defcat'] = ! empty($extra['defcat']) ? $extra['defcat'] : 0;
		$extra['userChoice'] = isset($extra['userChoice']) && $extra['userChoice'] == 'on' ? 'y' : '';
		$extra['expireAfter'] = empty($extra['expireAfter']) ? 0 : $extra['expireAfter'];
		$extra['isRole'] = isset($extra['isRole']) && $extra['isRole'] == 'on' ? 'y' : '';
		$extra['isTplGroup'] = isset($extra['isTplGroup']) && $extra['isTplGroup'] == 'on' ? 'y' : '';

		$defaults = [
			'groupstracker' => 0,
			'groupfield' => 0,
			'userstracker' => 0,
			'usersfield' => 0,
			'registrationUsersFieldIds' => ''
		];
		global $prefs;
		$prefGroupTracker = isset($prefs['groupTracker']) and $prefs['groupTracker'] == 'y';
		$prefUserTracker = isset($prefs['userTracker']) and $prefs['userTracker'] == 'y';
		if (! empty($extra['groupstracker']) || ! empty($extra['userstracker'])) {
			if ($prefGroupTracker || $prefUserTracker) {
				$trklib = TikiLib::lib('trk');
				$trackerlist = $trklib->list_trackers(0, -1, 'name_asc', '');
				$trackers = $trackerlist['list'];
				if ($prefGroupTracker && isset($extra['groupstracker']) && isset($trackers[$extra['groupstracker']])) {
					$defaults['groupstracker'] = $extra['groupstracker'];
					if (isset($extra['groupfield']) && $extra['groupfield']) {
						$defaults['groupfield'] = $extra['groupfield'];
					}
				}
				if ($prefUserTracker && isset($extra['userstracker']) && isset($trackers[$extra['userstracker']])) {
					$defaults['userstracker'] = $extra['userstracker'];
				}
				if (isset($extra['usersfield']) && $extra['usersfield']) {
					$defaults['usersfield'] = $extra['usersfield'];
				}
				if (! empty($extra['registrationUsersFieldIds'])) {
					$defaults['registrationUsersFieldIds'] = $extra['registrationUsersFieldIds'];
				}
			}
		}
		$ret = array_merge($extra, $defaults);
		return $ret;
	}
}
