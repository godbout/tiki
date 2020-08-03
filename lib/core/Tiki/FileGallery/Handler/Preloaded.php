<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Handler;

use Tiki\FileGallery\FileWrapper\PreloadedContent;

class Preloaded implements HandlerInterface
{
    public function getFileWrapper($file)
    {
        return new PreloadedContent($file->data);
    }

    public function delete($file)
    {
    }

    public function uniquePath($file)
    {
        return $file->path;
    }

    public function isWritable()
    {
        return true;
    }
}
