<?php

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_block_packageview($params, $content, $smarty)
{
	extract($params, EXTR_SKIP);

	if (empty($params['package']) || empty($params['view'])) {
		return tra("Please specify the name of the package and the view.");
	}

	if (!$extensionPackage = \Tiki\Package\ExtensionManager::get($params['package'])) {
		return tr('Package %0 is not enabled', $params['package']);
	}

	$path = $extensionPackage->getPath() . '/Views/' . $params['view'] . '.php';

	if (! file_exists($path)) {
		return tra("Error: Unable to locate view file for the package.");
	}

	require_once($path);

	$namespace = $extensionPackage->getBaseNamespace();
	if (!empty($namespace)) {
		$namespace .= '\\Views\\';
	}
	$functionname = $namespace . $params['view'];

	if (! function_exists($functionname)) {
		return tra("Error: Unable to locate function name for the view.");
	}

	if ($params['assign']) {
		$smarty->assign($params['assign'], $functionname($content, $params, $smarty));
	} else {
		return $functionname($content, $params, $smarty);
	}
}
