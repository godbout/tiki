<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');

$access->check_feature('feature_blogs');

$bloglib = TikiLib::lib('blog');
if (! isset($_REQUEST['imgId'])) {
    die;
}
$info = $bloglib->get_post_image($_REQUEST['imgId']);
$type = & $info['filetype'];
$file = & $info['filename'];
$content = & $info['data'];
header("Content-type: $type");
header("Content-Disposition: inline; filename=$file");
echo $content;
