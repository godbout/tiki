<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery;

use TikiLib;

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

    public function __construct($params = [])
    {
        global $mimetypes;
        include_once(__DIR__ . '/../../../mime/mimetypes.php');

        $this->setParam('filetype', $mimetypes["txt"]);
        $this->setParam('filename', tr("New File"));

        $this->init($params);
    }

    public static function fromFile($file)
    {
        $draft = new FileDraft;
        $draft->setParams(array_intersect_key($file->getParams(), $draft->getParams()));

        return $draft;
    }

    public static function fromFileDraft($params)
    {
        $draft = new FileDraft;
        $draft->setParams($params);

        return $draft;
    }

    public static function id($id = 0)
    {
        $file = File::id($id);
        $params = TikiLib::lib("filegal")->get_file_draft((int)$id);
        if ($params) {
            return new self($params);
        }

        return $file;
    }

    public function setParams($params)
    {
        $this->param = $params;
    }

    public function init($params)
    {
        foreach ($params as $key => $val) {
            $this->setParam($key, $val);
        }
    }

    public function galleryDefinition()
    {
        $filegallib = TikiLib::lib('filegal');
        $file = $filegallib->get_file($this->fileId);

        return $filegallib->getGalleryDefinition($file['galleryId']);
    }
}
