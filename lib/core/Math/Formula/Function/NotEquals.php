<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_NotEquals extends Math_Formula_Function
{
    public function evaluate($element)
    {
        // Multiple components will all need to be equal.

        $out = [];

        $reference = $this->evaluateChild($element[0]);

        $count = 0;
        foreach ($element as $child) {
            $component = $this->evaluateChild($child);
            if ($component == $reference) {
                if (++$count > 1) {
                    return false;
                }
            }
        }

        return true;
    }
}
