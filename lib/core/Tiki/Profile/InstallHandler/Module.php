<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Module extends Tiki_Profile_InstallHandler
{
	function getData()
	{
		if ($this->data) {
			return $this->data;
		}

		$defaults = [
			'cache' => 0,
			'rows' => 10,
			'custom' => null,
			'groups' => [],
			'params' => [],
			'parse' => null,
		];

		$data = array_merge($defaults, $this->obj->getData());

		$data = Tiki_Profile::convertYesNo($data);
		$data['params'] = Tiki_Profile::convertYesNo($data['params']);

		return $this->data = $data;
	}

	function formatData($data)
	{
		$data['groups'] = serialize($data['groups']);

		$modlib = TikiLib::lib('mod');
		$module_zones = $modlib->module_zones;
		$module_zones = array_map([$this, 'processModuleZones'], $module_zones);
		$module_zones = array_flip($module_zones);
		$data['position'] = $module_zones[$data['position']];
		if (is_null($data['params'])) {
			// Needed on some versions of php to make sure null is not passed all the way to query as a parameter, since params field in db cannot be null
			$data['params'] = '';
		} else {
			$data['params'] = http_build_query($data['params'], '', '&');
		}

		return $data;
	}

	function canInstall()
	{
		$data = $this->getData();
		if (! isset($data['name'], $data['position'], $data['order'])) {
			return false;
		}

		return true;
	}

	function _install()
	{
		$data = $this->getData();

		$modlib = TikiLib::lib('mod');
		$this->replaceReferences($data);
		$data = $this->formatData($data);

		if ($data['custom']) {
			$modlib->replace_user_module($data['name'], $data['name'], (string) $data['custom'], $data['parse']);
		}

		return $modlib->assign_module(0, $data['name'], null, $data['position'], $data['order'], $data['cache'], $data['rows'], $data['groups'], $data['params']);
	}

	private function processModuleZones($zone_id)
	{
		return str_replace('_modules', '', $zone_id);
	}

	public static function export(Tiki_Profile_Writer $writer, $moduleId)
	{
		$modlib = TikiLib::lib('mod');

		if (! $info = $modlib->get_assigned_module($moduleId)) {
			return false;
		}

		$spec = $modlib->get_module_info($info['name']);
		parse_str($info['params'], $module_params);

		foreach ($module_params as $param => & $value) {
			if (isset($spec['params'][$param])) {
				$def = $spec['params'][$param];

				if (isset($def['profile_reference'])) {
					$value = self::handleValueExport($writer, $def, $value);
				}
			}
		}

		$info['params'] = $module_params;

		$data = [
			'name' => $info['name'],
			'position' => $info['position'],
			'order' => $info['ord'],
			'cache' => $info['cache_time'],
			'rows' => $info['rows'],
			'groups' => unserialize($info['groups']),
			'params' => $info['params'],
		];

		if ($custom = $modlib->get_user_module($info['name'])) {
			$data['custom'] = $custom['data'];
			$data['parse'] = $custom['parse'];
		}

		$writer->addObject('module', $moduleId, $data);

		return true;
	}

	private static function handleValueExport($writer, $def, $value)
	{
		$value = $writer->getReference($def['profile_reference'], $value);

		return $value;
	}

	/**
	 * Remove module
	 *
	 * @param string $module
	 * @return bool
	 */
	function remove($module)
	{
		if (! empty($module)) {
			$modlib = TikiLib::lib('mod');
			$query = "select `moduleId` from `tiki_modules` where `name`=? order by `moduleId` desc";
			$moduleId = $modlib->getOne($query, $module);
			if ($modlib->unassign_module($moduleId)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get current module data
	 *
	 * @param array $module
	 * @return mixed
	 */
	public function getCurrentData($module)
	{
		$moduleName = ! empty($module['name']) ? $module['name'] : '';
		$modlib = TikiLib::lib('mod');
		$userModuleInfo = $modlib->get_user_module($moduleName);
		$query = "select `moduleId` from `tiki_modules` where `name`=? order by `moduleId` desc";
		$moduleId = $modlib->getOne($query, $moduleName);
		if (! empty($moduleId)) {
			$module = $modlib->get_assigned_module($moduleId);
			$userModuleInfo = ! empty($userModuleInfo) ? $userModuleInfo : [];
			$moduleData = array_merge($module, $userModuleInfo);
			return $moduleData;
		}
		return false;
	}

	/**
	 * Get wiki page changes
	 *
	 * @param array $before
	 * @param array $after
	 * @return mixed
	 */
	public function getChanges($before, $after)
	{
		if (! empty($before['moduleId']) && ! empty($after['moduleId']) && $before['moduleId'] === $after['moduleId']) {
			return $before;
		}
		return false;
	}

	/**
	 * Revert module data
	 *
	 * @param array $moduleData
	 * @return bool
	 */
	public function revert($moduleData)
	{
		if (! empty($moduleData)) {
			$modlib = TikiLib::lib('mod');
			$modlib->assign_module(
				$moduleData['moduleId'],
				$moduleData['name'],
				$moduleData['title'],
				$moduleData['position'],
				$moduleData['ord'],
				$moduleData['cache_time'],
				$moduleData['rows'],
				$moduleData['groups'],
				$moduleData['params']
			);
			return true;
		}
		return false;
	}
}
