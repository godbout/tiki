<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * @return array
 */
function module_quickadmin_info()
{
	return [
		'name' => tra('Quick Administration'),
		'description' => tra('Some helpful tools for administrators.'),
		'prefs' => [],
		'params' => [
			'mode' => [
				'name' => tra('Mode'),
				'description' => tra('Display mode: module or header. Leave empty for module mode'),
			],
			'display' => [
				'name' => tra('Display'),
				'description' => tra('Shown: Display shortcuts or preferences history or both (both / shortcuts / history)'),
				'filter' => 'alpha',
				'default' => 'both',
				'since' => '21',
				'options' => [
					['text' => tra('Both'), 'value' => 'both'],
					['text' => tra('Only shortcuts'), 'value' => 'shortcuts'],
					['text' => tra('Only preferences history'), 'value' => 'history'],
				],
			],
		]
	];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_quickadmin($mod_reference, $module_params)
{

	if (empty($module_params['display'])) {
		$module_params['display'] = 'both';
	}
	if ($module_params['display'] == 'shortcuts') {
		TikiLib::lib('smarty')->assign('only_shortcuts', 'y');
	}
	if ($module_params['display'] == 'history') {
		TikiLib::lib('smarty')->assign('only_prefs_history', 'y');
		TikiLib::lib('smarty')->assign('recent_prefs', TikiLib::lib('prefs')->getRecent());
	}
	if ($module_params['display'] == 'both') {
		TikiLib::lib('smarty')->assign('recent_prefs', TikiLib::lib('prefs')->getRecent());
	}
}
