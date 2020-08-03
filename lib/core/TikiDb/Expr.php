<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiDb_Expr
{
    private $string;
    private $arguments;

    public function __construct($string, array $arguments)
    {
        $this->string = $string;
        $this->arguments = $arguments;
    }

    public function getQueryPart($currentField)
    {
        return str_replace('$$', $currentField, $this->string);
    }

    public function getValues()
    {
        return $this->arguments;
    }
}
