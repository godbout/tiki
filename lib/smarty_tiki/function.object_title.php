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

function smarty_function_object_title($params, $smarty)
{
    if (! isset($params['type'], $params['id']) && ! isset($params['identifier'])) {
        return tra('No object information provided.');
    }

    if (isset($params['type'], $params['id'])) {
        $type = $params['type'];
        $object = $params['id'];
        if (substr($type, -7) == 'comment') {
            $type = substr($type, 0, strlen($type) - 8);
            $info = TikiLib::lib('comments')->get_comment((int)$object);
            $object = $info['object'];
        }
    } else {
        list($type, $object) = explode(':', $params['identifier'], 2);
    }

    $smarty->loadPlugin('smarty_modifier_escape');

    return smarty_modifier_escape(TikiLib::lib('object')->get_title($type, $object));
}
