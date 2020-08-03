<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation;

class RecommendationSet implements \Countable, \Iterator
{
    private $engine;
    private $recommendations = [];
    private $debug = [];

    public function __construct($engineName)
    {
        $this->engine = $engineName;
    }

    public function add(EngineOutput $recommendation)
    {
        if ($recommendation instanceof Recommendation) {
            $this->recommendations[] = $recommendation;
        } else {
            $this->addDebug($recommendation);
        }
    }

    public function addDebug($info)
    {
        $this->debug[] = $info;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function getDebug()
    {
        return new \ArrayIterator($this->debug);
    }

    public function count()
    {
        return count($this->recommendations);
    }

    public function current()
    {
        return current($this->recommendations);
    }

    public function next()
    {
        next($this->recommendations);
    }

    public function key()
    {
        return key($this->recommendations);
    }

    public function valid()
    {
        return current($this->recommendations) !== false;
    }

    public function rewind()
    {
        reset($this->recommendations);
    }
}
