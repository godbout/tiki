<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Expr_Initial implements Search_Expr_Interface
{
    private $string;
    private $type;
    private $field;
    private $weight;

    public function __construct($string, $type = null, $field = null, $weight = 1.0)
    {
        $this->string = $string;
        $this->type = $type;
        $this->field = $field;
        $this->setWeight($weight);
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setField($field = 'global')
    {
        $this->field = $field;
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
        return call_user_func($callback, $this, []);
    }

    public function getContent()
    {
        return $this->string;
    }

    public function getValue(Search_Type_Factory_Interface $typeFactory)
    {
        $type = $this->type;

        return $typeFactory->$type($this->string);
    }

    public function getField()
    {
        return $this->field;
    }

    public function traverse($callback)
    {
        return call_user_func($callback, $callback, $this, []);
    }
}
