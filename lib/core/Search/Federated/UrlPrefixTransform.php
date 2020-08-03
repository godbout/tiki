<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Federated;

class UrlPrefixTransform
{
    private $prefix;

    public function __construct($prefix)
    {
        $this->prefix = rtrim($prefix, '/');
    }

    public function __invoke($entry)
    {
        if (isset($entry['url'])) {
            $entry['url'] = $this->prefix . '/' . ltrim($entry['url'], '/');
            $entry['_external'] = true;
        }

        return $entry;
    }
}
