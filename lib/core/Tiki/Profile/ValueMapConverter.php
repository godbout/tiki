<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_ValueMapConverter
{
    private $map;
    private $implode;

    public function __construct($map, $implodeArray = false)
    {
        $this->map = $map;
        $this->implode = $implodeArray;
    }

    public function convert($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                if (isset($this->map[$v])) {
                    $v = $this->map[$v];
                }
            }

            if ($this->implode) {
                return implode('', $value);
            }

            return $value;
        }
        if (isset($this->map[$value])) {
            return $this->map[$value];
        }

        return $value;
    }

    public function reverse($key)
    {
        $tab = array_flip($this->map);

        if (isset($tab[$key])) {
            return $tab[$key];
        }

        return $key;
    }
}
