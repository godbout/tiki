<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Object;

class Selector
{
    private $lib;

    public function __construct($lib)
    {
        $this->lib = $lib;
    }

    public function read($input, $format = null)
    {
        $parts = explode(':', trim($input), 2);

        if (count($parts) === 2) {
            list($type, $object) = $parts;

            return new SelectorItem($this, $type, $object, $format);
        }

        return null;
    }

    public function readMultiple($input, $format = null)
    {
        if (! is_array($input)) {
            $input = explode("\n", $input);
        }

        $raw = array_map('trim', $input);
        $raw = array_unique($raw);
        $raw = array_map(function ($input) use ($format) {
            return $this->read($input, $format);
        }, $raw);

        return array_values(array_filter($raw));
    }

    public function readMultipleSimple($type, $input, $separator, $format = null)
    {
        if (is_string($input)) {
            $parts = explode($separator, $input);
        } else {
            $parts = (array) $input;
        }

        $parts = array_map('trim', $parts);
        $parts = array_filter($parts);
        $parts = array_unique($parts);

        return array_map(function ($object) use ($type, $format) {
            return new SelectorItem($this, $type, $object, $format);
        }, array_values($parts));
    }

    public function getTitle($type, $object, $format = null)
    {
        return $this->lib->get_title($type, $object, $format);
    }
}
