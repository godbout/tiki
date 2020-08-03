<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 * @param mixed $array
 */


/**
 * Smarty reverse_array modifier plugin
 *
 * Type:     modifier<br>
 * Name:     reverse_array<br>
 * Purpose:  reverse arrays
 * @param array
 * @return array
 */
function smarty_modifier_reverse_array($array)
{
    return array_reverse($array);
}
