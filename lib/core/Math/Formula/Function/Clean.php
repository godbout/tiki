<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: Lower.php 64622 2017-11-18 19:34:07Z rjsmelo $

class Math_Formula_Function_Clean extends Math_Formula_Function
{
	function evaluate($element)
	{
        $out = "";

	    foreach ($element as $child) {
            $out .= TikiLib::remove_non_word_characters_and_accents($this->evaluateChild($child));
        }

        return $out;
	}

}
