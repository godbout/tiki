<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_If extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $out = [];

        $cond = $this->evaluateChild($element[0]);
        $then = $element[1];
        $else = $element[2] ?: 0;

        return $this->evaluateChild($cond ? $then : $else);
    }

    public function evaluateFull($element)
    {
        $out = [];

        $cond = $this->evaluateChild($element[0]);
        $then = $this->evaluateChild($element[1]);
        $else = $this->evaluateChild($element[2] ?: 0);

        return $cond ? $then : $else;
    }
}
