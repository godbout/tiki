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

/* params
 * - link_on_section
 * - css = use suckerfish menu
 * - type = vert|horiz
 * - id = menu ID (mandatory)
 * - translate = y|n , n means no option translation (default y)
 * - menu_cookie=y|n (default y) n, it will automatically open the submenu the url is in
 * - bs_menu_class='' custom class for the top level bootstrap menu element
 * - sectionLevel: displays from this level only
 * - toLevel : displays to this level only
 * - drilldown ??
 * - bootstrap : navbar|basic (equates to horiz or vert in old menus)
 * - setSelected=y|n (default=y) processes all menu items to show currently selected item, also sets open states, sectionLevel, toLevel etc
 * 								so menu_cookie, sectionLevel and toLevel will be ignored if this is set to n
 */
function smarty_function_menu($params, $smarty)
{
    global $prefs;

    $default = ['css' => 'y'];
    if (isset($params['params'])) {
        $params = array_merge($params, $params['params']);
        unset($params['params']);
    }
    $params = array_merge($default, $params);
    extract($params, EXTR_SKIP);

    if (empty($link_on_section) || $link_on_section == 'y') {
        $smarty->assign('link_on_section', 'y');
    } else {
        $smarty->assign('link_on_section', 'n');
    }

    if (empty($translate)) {
        $translate = 'y';
    }
    $smarty->assignByRef('translate', $translate);

    if (empty($menu_cookie)) {
        $menu_cookie = 'y';
    }
    $smarty->assignByRef('menu_cookie', $menu_cookie);

    if (empty($bs_menu_class)) {
        $bs_menu_class = '';
    }
    $smarty->assignByRef('bs_menu_class', $bs_menu_class);

    list($menu_info, $channels) = get_menu_with_selections($params);
    $smarty->assign('menu_channels', $channels['data']);
    $smarty->assign('menu_info', $menu_info);

    $objectCategories = TikiLib::lib('categ')->get_current_object_categories();

    if ($objectCategories) {
        list($categGroups) = array_values(
            array_filter(
                array_map(
                    function ($categId) {
                        $categ = TikiLib::lib('categ')->get_category($categId);
                        $parent = TikiLib::lib('categ')->get_category($categ["parentId"]);
                        if (! $parent || $parent["id"] != 0 || ! $parent["tplGroupContainerId"]) {
                            return null;
                        }
                        $templatedgroupid = TikiLib::lib('attribute')->get_attribute(
                            "category",
                            $categId,
                            "tiki.category.templatedgroupid"
                        );
                        $tplGroup = TikiLib::lib('user')->get_groupId_info($templatedgroupid);
                        if (empty($tplGroup['groupName'])) {
                            return null;
                        }

                        return [$parent["tplGroupContainerId"] => $tplGroup['groupName']];
                    },
                    $objectCategories
                ),
                function ($group) {
                    return $group != null;
                }
            )
        );
    } else {
        $categGroups = [];
    }

    if (isset($params['bootstrap']) && $params['bootstrap'] !== 'n' && $prefs['javascript_enabled'] === 'y') {
        $structured = [];
        $activeSection = null;
        foreach ($channels['data'] as $element) {
            $attribute = TikiLib::lib('attribute')->get_attribute('menu', $element["optionId"], 'tiki.menu.templatedgroupid');
            if ($attribute && $catName = $categGroups[$attribute]) {
                $element["name"] = str_replace("--groupname--", $catName, $element["name"]);
                $element["url"] = str_replace("--groupname--", $catName, $element["name"]);
                $element["sefurl"] = str_replace("--groupname--", $catName, $element["sefurl"]);
                $element["canonic"] = str_replace("--groupname--", $catName, $element["canonic"]);
            } elseif ($attribute && ! $categGroups[$attribute]) {
                continue;
            }

            if ($element['type'] == 's') {
                $structured[] = $element;
                $structuredSize = count($structured);
                $activeSection = &$structured[$structuredSize - 1];

                $activeSection['children'] = [];
            } elseif ($element['type'] == 'o') {
                if ($activeSection) {
                    $element['parent'] = $activeSection;
                    $activeSection['children'][] = $element;
                } else {
                    $structured[] = $element;
                }
            } elseif ($element['type'] == '-') {
                if ($activeSection) {
                    $structured[] = $activeSection;
                }
                $activeSection = null;
            } else {
                $level = (int)$element['type'];
                if ($activeSection) {
                    //If the element is at a higher level than active section
                    if ($level < (int) $activeSection['type'] || $activeSection['type'] == 'o') {
                        $structured[] = $element;
                        $structuredSize = count($structured);
                        $activeSection = &$structured[$structuredSize - 1];

                        $activeSection['children'] = [];
                    } elseif ($level === (int) $activeSection['type'] || $activeSection['type'] === 'o') {
                        $element['parent'] = &$activeSection['parent'];
                        $activeSection['parent']['children'][] = $element;
                    } else {
                        $element['parent'] = &$activeSection;
                        $activeSection['children'][] = $element;
                        $activeSection = &$activeSection['children'][count($activeSection['children']) - 1];
                    }
                } else {
                    $structured[] = $element;
                }
            }
        }

        $smarty->assign('list', $structured);
        switch ($params['bootstrap']) {
            case 'navbar':
                return $smarty->fetch('bootstrap_menu_navbar.tpl');
            case 'y':
                if (isset($params['type']) && $params['type'] == "horiz") {
                    return $smarty->fetch('bootstrap_menu_navbar.tpl');
                }

                    return $smarty->fetch('bootstrap_menu.tpl');
                
            default:
                return $smarty->fetch('bootstrap_menu.tpl');
        }
    }
    if ($params['css'] !== 'n' && $prefs['feature_cssmenus'] == 'y') {
        static $idCssmenu = 0;
        if (! isset($css_id)) {//adding $css_id parameter to customize menu id and prevent automatic id renaming when a menu is removed
            $smarty->assign('idCssmenu', $idCssmenu++);
        } else {
            $smarty->assign('idCssmenu', $css_id);
        }

        if (empty($params['type'])) {
            $params['type'] = 'vert';
        }
        $smarty->assign('menu_type', $params['type']);

        if (! empty($drilldown) && $drilldown == 'y') {
            $smarty->assign('drilldownmenu', 'y');
        }

        $tpl = 'tiki-user_cssmenu.tpl';
    } else {
        $tpl = 'tiki-user_menu.tpl';
    }

    $data = $smarty->fetch($tpl);

    return MenuLib::clean_menu_html($data);
}

