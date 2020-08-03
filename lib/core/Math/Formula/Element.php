<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Element implements ArrayAccess, Iterator, Countable
{
    private $type;
    private $children;

    public function __construct($type, array $children = [])
    {
        $this->type = $type;
        $this->children = $children;
    }

    public function addChild($child)
    {
        $this->children[] = $child;
    }

    public function offsetExists($offset)
    {
        return is_int($offset) && isset($this->children[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->children[$offset])) {
            return $this->children[$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    public function __get($name)
    {
        foreach ($this->children as $child) {
            if ($child instanceof Math_Formula_Element && $child->type == $name) {
                return $child;
            }
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function current()
    {
        $key = key($this->children);

        return $this->children[$key];
    }

    public function next()
    {
        next($this->children);
    }

    public function rewind()
    {
        reset($this->children);
    }

    public function key()
    {
        return key($this->children);
    }

    public function valid()
    {
        return false !== current($this->children);
    }

    public function count()
    {
        return count($this->children);
    }

    public function getExtraValues(array $allowedKeys)
    {
        $extra = [];

        foreach ($this->children as $child) {
            if ($child instanceof self) {
                if (! in_array($child->type, $allowedKeys)) {
                    $extra[] = "({$child->type} ...)";
                }
            } else {
                $extra[] = $child;
            }
        }

        if (count($extra)) {
            return $extra;
        }
    }
}
