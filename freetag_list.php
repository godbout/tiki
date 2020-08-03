<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}
//smarty is not there - we need setup
require_once('tiki-setup.php');

global $prefs;
global $tiki_p_view_freetags;

if ($prefs['feature_freetags'] == 'y' and $tiki_p_view_freetags == 'y') {
    $freetaglib = TikiLib::lib('freetag');

    if (isset($cat_objid)) {
        $tags = $freetaglib->get_tags_on_object($cat_objid, $cat_type);
        $tagarray = [];
        $taglist = '';
        if (! empty($tags['data'])) {
            foreach ($tags['data'] as $tag) {
                if (strstr($tag['tag'], ' ')) {
                    $taglist .= '"' . $tag['tag'] . '" ';
                } else {
                    $taglist .= $tag['tag'] . ' ';
                }
                $tagarray[] = $tag['tag'];
            }
        }

        $smarty->assign('taglist', $taglist);
    } else {
        $taglist = '';
    }

    if (! isset($cat_lang)) {
        $cat_lang = null;
    }

    $suggestion = $freetaglib->get_tag_suggestion($taglist, $prefs['freetags_browse_amount_tags_suggestion'], $cat_lang);

    $smarty->assign('tag_suggestion', $suggestion);
}