function compare_menu_options($a, $b)
{
    return strcmp(tra($a['name']), tra($b['name']));
}

function get_menu_with_selections($params)
{
    global $user, $prefs;
    $tikilib = TikiLib::lib('tiki');
    $menulib = TikiLib::lib('menu');
    $cachelib = TikiLib::lib('cache');
    $cacheName = isset($prefs['mylevel']) ? $prefs['mylevel'] : 0;
    $cacheName .= '_' . $prefs['language'] . '_' . md5(implode("\n", $tikilib->get_user_groups($user)));

    extract($params, EXTR_SKIP);

    if (isset($structureId)) {
        $cacheType = 'structure_' . $structureId . '_';
    } else {
        $cacheType = 'menu_' . $id . '_';
    }

    if ($cdata = $cachelib->getSerialized($cacheName, $cacheType)) {
        list($menu_info, $channels) = $cdata;
    } elseif (! empty($structureId)) {
        $structlib = TikiLib::lib('struct');

        if (! is_numeric($structureId)) {
            $structureId = $structlib->get_struct_ref_id($structureId);
        }

        $channels = $structlib->build_subtree_toc($structureId);
        $structure_info = $structlib->s_get_page_info($structureId);
        $channels = $structlib->to_menu($channels, $structure_info['pageName'], 0, 0, $params);
        $menu_info = ['type' => 'd', 'menuId' => $structureId, 'structure' => 'y'];
    } elseif (! empty($id)) {
        $menu_info = $menulib->get_menu($id);
        $channels = $menulib->list_menu_options($id, 0, -1, 'position_asc', '', '', isset($prefs['mylevel']) ? $prefs['mylevel'] : 0);
        $channels = $menulib->sort_menu_options($channels);
    } else {
        return '<span class="alert-warning">menu function: Menu or Structure ID not set</span>';
    }
    if (strpos($_SERVER['SCRIPT_NAME'], 'tiki-register') === false) {
        $cachelib->cacheItem($cacheName, serialize([$menu_info, $channels]), $cacheType);
    }
    if (! isset($setSelected) || $setSelected !== 'n') {
        $channels = $menulib->setSelected($channels, isset($sectionLevel) ? $sectionLevel : '', isset($toLevel) ? $toLevel : '', $params);
    }

    foreach ($channels['data'] as & $item) {
        if (! empty($menu_info['parse']) && $menu_info['parse'] === 'y') {
            $item['block'] = TikiLib::lib('parser')->contains_html_block($item['name']); // Only used for CSS menus
            $item['name'] = preg_replace('/(.*)\n$/', '$1', $item['name']);	// parser adds a newline to everything
        }
    }

    return [$menu_info, $channels];
}
