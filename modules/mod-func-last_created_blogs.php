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
function module_last_created_blogs_info()
{
    return [
        'name' => tra('Newest Blogs'),
        'description' => tra('Displays the specified number of blogs from newest to oldest.'),
        'prefs' => ["feature_blogs"],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_created_blogs($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $bloglib = TikiLib::lib('blog');
    $ranking = $bloglib->list_blogs(0, $mod_reference["rows"], 'created_desc', '', 'blog');

    $smarty->assign('modLastCreatedBlogs', $ranking["data"]);
}
