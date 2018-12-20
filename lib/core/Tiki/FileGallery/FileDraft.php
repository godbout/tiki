<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery;

class FileDraft extends File
{
  public $param = [
    "fileId" => 0,
    "filename" => "",
    "filesize" => 0,
    "filetype" => "",
    "data" => "",
    "user" => "",
    "path" => "",
    "hash" => "",
    "metadata" => "",
    "lastModif" => 0,
    "lockedby" => "",
  ];

  function __construct($params = [])
  {
    global $mimetypes;
    include_once('lib/mime/mimetypes.php');

    $this->setParam('filetype', $mimetypes["txt"]);
    $this->setParam('filename', tr("New File"));

    $this->init($params);
  }

  static function fromFile($file) {
    $draft = new FileDraft;
    $draft->setParams(array_intersect_key($file->getParams(), $draft->getParams()));
    return $draft;
  }

  static function fromFileDraft($params) {
    $draft = new FileDraft;
    $draft->setParams($params);
    return $draft;
  }

  function setParams($params) {
    $this->param = $params;
  }

  function init($params) {
    foreach ($params as $key => $val) {
      $this->setParam($key, $val);
    }
  }
}
