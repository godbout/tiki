<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
    protected $name;
    protected $field;
    protected $renderCallback;
    protected $label;

    public function __construct($field)
    {
        $this->field = $field;
        $this->name = $field;
        $this->label = ucfirst($field);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function setRenderCallback($callback)
    {
        $this->renderCallback = $callback;

        return $this;
    }

    public function render($value)
    {
        if ($cb = $this->renderCallback) {
            return call_user_func($cb, $value);
        }

        return $value;
    }

    abstract public function getType();
}
