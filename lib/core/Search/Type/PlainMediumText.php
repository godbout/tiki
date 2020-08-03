<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Type_PlainMediumText implements Search_Type_Interface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function filter(array $filters)
    {
        $value = $this->value;

        foreach ($filters as $f) {
            $value = $f->filter($value);
        }

        return new self($value);
    }
}
