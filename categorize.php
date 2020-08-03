<?php
/**
 * @package tikiwiki
 * Update object categories
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

require_once('tiki-setup.php');

global $prefs;
if ($prefs['feature_categories'] == 'y' && Perms::get([ 'type' => $cat_type, 'object' => $cat_objid ])->modify_object_categories) {
    if (isset($_REQUEST['import']) and isset($_REQUEST['categories'])) {
        $_REQUEST["cat_categories"] = explode(',', $_REQUEST['categories']);
        $_REQUEST["cat_categorize"] = 'on';
    } elseif (! isset($_REQUEST["cat_categorize"]) || $_REQUEST["cat_categorize"] != 'on') {
        $_REQUEST['cat_categories'] = null;
    }
    $categlib = TikiLib::lib('categ');
    $categlib->update_object_categories(isset($_REQUEST['cat_categories']) ? $_REQUEST['cat_categories'] : [], $cat_objid, $cat_type, $cat_desc, $cat_name, $cat_href, $_REQUEST['cat_managed']);
}
