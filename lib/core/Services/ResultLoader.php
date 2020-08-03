<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Iterator to read from paginated results until the end is reached. Allows to read
 * a complete result set without loading it all into memory at once. The loader uses
 * a callback function to obtain the result from either the database or a web service.
 */
class Services_ResultLoader implements Iterator
{
    private $offset = 0;
    private $perPage;
    private $callback;
    private $position = 0;

    private $data;
    private $loaded = 0;
    private $isLast = false;

    public function __construct($callback, $perPage = 50)
    {
        $this->perPage = (int) $perPage;
        $this->callback = $callback;
    }

    public function current()
    {
        return $this->data[$this->position % $this->perPage];
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        if ($this->position >= $this->loaded && ! $this->isLast) {
            $this->data = call_user_func($this->callback, $this->position, $this->perPage);
            $this->isLast = count($this->data) < $this->perPage;
            $this->loaded += count($this->data);
        }

        return isset($this->data[$this->position % $this->perPage]);
    }
}
