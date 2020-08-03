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
function module_user_image_galleries_info()
{
    return [
        'name' => tra('My Image Galleries'),
        'description' => tra('Displays to registered users their image galleries.'),
        'prefs' => ['feature_galleries'],
        'params' => [],
        'common_params' => ["nonums", "rows"]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_user_image_galleries($mod_reference, $module_params)
{
    global $user;
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    if ($user) {
        $ranking = $tikilib->get_user_galleries($user, $mod_reference["rows"]);

        $smarty->assign('modUserG', $ranking);
    }
    $smarty->assign('tpl_module_title', tra('My galleries'));
}
