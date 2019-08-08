<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
use Tiki\FileGallery\File;

/**
 * Class Services_Diagram_Controller
 *
 * Controller for diagram related operations
 *
 */
class Services_Diagram_Controller
{
	/**
	 * Controller setup function. Runs before any action
	 * @throws Services_Exception_Disabled
	 */
	public function setUp()
	{
		Services_Exception_Disabled::check('wikiplugin_diagram');
	}

	/**
	 * Function used to cache a diagram.
	 * If the diagram is represented inline in a page, the cache file name result will be the md5 of the content
	 * @param $input
	 * @return bool|void
	 * @throws Exception
	 * @return bool
	 */
	public function action_image($input)
	{
		global $prefs;

		if ($prefs['fgal_export_diagram_on_image_save'] !== 'y') {
			return false;
		}

		$cacheLib = TikiLib::lib('cache');
		$fileId = $input->fileId->int();
		$data = $input->data->value(); // Cache data is stored in base64 avoiding the need to decode/encode for pdf export
		$key = $input->content->value();

		if (! is_null($fileId)) {
			$file = File::id($fileId);

			if (empty($file)) {
				return false;
			}

			$key = $file->data();
		}

		return $cacheLib->cacheItem(md5($key), $data, 'diagram');
	}
}
