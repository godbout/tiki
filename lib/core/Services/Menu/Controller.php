<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Menu_Controller
{
	/** @var  MenuLib */
	private $menulib;

	function setUp()
	{
		$this->menulib = TikiLib::lib('menu');
	}

	/**
	 * @param JitFilter $input
	 * @return mixed
	 */
	function action_get_menu($input)
	{
		$menuId = $input->menuId->int();
		return $this->menulib->get_menu($menuId);
	}

	/**
	 * @param JitFilter $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_MissingValue
	 * @throws Services_Exception_NotFound
	 */
	function action_edit($input)
	{
		//get menu details
		$menuId = $input->menuId->int();
		$info = $this->menulib->get_menu($menuId);

		if (! $info) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu) {
			throw new Services_Exception_Denied(tr("You don't have permission to edit menus (tiki_p_edit_menu)"));
		}

		$symbol = Tiki_Profile::getObjectSymbolDetails('menu', $menuId);
		$util = new Services_Utilities();
		//execute menu insert/update
		if ($util->isConfirmPost()) {
			$menuId = $input->menuId->int();
			$name = $input->name->text();
			$description = $input->description->text();
			$type = $input->type->text();
			$icon = $input->icon->text();
			$use_items_icons = $input->use_items_icons->int() ? 'y' : 'n';
			$parse = $input->parse->int() ? 'y' : 'n';

			if (! $name) {
				throw new Services_Exception_MissingValue('name');
			}
			$success = $this->menulib->replace_menu($menuId, $name, $description, $type, $icon, $use_items_icons, $parse);
			if ($success) {
				if ($menuId) {
					Feedback::success(tr('The %0 menu has been edited', $name));
				} else {
					Feedback::success(tr('The %0 menu has been created', $name));
				}
			} else {
				if ($menuId) {
					Feedback::error(tr('An error occurred - the %0 menu may not have been edited', $name));
				} else {
					Feedback::error(tr('An error occurred - the %0 menu may not have been created', $name));
				}
			}
		}
		//information for the menu management screen
		return [
			'title' => $info['menuId'] ? tr('Edit Menu') : tr('Create Menu'),
			'info' => $info,
			'symbol' => $symbol,
		];

	}

	function action_edit_icon($input)
	{
		$menuLib=$this->menulib;
		$menuLib->replace_menu_icon($input->optionid->text(), $input->updatedicon->text());
	}

	function action_clone($input)
	{
		$menuId = $input->menuId->int();
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied();
		}

		$util = new Services_Utilities();
		//execute menu cloning
		if ($util->isConfirmPost()) {
			$this->menulib->clone_menu($menuId, $input->name->text(), $input->description->text());
			Feedback::success(tr('Menu %0 cloned as menu %1', $menuDetails['info']['name'], $input->name->text()));
			return [];
		}
		//prepare basic data
		$info = $this->menulib->get_menu($menuId);
		$symbol = Tiki_Profile::getObjectSymbolDetails('menu', $menuId);

		//information for the clone menu screen
		return [
			'title' => tr('Clone this menu and its options'),
			'info' => $info,
			'symbol' => $symbol,
		];
	}

	/**
	 * Display menu option edit form and process replace
	 *
	 * @param JitFilter $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_MissingValue
	 * @throws Services_Exception_NotFound
	 */
	function action_edit_option($input)
	{
		global $prefs;

		$menuLib = $this->menulib;
		$userLib = TikiLib::lib('user');
		$headerlib = TikiLib::lib('header');

		//prepare basic data
		$optionId = $input->optionId->int();
		if ($optionId) {
			$optionInfo = $menuLib->get_menu_option($optionId);

			if (! $optionInfo) {
				throw new Services_Exception_NotFound(tr('Menu option %0 not found', $optionId));
			}
		} else {
			$optionInfo = [];
		}
		$tplGroupContainerId = TikiLib::lib('attribute')->get_attribute('menu', $optionId, 'tiki.menu.templatedgroupid');
		//get menu information
		$menuId = $input->menuId->int();

		if (! $menuId && isset($optionInfo['menuId'])) {
			$menuId = $optionInfo['menuId'];
		}

		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied();
		}

		//get usergroup information
		if (! empty($optionInfo['groupname'])) {
			if (! is_array($optionInfo['groupname'])) {
				$optionInfo['groupname'] = explode(',', $optionInfo['groupname']);
			}
		} else {
			$optionInfo['groupname'] = [];
		}


		// groups info
		$all_groups = $userLib->list_all_groups();
		$option_groups = [];

		if (is_array($all_groups)) {
			foreach ($all_groups as $g) {
				if (in_array($g, $optionInfo['groupname'])) {
					$option_groups[$g] = 'selected="selected"';
				} else {
					$option_groups[$g] = '';
				}
			}
		}

		//get preference information
		$feature_prefs = [];
		foreach ($prefs as $k => $v) {	// attempt to filter out non-feature prefs (still finds 133!)
			if (strpos($k, 'feature') !== false && preg_match_all('/_/m', $k, $m) === 1) {
				$feature_prefs[] = $k;
			}
		}
		$headerlib->add_js('var prefNames = ' . json_encode($feature_prefs) . ';');

		//get permission information
		$headerlib->add_js('var permNames = ' . json_encode($userLib->get_permission_names_for('all')) . ';');
		$util = new Services_Utilities();
		//perform insert or update
		if ($util->isConfirmPost()) {
			//check necessary permissions
			if (! Perms::get('menu', $menuId)->edit_menu_option) {
				throw new Services_Exception_Denied(tr("You don't have permission to edit menu options (tiki_p_edit_menu_option)"));
			}

			$name = $input->name->text();
			if (! $name) {
				throw new Services_Exception_MissingValue('name');
			}

			//check if user enters the position in the form
			$position = $input->position->int();
			if (empty($position)) {
				//get current menu for menuId
				$oldOptions = $this->menulib->list_menu_options($menuId);
				//Get position from current menu
				if ($oldOptions) {
					$position = (int)$oldOptions['cant'] + 1;
				} else {
					$position = 1;
				}
			}

			$url = $input->url->text();
			$type = $input->type->text();
			$section = $input->section->text();
			$perm = $input->perm->text();
			$groupname = $input->asArray('groupname');
			$groupname = implode(',', $groupname);
			$level = $input->level->text();
			$icon = $input->icon->text();
			$class = $input->class->text();
			$class = $input->class->text();
			$tplGroupContainer = $input->tplGroupContainer->text();
			$tplGroupContainerId = '';
			if ($tplGroupContainer && $tplGroupContainer != "None") {
				$tplGroupContainerInfo = TikiLib::lib('user')->get_groupId_info($tplGroupContainer);
				$tplGroupContainerId = $tplGroupContainerInfo["id"];
			}

			//execute insert/update
			$menuLib = $this->menulib;
			$optionId = $menuLib->replace_menu_option($menuId, $optionId, $name, $url, $type, $position, $section, $perm, $groupname, $level, $icon, $class);
			TikiLib::lib('attribute')->set_attribute('menu', $optionId, 'tiki.menu.templatedgroupid', $tplGroupContainerId);

		}

		$tplGroups = TikiLib::lib('user')->get_template_groups_containers();

		//information for the menu option screen
		return [
			'title' => $optionId ? tr('Menu Option %0', $optionInfo["optionId"]) : tr('Create Menu Option'),
			'optionId' => $optionId,
			'menuId' => $menuId,
			'menuInfo' => $menuDetails["info"],
			'menuSymbol' => $menuDetails["symbol"],
			'info' => $optionInfo,
			'option_groups' => $option_groups,
			'templatedGroups' => $tplGroups["data"],
			'tplGroupContainerId' => $tplGroupContainerId,
		];
	}

	/**
	 * @param JitFilter $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_export_menu_options($input)
	{
		//get basic input
		$menuId = $input->menuId->int();
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied();
		}


		//perform menu export
		$menuId = $input->menuId->int();
		$encoding = $input->encoding->text();
		$menuLib = $this->menulib;
		$menuLib->export_menu_options($menuId, $encoding);
		return [];

		//No confirm popup needed as there are no options that can be set currently
/*		return [
			'title' => tr('Export Menu Options'),
			'menuId' => $menuId,
			'menuInfo' => $menuDetails["info"],
			'menuSymbol' => $menuDetails["symbol"],
		];*/
	}

	/**
	 * @param JitFilter $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_import_menu_options($input)
	{
		//get menu details
		$menuId = $input->menuId->int();
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied();
		}

		//execute import
		$redirect = '';
		$util = new Services_Utilities();
		if ($util->isConfirmPost()) {
			$menuId = $input->menuId->int();
			$menuLib = $this->menulib;
			$menuLib->import_menu_options($menuId);
			global $base_url;
			$redirect = $base_url . 'tiki-admin_menu_options.php?menuId=' . $menuId;
			Feedback::success(tr('Your menu options have been imported'));
			Services_Utilities::sendFeedback($redirect);
		}

		//information for the import menu screen
		return [
			'title' => tr('Import Menu Options'),
			'menuId' => $menuId,
			'menuInfo' => $menuDetails["info"],
			'menuSymbol' => $menuDetails["symbol"],
			'FORWARD' => $redirect,
		];
	}

	/**
	 *
	 * @param JitFilter $input
	 * @return array
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_preview($input)
	{
		//get menu details
		$menuId = $input->menuId->int();
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied(tr("You don't have permission to edit menu options (edit_menu_option)"));
		}

		//preview options, see function.menu.php
		$preview_type = $input->preview_type->text() ? $input->preview_type->text() : 'vert';
		$preview_css = $input->preview_css->text() ? 'y' : 'n';
		$preview_bootstrap = $input->preview_bootstrap->text() ? 'y' : 'n';

		//information for the preview menu screen
		return [
			'title' => tr('Menu Preview'),
			'menuId' => $menuId,
			'menuInfo' => $menuDetails["info"],
			'menuSymbol' => $menuDetails["symbol"],
			'preview_type' => $preview_type,
			'preview_css' => $preview_css,
			'preview_bootstrap' => $preview_bootstrap,
		];
	}

	/**
	 * Saves all options in a menu
	 *
	 * @param JitFilter $input
	 * @return array
	 * @throws Exception
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_save($input)
	{

		//get menu details
		$menuId = $input->menuId->int();
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu_option) {
			throw new Services_Exception_Denied(tr("You don't have permission to edit menu options (tiki_p_edit_menu_option)"));
		}

		$util = new Services_Utilities();
		if ($util->checkCsrf()) {

			$oldOptions = $this->menulib->list_menu_options($menuId);
			$options = json_decode($input->data->striptags(), true);

			foreach ($options as $option) {
				$optionId = $option['optionId'];
				if ($optionId) {
					$oldOption = $this->menulib->get_menu_option($optionId);
				} else {
					$optionId = 0;
					$oldOption = [
						'name' => '',
						'url' => '',
						'type' => 'o',
						'position' => 1,
						'section' => '',
						'perm' => '',
						'groupname' => '',
						'userlevel' => 0,
						'icon' => '',
						'class' => ''
					];
				}

				$option = array_merge($oldOption, $option);

				$this->menulib->replace_menu_option(
					$menuId,
					$optionId,
					$option['name'],
					$option['url'],
					$option['type'],
					$option['position'],
					$option['section'],
					$option['perm'],
					$option['groupname'],
					$option['userlevel'],
					$option['icon'],
					$option['class']
				);
			}

			$optionsToRemove = array_filter($oldOptions['data'], function ($item) use ($options) {
				foreach ($options as $option) {
					if ($option['optionId'] == $item['optionId']) {
						return false;    // still here
					}
				}
				return true;    // gone
			});

			foreach ($optionsToRemove as $item) {
				$this->menulib->remove_menu_option($item['optionId']);
			}
		}

		return ['menuId' => $menuId];
	}

	/**
	 * @param $input jitFilter
	 * @return array
	 * @throws Services_Exception
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_remove($input)
	{
		$input->replaceFilters(['menuId' => 'digits', 'referer' => 'url']);
		$util = new Services_Utilities();
		//get menu details
		if ($input->offsetExists('menuId')) {
			$menuId = $input['menuId'];
		} else {
			$util->setDecodedVars($input, ['menuId' => 'digits']);
			$menuId = $util->extra['menuId'];
		}
		$menuDetails = $this->get_menu_details($menuId);

		if (! $menuDetails['info']['menuId']) {
			throw new Services_Exception_NotFound(tr('Menu %0 not found', $menuId));
		}

		//check permissions
		$perms = Perms::get('menu', $menuId);
		if (! $perms->edit_menu) {
			throw new Services_Exception_Denied();
		}

		//execute deletion
		if ($util->isConfirmPost()) {
			$result = $this->menulib->remove_menu($menuId);
			if ($result) {
				Feedback::success(tr('The %0 menu has been removed', $menuDetails['info']['name']));
			} else {
				Feedback::error(tr('The %0 menu has not been removed', $menuDetails['info']['name']));
			}
			return Services_Utilities::refresh($util->extra['referer']);
		} else {
			return [
				'FORWARD' => [
					'modal' => '1',
					'controller' => 'access',
					'action' => 'confirm',
					'confirmAction' => 'remove',
					'confirmController' => 'menu',
					'confirmClass' => 'n',
					'customMsg' => tr('Delete the %0 menu?', $menuDetails['info']['name']),
					'confirmButton' => tra('Delete'),
					'extra' => ['referer' => Services_Utilities::noJsPath(), 'menuId' => $menuId],
				]
			];
		}
	}


	private function get_menu_details($menuId)
	{
		//get menu information
		$menuInfo = $this->menulib->get_menu($menuId);

		if ($menuInfo) {
			//get related symbol information
			$menuSymbol = Tiki_Profile::getObjectSymbolDetails('menu', $menuId);

			//return menu details
			return [
				'info' => $menuInfo,
				'symbol' => $menuSymbol,
			];
		} else {
			return [];
		}
	}
}
