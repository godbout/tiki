<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\File\DiagramHelper;
use Tiki\File\FileHelper;

require_once('tiki-setup.php');

$accesslib = TikiLib::lib('access');

if (! isset($_GET['fileId'])) {
	$accesslib->display_error('tiki-display.php', tr('Invalid fileId. Please provide a valid fileId to preview a specific file.'));
}

$filegallib = TikiLib::lib('filegal');
$file = $filegallib->get_file($_GET['fileId']);

if (is_null($file)) {
	$accesslib->display_error('tiki-display.php', tr(sprintf('File ID %s not found.', $_GET['fileId'])));
}

$data = $file['data'];
$templatePath = FileHelper::getDisplayTemplate($file, $data, true);

if ($templatePath === false) {
	$accesslib->display_error('tiki-display.php', tr('Unable to display file.'));
}

$smarty->assign('data', $data);
$smarty->assign('mid', FileHelper::FILE_DISPLAY_TEMPLATE_FOLDER . $templatePath);
$smarty->display('tiki.tpl');
