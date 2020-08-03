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
function module_top_visited_blogs_info()
{
    return [
        'name' => tra('Top Visited Blogs'),
        'description' => tra('Display the specified number of blogs with links to them, from the most visited one to the least.'),
        'prefs' => ['feature_blogs'],
        'params' => [
            'showlastpost' => [
                'name' => tra('Show Last Post'),
                'description' => 'y|n',
                'required' => false,
                'filter' => 'alpha'
            ],
            'sort_mode' => [
                'name' => tra('Sort Mode'),
                'description' => tra('Sort Mode'),
                'required' => false,
                'filter' => 'word'
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_top_visited_blogs($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $bloglib = TikiLib::lib('blog');
    $with = '';
    if (isset($mod_reference['params']['showlastpost']) && $mod_reference['params']['showlastpost'] == 'y') {
        $with = ['showlastpost' => 'y'];
    }
    if (empty($mod_reference['sort_mode'])) {
        $mod_reference['sort_mode'] = 'hits_desc';
    }
    $ranking = $bloglib->list_blogs(0, $mod_reference['rows'], $mod_reference['sort_mode'], '', 'blog', $with);

    $smarty->assign('modTopVisitedBlogs', $ranking['data']);
}
