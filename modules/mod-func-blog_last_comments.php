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
function module_blog_last_comments_info()
{
    return [
        'name' => tra('Newest Blog Post Comments'),
        'description' => tra('Displays the specified number of the blog post comments most recently added.'),
        'prefs' => ['feature_blogs'],
        'params' => [
            'nodate' => [
                'name' => tra('No date'),
                'description' => tra('If set to "y", the date of comments is not displayed in the module box.') . " " . tra('Default: "n".')
            ],
            'moretooltips' => [
                'name' => tra('Verbose tooltips'),
                'description' => tra('If set to "y", blog post title is only visible as a tooltip and not displayed.') . " " . tra('Default: "n"') . " " . tra('Options: "y,n"')
            ]
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_blog_last_comments($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $bloglib = TikiLib::lib('blog');
    $comments = $bloglib->list_blog_post_comments('y', $mod_reference["rows"]);

    $smarty->assign('comments', $comments['data']);
    $smarty->assign('moretooltips', isset($module_params['moretooltips']) ? $module_params['moretooltips'] : 'n');
    $smarty->assign('nodate', isset($module_params['nodate']) ? $module_params['nodate'] : 'n');
}
