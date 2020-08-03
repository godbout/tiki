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
function module_breadcrumb_info()
{
    return [
        'name' => tra('Last-Visited Pages'),
        'description' => tra('Displays the last Wiki pages visited by the user.'),
        'prefs' => ['feature_wiki'],
        'documentation' => 'Module breadcrumb',
        'params' => [
            'maxlen' => [
                'name' => tra('Maximum length'),
                'description' => tra('Maximum number of characters in page names allowed before truncating.'),
                'filter' => 'int'
            ],
            'show_namespace' => [
                'name' => tra('Show Namespace'),
                'description' => tra('Show namespace prefix in page names.') . ' ( y / n )',	// Do not translate y/n
                'default' => 'y'
            ]
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_breadcrumb($mod_reference, $module_params)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');
    $categlib = TikiLib::lib('categ');
    if (! isset($_SESSION["breadCrumb"])) {
        $_SESSION["breadCrumb"] = [];
    }

    if ($jail = $categlib->get_jail()) {
        $objectlib = TikiLib::lib('object');
        $objectIds = $objectlib->get_object_ids("wiki page", $_SESSION["breadCrumb"]);

        $breadIds = [];
        foreach ($_SESSION["breadCrumb"] as $step) {
            if (isset($objectIds[$step])) {
                $breadIds[$objectIds[$step]] = $step;
            }
        }
        if ($breadIds) { // If we have visited pages and we're in a perspective
            $relevantIds = $categlib->filter_objects_categories(array_keys($breadIds), $jail);
        } else {
            $relevantIds = array_keys($breadIds);
        }

        $fullBreadCrumb = [];
        foreach ($breadIds as $breadId => $breadName) {
            if (in_array($breadId, $relevantIds)) {
                $fullBreadCrumb[$breadId] = $breadName;
            }
        }
    } else {
        $fullBreadCrumb = $_SESSION["breadCrumb"];
    }

    $bbreadCrumb = array_slice(array_reverse($fullBreadCrumb), 0, $mod_reference['rows']);
    $smarty->assign('breadCrumb', $bbreadCrumb);
    $smarty->assign('maxlen', isset($module_params["maxlen"]) ? $module_params["maxlen"] : 0);
    $smarty->assign('namespaceoption', isset($module_params['show_namespace']) ? $module_params['show_namespace'] : 'y');
}
