<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Perms;
use Sabre\DAV;
use TikiLib;

class WikiDirectory extends DAV\Collection
{
    public function getChildren()
    {
        $pages = $this->getWikiPages();
        $children = [];
        foreach ($pages as $page) {
            $children[] = new WikiPage($page['pageName']);
        }

        return $children;
    }

    public function getChild($name)
    {
        $children = $this->getWikiPages();
        foreach ($children as $child) {
            if ($child['pageName'] === $name) {
                return new WikiPage($child['pageName']);
            }
        }
        // We have to throw a NotFound exception if the file didn't exist
        throw new DAV\Exception\NotFound('The wiki page with name: ' . $name . ' could not be found');
    }

    public function childExists($name)
    {
        $children = $this->getWikiPages();
        foreach ($children as $child) {
            if ($child['pageName'] === $name) {
                return true;
            }
        }

        return false;
    }

    public function getName()
    {
        return 'Wiki Pages';
    }

    public function createFile($name, $data = null)
    {
        global $user;

        $perms = Perms::get();
        if (! $perms->edit) {
            throw new DAV\Exception\Forbidden(tr('Permission denied.'));
        }

        $info = Utilities::parseContents($name, $data);

        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page($name, 0, $info['content'], $tikilib->now, "Created from WebDAV", $user, $tikilib->get_ip_address());
    }

    public function createDirectory($name)
    {
        # not supported
    }

    public function delete()
    {
        # not supported
    }

    private function getWikiPages()
    {
        $pages = TikiLib::lib('tiki')->list_pages();

        return $pages['data'];
    }
}
