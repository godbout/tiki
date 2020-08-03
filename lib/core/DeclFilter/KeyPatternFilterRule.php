<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class DeclFilter_KeyPatternFilterRule extends DeclFilter_FilterRule
{
    private $rules;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    private function getMatchingPattern($key)
    {
        foreach ($this->rules as $pattern => $filter) {
            if (preg_match($pattern, $key)) {
                return $pattern;
            }
        }

        return false;
    }

    public function match($key)
    {
        return false !== $this->getMatchingPattern($key);
    }

    public function getFilter($key)
    {
        $pattern = $this->getMatchingPattern($key);

        return TikiFilter::get($this->rules[$pattern]);
    }
}
