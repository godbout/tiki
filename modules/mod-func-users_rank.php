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
function module_users_rank_info()
{
    return [
        'name' => tra('Most Active Users'),
        'description' => tra('Display the specified number of users and their score, starting from the one with the highest score.'),
        'prefs' => ['feature_score'],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_users_rank($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $users_rank = $tikilib->rank_users($mod_reference["rows"]);
    $smarty->assign('users_rank', $users_rank);
}
