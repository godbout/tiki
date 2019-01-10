<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Manipulator;

use TikiLib;
use Feedback;

class Validator extends Manipulator
{
  public function run($args = []) {
    if (! $this->isValid($this->file->filename)) {
      Feedback::error(tr('`%0` does not match acceptable naming patterns.', $this->file->filename));
      return false;
    }

    if (! $this->areDupesAllowed()) {
      Feedback::error(tr('Duplicate file found as `%0`. Upload rejected.', $this->file->filename));
      return false;
    }

    return true;
  }

  private function isValid($filename)
  {
    global $prefs;
    if (! empty($prefs['fgal_match_regex'])) {
      if (! preg_match('/' . $prefs['fgal_match_regex'] . '/', $filename)) {
        return false;
      }
    }
    if (! empty($prefs['fgal_nmatch_regex'])) {
      if (preg_match('/' . $prefs['fgal_nmatch_regex'] . '/', $filename)) {
        return false;
      }
    }

    return true;
  }

  private function areDupesAllowed() {
    global $prefs;

    $filesTable = TikiLib::lib('filegal')->table('tiki_files');
    
    if ($prefs['fgal_allow_duplicates'] !== 'y') {
      $conditions = [
        'hash' => $this->file->hash,
        'fileId' => $filesTable->not($this->file->fileId)
      ];
      if ($prefs['fgal_allow_duplicates'] === 'different_galleries') {
          $conditions['galleryId'] = $galleryId;
      }
      if ($filesTable->fetchCount($conditions)) {
        return false;
      }
    }

    return true;
  }
}