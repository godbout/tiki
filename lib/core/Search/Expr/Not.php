<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Expr_Not implements Search_Expr_Interface
{
    private $expression;
    private $weight = 1.0;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function __clone()
    {
        $this->expression = clone $this->expression;
    }

    public function setType($type)
    {
        $this->expression->setType($type);
    }

    public function setField($field = 'global')
    {
        $this->expression->setField($field);
    }

    public function setWeight($weight)
    {
        $this->weight = (float) $weight;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function walk($callback)
    {
        $result = $this->expression->walk($callback);

        return call_user_func($callback, $this, [$result]);
    }

    public function traverse($callback)
    {
        return call_user_func($callback, $callback, $this, [$this->expression]);
    }
}
