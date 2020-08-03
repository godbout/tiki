<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Lower extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $out = "";

        foreach ($element as $child) {
            $out .= strtolower($this->evaluateChild($child));
        }

        return $out;
    }
}
