<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * \brief Smarty fn to contain generate a ui-predicate-vue component for tracker fields
 *
 * Usage:
 *
 * Examples:
 *
 */

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * @param $params     array  [ app = n|y, name = string ]
 * @param $content    string body of the Vue componenet
 * @param $smarty     Smarty
 * @param $repeat     boolean
 *
 * @return string
 * @throws Exception
 */

function smarty_function_trackerrules($params, $smarty)
{
	global $prefs;
	$headerlib = TikiLib::lib('header');

	if ($prefs['vuejs_enable'] === 'n') {
		Feedback::error(tr('Vue.js is not enabled.'));
		return '';
	}

	if ($prefs['vuejs_always_load'] === 'n') {
		$headerlib->add_jsfile_cdn("vendor_bundled/vendor/npm-asset/vue/dist/{$prefs['vuejs_build_mode']}");
	}

	$headerlib->add_jsfile('lib/vue/lib/ui-predicate-vue.js')
		// FIXME temporary workaround for chosen which seems to lose the event bindings
		->add_js('jqueryTiki.chosen = false; jqueryTiki.chosen_sortable = false;');
		// possible route towards a fix is here: https://stackoverflow.com/q/38716371/2459703

	return '<link rel="stylesheet" href="lib/vue/lib/ui-predicate-vue.css" type="text/css">' .
		TikiLib::lib('vuejs')->getFieldRules($params);
}
