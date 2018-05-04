<?php
// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');

$pdfJsfile = 'vendor/npm-asset/pdfjs-dist/build/pdf.js';
$pdfJsAvailable = file_exists($pdfJsfile);
$smarty->assign('pdfJsAvailable', $pdfJsAvailable);

$headerlib = TikiLib::lib('header');
$headerlib->add_jsfile($pdfJsfile);
$headerlib->add_jsfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.js');
$headerlib->add_cssfile('vendor/npm-asset/pdfjs-dist/web/pdf_viewer.css');

$fileSrc = $_REQUEST['fileSrc'];
$url = TikiLib::lib('access')->absoluteUrl($fileSrc);
$smarty->assign('url', $url);

$smarty->assign('mid', 'tiki-display_pdf.tpl');
$smarty->display('tiki.tpl');
