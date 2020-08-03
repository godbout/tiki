<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Expr_MoreLikeThis implements Search_Expr_Interface
{
    private $type;
    private $object;
    private $field;
    private $weight;
    private $content;

    /**
     * If a single argument is provided, it will be assumed to be the direct content.
     * @param mixed $type
     * @param null|mixed $object
     */
    public function __construct($type, $object = null)
    {
        if (is_null($object)) {
            $this->content = $type;
        } else {
            $this->type = $type;
            $this->object = $object;
        }
    }

    public function setType($type)
    {
    }

    public function getType()
    {
        return 'plaintext';
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setField($field = 'contents')
    {
        $this->field = $field;
    }

    public function setWeight($weight)
    {
    }

    public function getWeight()
    {
        return 1;
    }

    public function walk($callback)
    {
        return call_user_func($callback, $this, []);
    }

    public function getValue(Search_Type_Factory_Interface $typeFactory)
    {
    }

    public function getField()
    {
        return $this->field;
    }

    public function traverse($callback)
    {
        return call_user_func($callback, $callback, $this, []);
    }

    public function getObjectType()
    {
        return $this->type;
    }

    public function getObjectId()
    {
        return $this->object;
    }
}
