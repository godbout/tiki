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
function module_featured_links_info()
{
    return [
        'name' => tra('Featured Links'),
        'description' => tra('Displays the site\'s first featured links.'),
        'prefs' => ['feature_featuredLinks'],
        'documentation' => 'Module featured_links',
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_featured_links($mod_reference, $module_params)
{
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('featuredLinks', $tikilib->get_featured_links($mod_reference['rows']));
}
