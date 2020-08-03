<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'blogs';
require_once('tiki-setup.php');
$bloglib = TikiLib::lib('blog');
$access->check_feature('feature_blogs');

if (! isset($_REQUEST["postId"])) {
    $smarty->assign('msg', tra("No post indicated"));
    $smarty->display("error.tpl");
    die;
}

$postId = $_REQUEST["postId"];
$post_info = $bloglib->get_post($postId);

$blogId = $post_info["blogId"];
$tikilib->get_perm_object($postId, 'blog post');
$access->check_permission('tiki_p_read_blog', '', 'blog post', $postId);


$blog_data = $bloglib->get_blog($blogId);

if (! $blog_data) {
    $smarty->assign('msg', tra("Blog not found"));
    $smarty->display("error.tpl");
    die;
}

$parsed_data = TikiLib::lib('parser')->parse_data($post_info["data"], ['is_html' => true]);
$parsed_data = preg_replace('/\.\.\.page\.\.\./', '<hr />', $parsed_data);

$smarty->assign('blog_data', $blog_data);
$smarty->assign('blogId', $blogId);
$post_info['parsed_data'] = $parsed_data;
$smarty->assign('post_info', $post_info);
$smarty->assign('postId', $postId);

ask_ticket('print-blog-post');

// note: tiki-print_blog_post.tpl doesn't use the $mid system for page layout,
//       this is assigned here to trick canonical.tpl into generating a link tag
$smarty->assign('mid', 'tiki-view_blog_post.tpl');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->display("tiki-print_blog_post.tpl");
