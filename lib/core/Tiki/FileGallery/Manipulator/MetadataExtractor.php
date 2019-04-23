<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Manipulator;

use TikiLib;

class MetadataExtractor extends Manipulator
{
	public function run($args = [])
	{
		global $prefs, $user;

		$file = $this->file;
		$filegallib = TikiLib::lib('filegal');

		$metadata = $filegallib->extractMetadataJson($file->getWrapper()->getReadableFile());
		$file->setParam('metadata', $metadata);

		if ($file->name === $file->filename) {
			$name = $this->getTitleFromFilename($file->name);
		} else {
			$name = $file->name;
		}
		$file->setParam('name', trim(strip_tags($name)));
		$file->setParam('description', strip_tags($file->description));
		$file->setParam('filename', $this->truncateFilename($file->filename));

		$search_data = '';
		if ($prefs['fgal_enable_auto_indexing'] != 'n') {
			$search_data = $filegallib->get_search_text_for_data($file);
		}
		$file->setParam('search_data', $search_data);

		if (empty($file->created)) {
			$created = $filegallib->now;
			$lastModif = $filegallib->now;
		} else {
			$created = $file->created;
			$lastModif = $filegallib->now;
		}
		$file->setParam('created', $created);
		$file->setParam('lastModif', $lastModif);
		$file->setParam('lastModifUser', $user);

		$file->setParam('filetype', $filegallib->fixMime($file->filetype, $file->filename));

		$ocrLib = Tikilib::lib('ocr');
		$ocr_state = $ocrLib::OCR_STATUS_SKIP;
		if ($prefs['fgal_ocr_enable'] === 'y' && ($args['ocr_file'] || $prefs['fgal_ocr_every_file'] === 'y')) {
			if (in_array($file->filetype, $ocrLib::OCR_MIME_NATIVE)) {
				$ocr_state = $ocrLib::OCR_STATUS_PENDING;
			}
		}
		$file->setParam('ocr_state', $ocr_state);
	}

	private function getTitleFromFilename($title)
	{
		if (strpos($title, '.zip') !== strlen($title) - 4) {
			$title = preg_replace('/\.[^\.]*$/', '', $title); // remove extension
			$title = preg_replace('/[\-_]+/', ' ', $title); // turn _ etc into spaces
			$title = ucwords($title);
		}
		if (strlen($title) > 200) {       // trim to length of name column in database
			$title = substr($title, 0, 200);
		}
		return $title;
	}

	private function truncateFilename($filename)
	{
		if (strlen($filename) > 80) {
			$filename = substr($filename, 0, 38) . '...' . substr($filename, strlen($filename) - 38);
		}
		return $filename;
	}
}
