<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function smarty_function_multilike($params, $smarty)
{
    global $prefs, $user;

    $relationlib = TikiLib::lib("relation");

    $multivalues = get_multivalues_from_pref($params['relation_prefix']);

    $item = [];
    foreach ($multivalues as $mv) {
        if ($mv['relation_prefix'] == $params['relation_prefix']) {
            $config = $mv;

            break;
        }
    }
    if (empty($config)) {
        return tr("Multivalue configuration not found");
    }
    $totalCount = 0;
    $totalPoints = 0;
    $buttons = [];
    foreach ($config['labels'] as $key => $label) {
        $button = [];
        $button['index'] = $key;
        $button['id'] = $config['ids'][$key];
        if (isset($config['values'])) {
            $button['value'] = $config['values'][$key];
        }
        $button['label'] = $label;
        $button['relation'] = $params['relation_prefix'] . "." . $button['id'];

        // check if there is an unselected icon else use default thumbs up open
        if (! empty($config['icon_unselected'][$key])) {
            $button['icon_unselected'] = $config['icon_unselected'][$key];
        } else {
            $button['icon_unselected'] = "thumbs-o-up";
        }

        // check if there is an selected icon else use default thumbs up
        if (! empty($config['icon_selected'][$key])) {
            $button['icon_selected'] = $config['icon_selected'][$key];
        } else {
            $button['icon_selected'] = "thumbs-up";
        }

        //get existing stats
        $button['count'] = $relationlib->get_relation_count($button['relation'], $params['type'], $params['object']);
        $totalCount += $button['count'];
        if ($button['value']) {
            $button['points'] = $button['count'] * $button['value'];
            $totalPoints += $button['points'];
        }

        // set whether already selected
        if ($relationlib->get_relation_id($button['relation'], "user", $user, $params['type'], $params['object'])) {
            $button['selected'] = 1;
            $smarty->assign('has_selection', 1);
        } else {
            $button['selected'] = 0;
        }
        $buttons[] = $button;
    }

    if (! empty($params['onlyShowTotalPoints'])) {
        return $totalPoints;
    }

    if (! empty($params['onlyShowTotalLikes'])) {
        return $totalCount;
    }

    if (! empty($params['showOptionTotals'])) {
        $smarty->assign("show_option_totals", true);
    }

    if (! empty($params['showInPopup']) && $params['showInPopup'] == 'y') {
        $smarty->assign("show_in_popup", true);
    }
    $smarty->assign("popup_placement", "left"); //default
    if (! empty($params['popupPlacement'])) {
        $smarty->assign("popup_placement", $params['popupPlacement']);
    }

    if (! empty($params['showPoints']) && strtolower($params['showPoints']) != 'n') {
        $smarty->assign("show_points", true);
    }

    if (! empty($params['showLikes']) && strtolower($params['showLikes']) == 'n') {
        $smarty->assign("show_likes", false);
    } else {
        $smarty->assign("show_likes", true);
    }

    if (! empty($params['choiceLabel'])) {
        $smarty->assign("choice_label", $params['choiceLabel']);
    } else {
        $smarty->assign("choice_label", "I found this:");
    }

    if (! empty($params['orientation']) && strtolower($params['orientation']) == 'vertical') {
        $smarty->assign("orientation", 'vertical');
    } else {
        $smarty->assign("orientation", 'horizontal');
    }

    $smarty->assign("buttons", $buttons);
    $smarty->assign("type", $params['type']);
    $smarty->assign("object", $params['object']);
    $smarty->assign("totalCount", $totalCount);
    $smarty->assign("totalPoints", $totalPoints);
    $smarty->assign("relation_prefix", $params['relation_prefix']);
    $smarty->assign("icon_unselected", $config['icon_unselected']);
    $smarty->assign("icon_selected", $config['icon_selected']);
    $smarty->assign("multilike_many", $config['allow_multi']);

    $smarty->assign("uses_values", isset($config['values']));

    $headerlib = TikiLib::lib('header');
    $headerlib->add_jsfile("lib/jquery_tiki/multilike.js");

    if (empty($params['template'])) {
        return $smarty->fetch('multilike.tpl');
    }

    return $smarty->fetch($params['template']);
}

/**
 * @param $mv
 * @return array
 */
function get_multivalues_from_pref()
{
    global $prefs;
    $data = (explode("\n\n", trim($prefs['user_multilike_config'])));
    $configurations = [];
    foreach ($data as $config) {
        preg_match_all("/(\S*)\s*=\s*(.*)/", $config, $temp_arr);
        $config = array_combine($temp_arr[1], $temp_arr[2]);
        if ($config['values']) {
            $config['values'] = array_map('trim', explode(',', $config['values']));
        }
        $config['labels'] = array_map('trim', explode(',', $config['labels']));
        $config['icon_unselected'] = array_map('trim', explode(',', $config['icon_unselected']));
        $config['icon_selected'] = array_map('trim', explode(',', $config['icon_selected']));
        foreach ($config['labels'] as &$label) {
            $label = tra($label);
        }
        unset($label);
        if (empty($config['ids'])) {
            return;
        }
        $config['ids'] = array_map('trim', explode(',', $config['ids']));
        $configurations[] = $config;
    }

    return $configurations;
}
