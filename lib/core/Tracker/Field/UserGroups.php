<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for UserGroups
 *
 * Letter key: ~usergroups~
 *
 */
class Tracker_Field_UserGroups extends Tracker_Field_Abstract
{
	public static function getTypes()
	{
		return [
			'usergroups' => [
				'name' => tr('User Groups'),
				'description' => tr('Display the list of groups for the user associated with the tracker items.'),
				'help' => 'User Groups',
				'prefs' => ['trackerfield_usergroups'],
				'tags' => ['advanced'],
				'default' => 'n',
				'params' => [
					'directOnly' => [
						'name' => tr('Show direct groups memberships only'),
						'description' => tr('Do not show inherited/included groups'),
						'filter' => 'int',
						'options' => [
							0 => tr('No'),
							1 => tr('Yes'),
						],
						'default' => 0,
					],
					'specifyFields' => [
						'name' => tr('Specify Fields'),
						'description' => tr('Get the groups for users from these fields, not the "owner" user fields.'),
						'separator' => '|',
						'filter' => 'int',
						'profile_reference' => 'tracker_field',
						'parent' => 'input[name=trackerId]',
						'parentkey' => 'tracker_id',
						'sort_order' => 'position_nasc',
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$itemId = $this->getItemId();

		$value = [];

		if ($itemId) {
			$fields = $this->getOption('specifyFields');

			if (empty(array_filter($fields))) {
				$itemUsers = $this->getTrackerDefinition()->getItemUsers($itemId);
			} else {
				$trackerId = $this->getConfiguration('trackerId');
				$trackerlib = TikiLib::lib('trk');
				$itemUsers = array_map(
					function ($fieldId) use ($trackerId, $itemId, $trackerlib) {
						$owners = $trackerlib->get_item_value($trackerId, $itemId, $fieldId);
						return $trackerlib->parse_user_field($owners);
					}, $fields);

				$itemUsers = call_user_func_array('array_merge', $itemUsers);
			}

			if (! empty($itemUsers)) {
				$tikilib = TikiLib::lib('tiki');
				foreach ($itemUsers as $itemUser) {
					$value = array_merge($value, array_diff(
						$tikilib->get_user_groups($itemUser, ! $this->getOption('directOnly')),
						['Registered', 'Anonymous']
					));
				}
			}
			$value = array_unique(array_filter($value));
			natcasesort($value);
		}

		return ['value' => $value];
	}

	function renderInput($context = [])
	{
		return $this->renderOutput($context);
	}

	function renderOutput($context = [])
	{
		return $this->renderTemplate('trackeroutput/usergroups.tpl', $context);
	}

	public function watchCompare($old, $new)
	{
		// TODO properly
		return '';
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$baseKey = $this->getBaseKey();
		$data = $this->getFieldData();
		$listtext = implode(' ', $data['value']);

		return [
			$baseKey => $typeFactory->multivalue($data['value']),
			"{$baseKey}_text" => $typeFactory->plaintext($listtext),
		];
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey];
	}

	function getGlobalFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey => true];
	}
}
