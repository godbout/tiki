<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use \Tiki\File\PDFHelper;
use Tiki\Lib\Unoconv\UnoconvLib;
use Tiki\Package\VendorHelper;

require_once('tiki-setup.php');

global $tikilib;

$accesslib = TikiLib::lib('access');
if ($tikilib->get_preference('fgal_pdfjs_feature') !== 'y') {
	$accesslib->display_error('tiki-display_pdf.php', tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.'));
}

$errorMessageToAppend = '';
$oldPdfJsFile = VendorHelper::getAvailableVendorPath('pdfjs', 'npm-asset/pdfjs-dist/build/pdf.js', false);
if (file_exists($oldPdfJsFile)) {
	$errorMessageToAppend = 'Previous npm-asset/pdfjs-dist package has been deprecated.<br/>';
}

$vendorPath = VendorHelper::getAvailableVendorPath('pdfjsviewer', 'vendor/npm-asset/pdfjs-dist-viewer-min/build/minified/build/pdf.js', false);
if (! file_exists($vendorPath)) {
	$accesslib->display_error('tiki-display_pdf.php', tr($errorMessageToAppend . 'To view PDF files Tiki needs the npm-asset/pdfjs-dist-viewer-min. If you do not have permission to install this package, ask the site administrator.'));
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
}

if (empty($sourceLink)) {
	$accesslib->display_error('', tr('Invalid request'));
} else {
	$htmlViewFile = $vendorPath . '/npm-asset/pdfjs-dist-viewer-min/build/minified/web/viewer.html?file=';
	$sourceLink = $htmlViewFile . urlencode(TikiLib::lib('access')->absoluteUrl($sourceLink));
};

$smarty->assign('source_link', $sourceLink);
$smarty->assign('export_pdf_link', $exportLink);

$headerlib = TikiLib::lib('header');
$headerlib->add_css("
	.iframe-container {
		overflow: hidden;
		padding-top: 56.25%;
		position: relative;
		height: 900px;
	}
	
	.iframe-container iframe {
		border: 0;
		height: 100%;
		left: 0;
		position: absolute;
		top: 0;
		width: 100%;
	}
	
	@media (max-width: 767px) {
		.iframe-container {
			height: 500px;
		} 
	}
	
	@media (min-width: 768px) AND (max-width: 991px) {
		.iframe-container {
			height: 600px;
		}
	}
	
	@media (min-width: 992px) AND (max-width: 1209px) {
		.iframe-container {
			height: 700px;
		}
	}
");

$smarty->assign('mid', 'tiki-display_pdf.tpl');
$smarty->display('tiki.tpl');
