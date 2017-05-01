<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\ComposerManager;

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

global $tikipath;

$composerManager = new ComposerManager($tikipath);

if ($access->ticketMatch()) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($_POST['auto-fix-missing-packages']){
			$smarty->assign('composer_output', $composerManager->fixMissing());
		}
		if ($_POST['auto-install-package']){
			$smarty->assign('composer_output', $composerManager->installPackage($_POST['auto-install-package']));
		}
	}
}



$installableList = $composerManager->getInstalled();
$packagesMissing = array_reduce(
	$installableList,
	function ($carry, $item) {
		return $carry || $item['status'] === ComposerManager::STATUS_MISSING;
	},
	false
);


$smarty->assign('composer_available', $composerManager->composerIsAvailable());
$smarty->assign('composer_packages_installed', $installableList);
$smarty->assign('composer_packages_missing', $packagesMissing);
$smarty->assign('composer_packages_available', $composerManager->getAvailable());