<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_preference_info()
{
	return [
		'name' => tra('Preference'),
		'documentation' => 'PluginPreference',
		'description' => tra('Allows to edit a permission by anyone that has permissions to see the current page'),
		'prefs' => ['wikiplugin_preference'],
		'extraparams' => true,
		'iconname' => 'settings',
		'validate' => 'all',
		'params' => [
			'name' => [
				'required' => true,
				'name' => tra('Name of the preference'),
				'description' => tra('Preferences to be edited(separated by ,).'),
				'filter' => 'striptags',
				'type' => 'list',
			],
			'currentpage' => [
				'required' => true,
				'name' => tra('Current Page'),
				'description' => tra('Page\'s name where this plugin will be shown.'),
			],
		],
	];
}

function wikiplugin_preference($data, $params)
{
	global $user;

	// Prevent anonymous users to use this plugin
	if (! $user or $user === 'anonymous') {
		return;
	}

	$wikilib = TikiLib::lib('wiki');
	$logslib = TikiLib::lib('logs');
	$prefslib = TikiLib::lib('prefs');

	$name = $params['name'];
	$currentpage = $params['currentpage'];

	if ($_GET['page'] != $currentpage || ! $name) {
		return;
	}

	$names = array_unique(explode(',', $name)); //prevent duplicated preferences
	$names = array_map(function ($prefName) {
		return trim($prefName);
	}, $names);

	$values = array_filter(array_combine(
		array_values($names),
		array_map(function ($name) use ($prefslib) {
			$prefDetail = $prefslib->getPreference($name);
			if (empty($_POST[$name]) && $prefDetail['type'] == 'flag') {
				return 'n';
			}
			return $_POST[$name];
		}, $names)
	), function ($val) {
		return isset($val)	;
	}); // prevent un matched preferences
	$lm_preference = array_filter($_POST['lm_preference'], function ($v) use ($values) {
		return array_key_exists($v, $values) || in_array($v, $_POST['lm_reset']);
	});

	if (isset($lm_preference)) { //edit
		$changes = $prefslib->applyChanges($lm_preference, array_merge($values, ['lm_reset' => $_POST['lm_reset']]));
		if (count($changes) > 0) {
			foreach ($changes as $key => $val) {
				if (isset($_POST['lm_reset']) && in_array($key, $_POST['lm_reset'])) {
					add_feedback($key, tr('%0 reset', $key), 4);
					$logslib->add_action('feature', $key, 'system', 'reset');
				} else {
					add_feedback($key, tr('%0 set', $key), 1, 1);
					$logslib->add_action('feature', $key, 'system', (is_array($val['old']) ? implode(',', $val['old']) : $val['old']) . '=>' . (is_array($val['new']) ? implode(',', $val['new']) : $val['new']));
				}
			}
		}
	}

	$url = $wikilib->sefurl($_GET['page']);

	$smarty = TikiLib::lib('smarty');
	$smarty->assign('names', $names);
	$smarty->assign('url', $url);
	$html = $smarty->fetch('wiki-plugins/wikiplugin_preference.tpl');
	$html = preg_replace('/(\v|\s)+/', ' ', $html);
	return '~np~' . html_entity_decode($html, ENT_HTML5, 'utf-8') . '~/np~';
}

if (! function_exists('add_feedback')) {
	function add_feedback($name, $message, $st, $num = null)
	{
		TikiLib::lib('prefs')->addRecent($name);

		Feedback::add(['num' => $num,
			'mes' => $message,
			'st' => $st,
			'name' => $name,
			'tpl' => 'pref',]);
	}
}
