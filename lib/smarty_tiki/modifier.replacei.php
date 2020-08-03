<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 * @param mixed $string
 * @param mixed $find
 * @param mixed $replacement
 */

/**
 * Smarty replacei modifier plugin
 *
 * Type:     modifier<br>
 * Name:     replacei<br>
 * Purpose:  Returns a case insensitive replaced string.
 *           Same arguments as PHP str_ireplace function.
 */
function smarty_modifier_replacei($string, $find, $replacement)
{
    return str_ireplace($find, $replacement, $string);
}
