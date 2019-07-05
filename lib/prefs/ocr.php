<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_ocr_list()
{
	$langLib = TikiLib::lib('language');
	$ocr = TikiLib::lib('ocr');

	$ocrLangs = $langLib->FindLanguageNames($ocr->getTesseractLangs());
	// Place the default (OSD) at the top
	$ocrLangs = ['osd' => tr('Orientation and script detection' . ' - '. tr('auto select'))] + $ocrLangs;

	try{
		$tesseractPath = $ocr->whereIsExecutable('tesseract');
		$pdfimagesPath = $ocr->whereIsExecutable('pdfimages');
	}catch (Exception $e){
		$tesseractPath = 'tesseract';
		$pdfimagesPath = 'pdfimages';
	}

	return [
		'ocr_enable' => [
			'name' => tra('OCR Files'),
			'type' => 'flag',
			'default' => 'n',
			'description' => tra('Extract and index text from supported file types.'),
			'keywords' => 'ocr optical character recognition',
			'dependencies' => ['feature_file_galleries'],
			'packages_required' => ['thiagoalessio/tesseract_ocr' => 'thiagoalessio\TesseractOCR\TesseractOCR',
									'media-alchemyst/media-alchemyst' => 'MediaAlchemyst\Alchemyst'],
		],
		'ocr_every_file' => [
			'name' => tra('OCR Every File'),
			'type' => 'flag',
			'description' => tra('Attempt to OCR every supported file.'),
			'default' => 'n',
		],
		'ocr_default_languages' => [
			'name' => tra('OCR processing languages'),
			'description' => tra('Select the default languages that the OCR process will use.'),
			'filter' => 'text',
			'type' => 'multilist',
			'options' => $ocrLangs,
			'default' => ['osd'],
		],
		'ocr_limit_languages' => [
			'name' => tra('OCR limit languages'),
			'description' => tra('Limit the number of languages one can select from this list.'),
			'filter' => 'text',
			'type' => 'multilist',
			'options' => $ocrLangs,
			'default' => ['osd'],
		],
		'ocr_tesseract_path' => [
			'name' => tra('tesseract path'),
			'description' => tra('Path to the location of the binary. Defaults to the $PATH location.'),
			'hint' => 'If blank, the $PATH will be used, but will likely fail with scheduler.',
			'type' => 'text',
			'size' => '256',
			'filter' => 'text',
			'default' => $tesseractPath,
		],
		'ocr_pdfimages_path' => [
			'name' => tra('pdfimages path'),
			'description' => tra('Path to the location of the binary. Defaults to the $PATH location.'),
			'hint' => 'If blank, the $PATH will be used, but will likely fail with scheduler.',
			'type' => 'text',
			'size' => '256',
			'filter' => 'text',
			'default' => $pdfimagesPath,
		],
	];
}