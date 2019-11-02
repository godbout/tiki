<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_File_Utilities
{
	function checkTargetGallery($galleryId)
	{
		global $prefs;

		if (! $gal_info = $this->getGallery($galleryId)) {
			throw new Services_Exception(tr('Requested gallery does not exist.'), 404);
		}

		$canUpload = TikiLib::lib('filegal')->can_upload_to($gal_info);

		if (! $canUpload) {
			throw new Services_Exception(tr('Permission denied.'), 403);
		}

		return $gal_info;
	}

	function getGallery($galleryId)
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->get_file_gallery_info($galleryId);
	}

	function uploadFile($gal_info, $name, $size, $type, $data, $asuser = null, $image_x = null, $image_y = null, $description = '', $created = '', $title = '')
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->upload_single_file($gal_info, $name, $size, $type, $data, $asuser, $image_x, $image_y, $description, $created, $title);
	}

	function updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser = null, $title = '')
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->update_single_file($gal_info, $name, $size, $type, $data, $fileId, $asuser, $title);
	}
}
