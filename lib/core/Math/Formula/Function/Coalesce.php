<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Coalesce extends Math_Formula_Function
{
    public function evaluate($element)
    {
        foreach ($element as $child) {
            $value = $this->evaluateChild($child);

            if (is_array($value)) {
                foreach ($value as $val) {
                    if (! empty($val)) {
                        return $val;
                    }
                }
            }

            if (! empty($value)) {
                return $value;
            }
        }

        return 0;
    }
}
