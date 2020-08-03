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
 * @param $installer
 */
function upgrade_20100507_flash_banner_tiki($installer)
{
    $result = $installer->query('select * from `tiki_banners` where `which` = ? and `HTMLData` like ?', ['useFlash', '%embedSWF%']);
    $query = 'update `tiki_banners` set `HTMLData`=? where `bannerId`=?';
    while ($res = $result->fetchRow()) {
        if (preg_match('/(swfobject|SWFFix)\.embedSWF\([\'" ]*([^,\'"]*)[\'" ]*,[\'" ]*([^,\'"]*)[\'" ]*,[\'" ]*([^,\'"]*)[\'" ]*,[\'" ]*([^,\'"]*)[\'" ]*,[\'" ]*([^,\'"]*)[\'" ]*,[\'" ]*([^,\'"]*)[\'" ]*/m', $res['HTMLData'], $matches)) {
            $movie['movie'] = $matches[2];
            $movie['width'] = $matches[4];
            $movie['height'] = $matches[5];
            $movie['version'] = $matches[6];
            $installer->query($query, [serialize($movie), $res['bannerId']]);
        }
    }
}
