<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_Transport_File implements Tiki_Profile_Transport_Interface
{
    private $path;

    public function __construct($path, $profileName)
    {
        $this->path = "$path/$profileName";
    }

    public function getPageContent($pageName)
    {
        $fullpath = $this->path . '/' . $pageName . '.wiki';
        if (file_exists($fullpath)) {
            return file_get_contents($fullpath);
        }

        return ''; // assume empty if file not found to prevent unexpected errors
    }

    public function getPageParsed($pageName)
    {
        $content = $this->getPageContent($pageName);

        if ($content) {
            return TikiLib::lib('parser')->parse_data($content);
        }

        return ''; // assume empty if file not found to prevent unexpected errors
    }

    public function getProfilePath()
    {
        return $this->path;
    }
}
