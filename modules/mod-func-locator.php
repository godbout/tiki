<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}


/**
 * @return array
 */
function module_locator_info()
{
    return [
        'name' => tra('Locator'),
        'description' => tra('Presents a map with the geolocated content within the page.'),
        'prefs' => [],
        'params' => [
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_locator($mod_reference, $module_params)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');

    if ($prefs['geo_enabled'] === 'y') {
        TikiLib::lib('header')->add_map();

        // assign the default map centre from the prefs as a data attribute for the map-container div
        $smarty->assign('center', TikiLib::lib('geo')->get_default_center());
    } else {
        $smarty->assign('module_error', tr('Preference "%0" is disabled', 'geo_enabled'));
    }
}
