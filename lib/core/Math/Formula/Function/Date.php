<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Date extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $elements = [];

        if (count($element) > 2) {
            $this->error(tr('Too many arguments on date.'));
        }

        foreach ($element as $child) {
            $elements[] = $this->evaluateChild($child);
        }

        $format = array_shift($elements);
        if (empty($format)) {
            $format = 'U';	// Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
        }
        $timestamp = (int)array_shift($elements);

        $tikilib = TikiLib::lib('tiki');
        $tz = $tikilib->get_display_timezone();
        $old_tz = date_default_timezone_get();
        if ($tz) {
            date_default_timezone_set($tz);
        }

        $date = null;
        if (empty($timestamp)) {
            $date = date($format);
        } else {
            $date = date($format, $timestamp);
        }

        date_default_timezone_set($old_tz);

        return $date;
    }
}
