<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiFilter_HtmlPurifier implements Laminas\Filter\FilterInterface
{
    private $cache;

    public function __construct($cacheFolder)
    {
        $this->cache = $cacheFolder;
    }

    public function filter($data)
    {
        require_once('lib/htmlpurifier_tiki/HTMLPurifier.tiki.php');

        return HTMLPurifier($data);
    }
}
