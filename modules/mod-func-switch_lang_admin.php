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
function module_switch_lang_admin_info()
{
	return [
		'name' => tra('Switch Admin Language'),
		'description' => tra('Displays a language picker to change the language of admin site.'),
		'params' => [
			'mode' => [
				'name' => tra('Display mode'),
				'description' => tra('Changes how the list of languages is displayed. Possible values are droplist, flags and words. Defaults to droplist.'),
				'filter' => 'alpha',
			],
		],
	];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_switch_lang_admin($mod_reference, $module_params)
{
	global $prefs, $user;

	if (empty($user)) {
		return false;
	}

	$userlib = TikiLib::lib('user');
	if (! $userlib->user_has_permission($user, 'tiki_p_admin')) {
		return false;
	}

	$frontendLang = $prefs['language'];
	$userlib = TikiLib::lib('user');
	$languageAdmin = ! empty($prefs['language_admin']) ? $prefs['language_admin'] : $frontendLang;
	$userLang = $userlib->get_user_preference($user, 'language');
	$userAdminLang = $userlib->get_user_preference($user, 'language_admin');

	if (! empty($userAdminLang)) {
		$frontendLang = $userAdminLang;
	} elseif (! empty($languageAdmin)) {
		$frontendLang = $languageAdmin;
	} elseif (! empty($userLang)) {
		$frontendLang = $userLang;
	} else {
		$frontendLang = $prefs['site_language'];
	}

	$smarty = TikiLib::lib('smarty');
	$tikilib = TikiLib::lib('tiki');

	$languages = [];
	$langLib = TikiLib::lib('language');
	$languages = $langLib->list_languages(false, 'y');
	$mode = isset($module_params["mode"]) ? $module_params["mode"] : "droplist";
	$smarty->assign('mode', $mode);
	if ($mode == 'flags' || $mode == 'words' || $mode == 'abrv') {
		include('lang/flagmapping.php');
		global $pageRenderer;

		for ($i = 0, $icount_languages = count($languages); $i < $icount_languages; $i++) {
			if (isset($flagmapping[$languages[$i]['value']])) {
				$languages[$i]['flag'] = $flagmapping[$languages[$i]['value']][0];
			} else {
				$languages[$i]['flag'] = '';
			}
			if (isset($pageRenderer) && count($pageRenderer->trads) > 0) {
				$languages[$i]['class'] = ' unavailable';
				for ($t = 0, $tcount_pageR = count($pageRenderer->trads); $t < $tcount_pageR; $t++) {
					if ($pageRenderer->trads[$t]['lang'] == $languages[$i]['value']) {
						$languages[$i]['class'] = ' available';
					}
				}
			} else {
				$languages[$i]['class'] = '';
			}
			if (isset($languageAdmin) && $languages[$i]['value'] == $languageAdmin) {
				$languages[$i]['class'] .= ' highlight';
			}
		}
	}
	$smarty->assign_by_ref('languages', $languages);
	$smarty->assign_by_ref('frontendLang', $frontendLang);
}
