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
require_once('tiki-setup.php');

$modlib = TikiLib::lib('mod');
$usermoduleslib = TikiLib::lib('usermodules');
$userlib = TikiLib::lib('user');
$smarty = TikiLib::lib('smarty');
$tikilib = TikiLib::lib('tiki');

include_once('tiki-module_controls.php');
global $prefs, $user;

clearstatcache();
$modules = $modlib->get_modules_for_user($user);

if (Perms::get()->admin) {
    $smarty->assign('module_pref_errors', $modlib->pref_errors);
}

$show_columns = array_fill_keys(array_keys($modules), 'n');

$modnames = [];
foreach ($modules as $zone => & $moduleList) {
    if ($prefs['feature_fullscreen'] != 'y' || empty($_SESSION['fullscreen']) || $_SESSION['fullscreen'] != 'y' ||
            strpos($zone, 'page') === 0) {	// pagetop and pagebottom zones appear in fullscreen
        foreach ($moduleList as & $mod_reference) {
            $show_columns[$zone] = 'y';

            $ref = (array) $mod_reference;
            $mod_reference['data'] = new Tiki_Render_Lazy(
                function () use ($ref) {
                    $modlib = TikiLib::lib('mod');

                    return $modlib->execute_module($ref);
                }
            );
            $modnames[$ref['name']] = '';
        }

        $smarty->assign($zone, $moduleList);
    }
}

//add necessary css files to header as required for specific modules
//TODO only add css when module will actually be showing
$cssadd = array_intersect_key($modlib->cssfiles, $modnames);
if (count($cssadd)) {
    $headerlib = TikiLib::lib('header');
    foreach ($cssadd as $add) {
        $headerlib->add_cssfile($add['csspath'], $add['rank']);
    }
}

$smarty->assign('show_columns', $show_columns);
$smarty->assign('module_zones', $modules);

$module_nodecorations = ['decorations' => 'n'];
$module_isflippable = ['flip' => 'y'];
$smarty->assign('module_nodecorations', $module_nodecorations);
$smarty->assign('module_isflippable', $module_isflippable);
