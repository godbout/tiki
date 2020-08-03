<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 * \brief Smarty modifier plugin to replace strings
 *
 * - type:     modifier
 * - name:     stringfix
 * - purpose:  to return a "bugged" string which needs to be fixed
 *
 * Simple function to return a string with replaced string(s), e.g. to get correct translation strings for country names taken from flag filenames
 *
 * @author luciash
 * @param string to be replaced (optional)
 * @param replaced by string (optional)
 * @param mixed $string
 * @param mixed $what
 * @param mixed $by
 * @return corrected string
 *
 * Syntax: {$foo|stringfix[:"<fix_what>"][:"<fix_by>"]} (optional params in brackets)
 *
 * Example: {$country|stringfix:"_":" "}
 */
function smarty_modifier_stringfix($string, $what = '_', $by = ' ')
{
    return str_replace($what, $by, $string);
}
