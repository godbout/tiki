<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Handler;

class System implements HandlerInterface
{
    private $real;

    public function __construct()
    {
        global $prefs;

        if ($prefs['fgal_use_db'] == 'n') {
            $this->real = new FileSystem($prefs['fgal_use_dir']);
            if (! empty($prefs['fgal_preserve_filenames'])) {
                $this->real->setPreserveFilename($prefs['fgal_preserve_filenames'] == 'y');
            }
        } else {
            $this->real = new Preloaded;
        }
    }

    public function getFileWrapper($file)
    {
        return $this->real->getFileWrapper($file);
    }

    public function delete($file)
    {
        return $this->real->delete($file);
    }

    public function uniquePath($file)
    {
        return $this->real->uniquePath($file);
    }

    public function isWritable()
    {
        return $this->real->isWritable();
    }
}
