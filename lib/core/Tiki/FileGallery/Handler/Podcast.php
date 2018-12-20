<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Handler;

class Podcast extends FileSystem
{
	private $directory;

	function __construct()
	{
		global $prefs;
		parent::__construct($prefs['fgal_podcast_dir']);
	}

  function uniquePath($file) {
    if (! empty($file->path)) {
      return $file->path;
    }
    // for podcast galleries add the extension so the
    // file can be called directly if name is known,
    $extension = '';
    $path_parts = pathinfo($file->name);
    if (in_array(strtolower($path_parts['extension']), ['m4a', 'mp3', 'mov', 'mp4', 'm4v', 'pdf', 'flv', 'swf', 'wmv'])) {
      $extension = '.' . strtolower($path_parts['extension']);
    }
    $fhash = md5($file->name);
    while (file_exists($this->directory . '/' . $fhash . $extension)) {
      $fhash = md5(uniqid($fhash));
    }
    return $fhash . $extension;
  }
}
