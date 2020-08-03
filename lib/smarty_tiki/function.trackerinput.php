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

function smarty_function_trackerinput($params, $smarty)
{
    $trklib = TikiLib::lib('trk');

    $field = $params['field'];
    if (isset($params['item'])) {
        $item = $params['item'];
    } elseif (!empty($params['itemId'])) {
        $item = $trklib->get_item_info($params['itemId']);
    } else {
        $item = [];
    }

    $handler = $trklib->get_field_handler($field, $item);

    if ($handler) {
        $context = $params;
        unset($context['item']);
        unset($context['field']);

        $desc = '';
        if (isset($params['showDescription']) && $params['showDescription'] == 'y' && $params['field']['type'] != 'S') {
            $desc = $params['field']['description'];
            if ($params['field']['descriptionIsParsed'] == 'y') {
                $desc = TikiLib::lib('parser')->parse_data($desc);
            } else {
                $desc = htmlspecialchars($desc);
            }
            if (! empty($desc)) {
                $desc = '<div class="description form-text">' . $desc . '</div>';
            }
        }

        return $handler->renderInput($context) . $desc;
    }
}
