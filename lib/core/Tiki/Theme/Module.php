<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Theme;

use TikiLib;

/**
 * Class that handles tiki theme module operations
 *
 * @access public
 */
class Module
{
	/**
	 * Add or update module
	 *
	 * @param array $data
	 * @return bool|string
	 */
	public function addOrUpdate($data)
	{
		$modLib = TikiLib::lib('mod');
		$default = [
			'name' => '',
			'title' => '',
			'position' => '',
			'order' => 0,
			'cache' => 0,
			'rows' => 10,
			'groups' => null,
			'params' => null,
			'type' => null
		];
		$data = array_merge($default, $data);
		$groups = ! empty($data['groups']) ? serialize($data['groups']) : null;
		$moduleId = $modLib->assign_module(0, $data['name'], $data['title'], $data['position'], $data['order'], $data['cache'], $data['rows'], $groups, $data['params'], $data['type']);
		if (! empty($moduleId)) {
			return $data['name'];
		}
		return false;
	}
}
