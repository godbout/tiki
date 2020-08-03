<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_NumberFormat extends Math_Formula_Function
{
    public function evaluate($args)
    {
        $elements = [];

        if (count($args) < 1) {
            $this->error('Not enough arguments');
        }

        foreach ($args as $child) {
            $elements[] = $this->evaluateChild($child);
        }

        $value = $elements[0];

        if ((string)(float)$value !== (string)$value) {
            return $value;
        }
        if (count($elements) > 2) {
            return number_format((float)$value, $elements[1], $elements[2], $elements[3]);
        } elseif (count($elements) > 1) {
            return number_format((float)$value, $elements[1]);
        }

        return number_format((float)$value);
    }
}
