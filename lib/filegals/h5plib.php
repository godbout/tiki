<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

class H5PLib
{
	/**
	 * Lib version, used for cache-busting of style and script file references.
	 * Keeping track of the DB version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	private $H5PTiki = null;

	function __construct()
	{
		$this->H5PTiki = new \H5P_H5PTiki();
	}

	function __destruct()
	{
	}

	function handle_file_creation($args)
	{
		if ($metadata = $this->getRequestMetadata($args)) {

			$validator = H5P_H5PTiki::get_h5p_instance('validator');

			if ($validator->isValidPackage()) {

				$content = [];    // TODO

				$storage = H5P_H5PTiki::get_h5p_instance('storage');
				$storage->savePackage($content);

				// TODO: Somehow connect the filename/fileId and $storage->contentId ? Needed when .h5p file is updated, deleted(or worse?)
			} else {

				// TODO: What to do if the file isn't a valid H5P? Seems a bit drastic to delete the file – but then again, why would we host broken files?
				// @unlink($interface->getUploadedH5pPath());
			}

		}
	}

	function handle_file_update($args)
	{
		if (isset($args['initialFileId']) && $metadata = $this->getRequestMetadata($args)) {

			// TODO: Similar to creation, only we need to find the related contentId before saving the package.

			// Clear content dependency cache
			//$interface->deleteLibraryUsage($content['id']);
			//$storage->savePackage($content);
		}
	}

	private function getRequestMetadata($args)
	{
		$metadata = null;

		if ($this->isZipFile($args) && $zip = $this->getZipFile($args['object'])) {

			if ($manifest = $this->getH5PManifest($zip)) {
				$metadata = $this->getMetadata($manifest);
			}

			$zip->close();
		}

		return $metadata;
	}

	private function isZipFile($args)
	{
		if (!isset($args['filetype'])) {
			return false;
		}

		return in_array($args['filetype'], array('application/zip', 'application/x-zip', 'application/x-zip-compressed'));
	}

	private function getZipFile($fileId)
	{
		global $prefs, $tikipath;

		if (!class_exists('ZipArchive')) {
			Feedback::error(tra('PHP Class "ZipArchive" not found'));
		}

		$filegallib = TikiLib::lib('filegal');

		if (!$info = $filegallib->get_file_info($fileId, false, true, false)) {
			return null;
		}

		// make a copy of the h5p file for the validator to unpack (and eventually delete)
		$dir = $filegallib->get_gallery_save_dir($info['galleryId']);

		$dest = $tikipath . 'temp/' . $info['filename'];
		if ($dir) {
			copy($dir . $info['path'], $dest);
		} else {
			file_put_contents($dest, $info['data']);
		}

		/** @var ZipArchive $zip */
		$zip = new ZipArchive;
		$interface = H5P_H5PTiki::get_h5p_instance('interface');

		$filepath = $interface->getUploadedH5pPath($dest);

		if ($zip->open($filepath) === true) {
			return $zip;
		}
	}

	/**
	 * @param ZipArchive $zip
	 * @return mixed
	 */
	private function getH5PManifest($zip)
	{
		return $zip->getFromName('h5p.json');
	}

	private function getMetadata($manifest)
	{

		return json_decode($manifest, false);
	}

}
