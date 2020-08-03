<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiFilter_AttributeType implements Laminas\Filter\FilterInterface
{
    public function filter($name)
    {
        // Force to have at least two dots to scope the attribute name
        if (substr_count($name, '.') < 2) {
            return false;
        }

        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_\-\.]/', '', $name);

        return $name;
    }
}
