<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class DeclFilter_StaticKeyFilterRule extends DeclFilter_FilterRule
{
    private $rules;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    public function match($key)
    {
        return array_key_exists($key, $this->rules);
    }

    public function getFilter($key)
    {
        return TikiFilter::get($this->rules[$key]);
    }
}
