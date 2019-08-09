<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_package_info()
{
	return [
		'name' => tra('Tiki Package Plugin'),
		'documentation' => 'PluginPackage',
		'description' => tra('Display the output of a wiki plugin within a Tiki Package.'),
		'prefs' => ['wikiplugin_package'],
		'body' => '',
		'introduced' => 14,
		'filter' => 'rawhtml_unsafe',
		'iconname' => 'view',
		'tags' => [ 'basic' ],
		'extraparams' => true,
		'params' => [
			'package' => [
				'required' => true,
				'name' => tra('Package Name'),
				'description' => tr('Name of package in the form %0vendor/name%1', '<code>', '</code>'),
				'filter' => 'text',
				'since' => '14.0',
			],
			'plugin' => [
				'required' => true,
				'name' => tra('Name of the plugin'),
				'description' => tr('Name of the plugin file without the %0.php%1', '<code>', '</code>'),
				'filter' => 'text',
				'since' => '14.0',
			],
		],
	];
}

function wikiplugin_package($data, $params)
{
	global $smarty;

	TikiLib::lib('smarty')->loadPlugin('smarty_block_packageplugin');

	return "~np~" . smarty_block_packageplugin($params, $data, $smarty) . "~/np~";
}
