<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\FileWrapper;

class PreloadedContent implements WrapperInterface
{
    private $data;

    private $temporaryFile = false;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __destruct()
    {
        if (false !== $this->temporaryFile) {
            \unlink($this->temporaryFile);
        }
    }

    public function getReadableFile()
    {
        if (false !== $this->temporaryFile) {
            return $this->temporaryFile;
        }

        $sIniUploadTmpDir = \ini_get('upload_tmp_dir');
        if (! empty($sIniUploadTmpDir)) {
            $sTmpDir = \ini_get('upload_tmp_dir');
        } else {
            $sTmpDir = '/tmp';
        }

        $this->temporaryFile = $tmpfname = \tempnam($sTmpDir, 'wiki_');
        @\file_put_contents($tmpfname, $this->data);

        return $tmpfname;
    }

    public function getContents()
    {
        return $this->data;
    }

    public function getChecksum()
    {
        return md5($this->data);
    }

    public function getSize()
    {
        return function_exists('mb_strlen') ? mb_strlen($this->data, '8bit') : strlen($this->data);
    }

    public function isFileLocal()
    {
        return false;
    }

    public function replaceContents($data)
    {
        $this->data = $data;
    }

    public function getStorableContent()
    {
        return [
            'data' => $this->data,
            'path' => null,
            'filesize' => $this->getSize(),
            'hash' => $this->getChecksum(),
        ];
    }
}
