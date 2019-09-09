<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use Tiki\Lib\Unoconv\UnoconvLib;
use Tiki\Package\VendorHelper;
use TikiLib;

/**
 * Class FileHelper
 * Generic FileHelper which includes logic
 * @package Tiki\File
 */
class FileHelper
{
	const FILE_DISPLAY_TEMPLATE_FOLDER = 'file_displays/';

	/**
	 * Function used to retrieve the file template display based on the file type
	 * Data is also passed by reference to parse its information if needed
	 *
	 * @param $file
	 * @param $data
	 * @param bool $injectDependencies If we want to retrieve only the file name, we can prevent injecting the Javascript files
	 * @return string
	 * @throws \Exception
	 */
	public static function getDisplayTemplate($file, &$data, $injectDependencies = false)
	{
		global $tikilib;
		$smarty = TikiLib::lib('smarty');
		$accesslib = TikiLib::lib('access');
		$headerlib = Tikilib::lib('header');
		$template = false;

		if (DiagramHelper::isDiagram($file['fileId'])) {
			// Diagrams
			if ($injectDependencies) {
				$errorMessageToAppend = '';
				$oldVendorPath = VendorHelper::getAvailableVendorPath('mxgraph', 'xorti/mxgraph-editor/drawio/webapp/js/app.min.js', false);
				if ($oldVendorPath) {
					$errorMessageToAppend = 'Previous xorti/mxgraph-editor package has been deprecated.<br/>';
				}

				$vendorPath = VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram/js/app.min.js', false);
				if (! $vendorPath) {
					$accesslib->display_error('tiki-display.php', tr($errorMessageToAppend . 'To view diagrams Tiki needs the tikiwiki/diagram package. If you do not have permission to install this package, ask the site administrator.'));
				}

				$headerlib->add_js_config("var diagramVendorPath = '{$vendorPath}';");
				$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);
				$headerlib->add_jsfile($vendorPath . '/tikiwiki/diagram/js/app.min.js', true);
			}

			$data = DiagramHelper::parseData($data);
			$data = DiagramHelper::getDiagramsFromIdentifier($data);
			$template = 'diagram.tpl';
		} elseif ($file['filetype'] == 'application/pdf' || PDFHelper::canConvertToPDF($file['filetype'])) {
			// PDFs
			if ($tikilib->get_preference('fgal_pdfjs_feature') !== 'y') {
				$accesslib->display_error('tiki-display.php', tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.'));
			}

			$errorMessageToAppend = '';
			$oldPdfJsFile = VendorHelper::getAvailableVendorPath('pdfjs', 'npm-asset/pdfjs-dist/build/pdf.js', false);
			if (file_exists($oldPdfJsFile)) {
				$errorMessageToAppend = 'Previous npm-asset/pdfjs-dist package has been deprecated.<br/>';
			}

			$vendorPath = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/build/pdf.js', false);
			if (! file_exists($vendorPath)) {
				$accesslib->display_error('tiki-display.php', tr($errorMessageToAppend . 'To view PDF files Tiki needs the npm-asset/pdfjs-dist-viewer-min. If you do not have permission to install this package, ask the site administrator.'));
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
						$accesslib->display_error('tiki-display.php', tr('To view office document files Tiki needs the media-alchemyst/media-alchemyst package. If you do not have permission to install this package, ask the site administrator.'));
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
				// smarty_modifier_sefurl return &amp; that is already encoded, revert so when url is encoded, it works.
				$sourceLink = preg_replace('/amp;/', '', $sourceLink);
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

			$template = 'pdf.tpl';
		}

		return $template;
	}

	/**
	 * Returns if a given mime-type belongs to a office document type
	 *
	 * @param $mimeType
	 * @return bool
	 */
	public static function isOfficeDocument($mimeType)
	{
		return strpos($mimeType, 'application/vnd.openxmlformats-officedocument') !== false ||
			strpos($mimeType, 'application/vnd.ms') !== false ||
			$mimeType == 'application/msword' ||
			strpos($mimeType, 'application/vnd.oasis.opendocument.') !== false;
	}
}
