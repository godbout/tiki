<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Type_Timestamp implements Search_Type_Interface
{
    private $value;
    private $dateOnly;

    public function __construct($value, $dateOnly = false)
    {
        $this->value = $value;
        $this->dateOnly = $dateOnly;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isDateOnly()
    {
        return $this->dateOnly;
    }
}
