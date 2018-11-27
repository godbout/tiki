<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use \Tiki\File\PDFHelper;
use Tiki\Lib\Unoconv\UnoconvLib;

require_once('tiki-setup.php');

global $tikilib;

$accesslib = TikiLib::lib('access');

if ($tikilib->get_preference('fgal_pdfjs_feature') !== 'y') {
	$accesslib->display_error('tiki-display_pdf.php', tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.'));
}

$pdfJsfile = 'vendor/npm-asset/pdfjs-dist/build/pdf.js';

if (! file_exists($pdfJsfile)) {
	$accesslib->display_error('tiki-display_pdf.php', tr('To view PDF files Tiki needs the npm-asset/pdfjs-dist package. If you do not have permission to install this package, ask the site administrator.'));
}

if (isset($_REQUEST['fileSrc'])) {
	$sourceLink = $downloadLink = $_REQUEST['fileSrc'];
}

if (! empty($_REQUEST['fileId'])) {
	$filegallib = TikiLib::lib('filegal');
	$info = $filegallib->get_file($_REQUEST['fileId']);

	$exportLink = null;

	if (PDFHelper::canConvertToPDF($info['filetype'])) {
		$accesslib->check_feature('fgal_convert_documents_pdf');

		if (! UnoconvLib::isLibraryAvailable()) {
			$accesslib->display_error('tiki-display_pdf.php', tr('To view office document files Tiki needs the media-alchemyst/media-alchemyst package. If you do not have permission to install this package, ask the site administrator.'));
		}

		$exportLink = sprintf('tiki-download_file.php?fileId=%s&pdf', $_REQUEST['fileId']);
	}

	$smarty->loadPlugin('smarty_modifier_sefurl');
	$sourceLink = smarty_modifier_sefurl($_REQUEST['fileId'], 'display');
	$downloadLink = smarty_modifier_sefurl($_REQUEST['fileId'], 'file');
}

if (empty($sourceLink)) {
	$accesslib->display_error('', tr('Invalid request'));
};

$smarty->assign('source_link', $sourceLink);
$smarty->assign('download_link', $downloadLink);
$smarty->assign('export_pdf_link', $exportLink);

$headerlib = TikiLib::lib('header');
$headerlib->add_jsfile($pdfJsfile);
$headerlib->add_jsfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.js');
$headerlib->add_jsfile('lib/jquery_tiki/tiki-pdfjs.js');
$headerlib->add_cssfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.css');

$smarty->assign('mid', 'tiki-display_pdf.tpl');
$smarty->display('tiki.tpl');
