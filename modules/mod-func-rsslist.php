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
 * @return array
 */
function module_rsslist_info()
{
    return [
        'name' => tra('News Feeds'),
        'description' => tra('List of feeds available on this site.'),
        'prefs' => [],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_rsslist($mod_reference, $module_params)
{
}
