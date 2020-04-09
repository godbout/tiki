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
function module_package_info()
{
	return [
		'name' => tra('Package Module'),
		'description' => tra('A module that shows content from a Tiki Package View'),
		'params' => [
			'package' => [
				'required' => true,
				'name' => tra('Name of package (vendor/name)'),
				'description' => tra('Name of package in the form vendor/name'),
				'filter' => 'text',
			],
			'view' => [
				'required' => true,
				'name' => tra('Name of the view'),
				'description' => tra('Name of the view file without the .php'),
				'filter' => 'text',
			],
			'otherparams' => [
				'required' => false,
				'name' => tra('Other parameters'),
				'description' => tra('URL encoded string of other parameters (will not replace standard parameters)'),
				'filter' => 'text',
			]
		],
	];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_package($mod_reference, $module_params)
{
	$smarty = TikiLib::lib('smarty');

	if (empty($module_params['package']) || empty($module_params['view'])) {
		$smarty->assign('error', tra("Please specify the name of the package and the view."));
		return;
	}

	if (! $extensionPackage = \Tiki\Package\ExtensionManager::get($module_params['package'])) {
		return tr('Package %0 is not enabled', $module_params['package']);
	}

	$path = $extensionPackage->getPath() . '/Views/' . $module_params['view'] . '.php';


	if (! file_exists($path)) {
		$smarty->assign('error', tra("Error: Unable to locate view file for the package."));
		return;
	}

	require_once($path);

	$namespace = $extensionPackage->getBaseNamespace();
	if (! empty($namespace)) {
		$namespace .= '\\Views\\';
	}
	$functionname = $namespace . $module_params['view'];

	if (! function_exists($functionname)) {
		$smarty->assign('error', tra("Error: Unable to locate view file for the package."));
		return;
	}

	$functionname('', $module_params);

	$smarty->assign('view', $module_params['view']);
	$smarty->assign('package', $module_params['package']);
	$smarty->assign('folder', $extensionPackage->getPath());
}
