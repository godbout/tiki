<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_ExtWiki extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        $data = $this->obj->getData();

        return $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['name'], $data['url'])) {
            return false;
        }

        return true;
    }

    public function _install()
    {
        $data = $this->getData();

        $this->replaceReferences($data);

        TikiLib::lib('admin')->replace_extwiki(null, $data['url'], $data['name']);

        return $data['name'];
    }
}
