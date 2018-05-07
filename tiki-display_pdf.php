<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

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

$fileSrc = $_REQUEST['fileSrc'];
$url = $accesslib->absoluteUrl($fileSrc);

$headerlib = TikiLib::lib('header');
$headerlib->add_jsfile($pdfJsfile);
$headerlib->add_jsfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.js');
$headerlib->add_jsfile('lib/jquery_tiki/tiki-pdfjs.js');
$headerlib->add_cssfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.css');

$smarty->assign('mid', 'tiki-display_pdf.tpl');
$smarty->display('tiki.tpl');
