<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use TikiLib;

class Utilities {
  static function checkUploadPermission($galleryDefinition) {
    $canUpload = TikiLib::lib('filegal')->can_upload_to($galleryDefinition->getInfo());
    if (! $canUpload) {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkCreatePermission($galleryDefinition) {
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $galleryDefinition->getInfo());
    if ($perms['tiki_p_create_file_galleries'] != 'y') {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkDeleteGalleryPermission($galleryDefinition) {
    global $user, $prefs;

    $info = $galleryDefinition->getInfo();
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $info);

    $mygal_to_delete = ! empty($user) && $info['type'] === 'user' && $info['user'] !== $user && $perms['tiki_p_userfiles'] === 'y' && $info['parentId'] !== $prefs['fgal_root_user_id'];

    if ($perms['tiki_p_admin_file_galleries'] != 'y' && ! $mygal_to_delete) {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkDeleteFilePermission($galleryDefinition) {
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $galleryDefinition->getInfo());
    if ($perms['tiki_p_remove_files'] != 'y' && $perms['tiki_p_admin_file_galleries'] != 'y') {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function parseContents($name, $data) {
    if (is_resource($data)) {
      $content = stream_get_contents($data);
    } else {
      $content = (string)$data;
    }

    $filesize = strlen($content);
    $mime = TikiLib::lib('mime')->from_content($name, $content);

    return compact($content, $filesize, $mime);
  }
}
