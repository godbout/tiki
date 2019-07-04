<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_ocr_list()
{

	return [
		'ocr_enable' => [
			'name' => tra('OCR Files'),
			'type' => 'flag',
			'default' => 'n',
			'description' => tra('Extract and index text from supported file types.'),
			'keywords' => 'ocr optical character recognition',
			'dependencies' => ['feature_file_galleries'],
			'packages_required' => ['thiagoalessio/tesseract_ocr' => 'thiagoalessio\TesseractOCR\TesseractOCR'],
		],
		'ocr_every_file' => [
			'name' => tra('OCR Every File'),
			'type' => 'flag',
			'description' => tra('Attempt to OCR every supported file.'),
			'default' => 'n',
		],
		'ocr_tesseract_path' => [
			'name' => tra('tesseract path'),
			'description' => tra('Path to the location of the binary. Defaults to the $PATH location.'),
			'hint' => 'If blank, the $PATH will be used, but will likely fail with scheduler.',
			'type' => 'text',
			'size' => '256',
			'default' => trim(shell_exec('type -p tesseract')),
		],
		'ocr_pdfimages_path' => [
			'name' => tra('pdfimages path'),
			'description' => tra('Path to the location of the binary. Defaults to the $PATH location.'),
			'hint' => 'If blank, the $PATH will be used, but will likely fail with scheduler.',
			'type' => 'text',
			'size' => '256',
			'default' => trim(shell_exec('type -p pdfimages')),
		],
	];
}