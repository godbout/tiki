<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\FileWrapper;

class PhysicalFile implements WrapperInterface
{
    private $path;
    private $basePath;

    public function __construct($basePath, $path)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->path = $path;
    }

    public function getReadableFile()
    {
        return $this->fullPath();
    }

    public function getContents()
    {
        $tmpfname = $this->fullPath();

        return \file_get_contents($tmpfname);
    }

    public function getChecksum()
    {
        $tmpfname = $this->fullPath();
        if (filesize($tmpfname) > 0) {
            return md5_file($tmpfname);
        }

        return md5(time());
    }

    public function getSize()
    {
        return filesize($this->fullPath());
    }

    public function isFileLocal()
    {
        return true;
    }

    public function replaceContents($data)
    {
        $dest = $this->fullPath();
        $baseDir = dirname($dest);

        if (! file_exists($baseDir)) {
            mkdir($baseDir, umask() ^ 0777, true);
        }

        if (is_writable($baseDir) && (! file_exists($dest) || is_writable($dest))) {
            $result = file_put_contents($dest, $data);
        } else {
            $result = false;
        }

        if ($result === false) {
            throw new WriteException(tr("Unable to write to destination path: %0", $dest));
        }
    }

    public function getStorableContent()
    {
        return [
            'data' => null,
            'path' => $this->path,
            'filesize' => $this->getSize(),
            'hash' => $this->getChecksum(),
        ];
    }

    private function fullPath()
    {
        return $this->basePath . '/' . $this->path;
    }
}
