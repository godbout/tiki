<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_wikidiff_info()
{
	global $prefs;

	return [
		'name' => tra('Wiki Diff'),
		'documentation' => 'PluginWikidiff',
		'description' => tra('Display the differences between two wiki objects'),
		'prefs' => [ 'wikiplugin_wikidiff', 'feature_wiki' ],
		'iconname' => 'code-fork',
		'introduced' => 15.3,
		'format' => 'html',
		'extraparams' => true,
		'params' => [
			'object_id' => [
				'required' => true,
				'name' => tra('Object Id'),
				'description' => tra('Object to do a diff on (page name for wiki pages)'),
				'since' => 15.3,
				'default' => '',
				'filter' => 'text',
			],
			'object_type' => [
				'required' => false,
				'name' => tra('Object Type'),
				'description' => tra('Object type (wiki pages only)'),
				'since' => 15.3,
				'default' => 'wiki page',
				'filter' => 'text',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Wiki Page'), 'value' => 'wiki page'],
				]
			],
			'oldver' => [
				'required' => true,
				'name' => tra('Old version'),
				'description' => tra('Integer for old version number, or date') ,
				'since' => 15.3,
				'filter' => 'text',
				'default' => '',
			],
			'newver' => [
				'required' => false,
				'name' => tra('New version'),
				'description' => tra('Integer for old version number, or date') . ' - ' . tra('Leave empty for current version') ,
				'since' => 15.3,
				'filter' => 'text',
				'default' => '',
			],
			'diff_style' => [
				'required' => false,
				'name' => tra('Diff Style'),
				'description' => tr('Defaults to "diff style" preference if empty'),
				'since' => '15.3',
				'filter' => 'text',
				'default' => $prefs['default_wiki_diff_style'],
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('HTML diff'), 'value' => 'htmldiff'],
					['text' => tra('Side-by-side diff'), 'value' => 'sidediff'],
					['text' => tra('Side-by-side diff by characters'), 'value' => 'sidediff-char'],
					['text' => tra('Inline diff'), 'value' => 'inlinediff'],
					['text' => tra('Inline diff by characters'), 'value' => 'inlinediff-char'],
					['text' => tra('Full side-by-side diff'), 'value' => 'sidediff-full'],
					['text' => tra('Full side-by-side diff by characters'), 'value' => 'sidediff-full-char'],
					['text' => tra('Full inline diff'), 'value' => 'inlinediff-full'],
					['text' => tra('Full inline diff by characters'), 'value' => 'inlinediff-full-char'],
					['text' => tra('Unified diff'), 'value' => 'unidiff'],
					['text' => tra('Side-by-side view'), 'value' => 'sideview'],
				],
			],
			'show_version_info' => [
				'required' => false,
				'name' => tra('Show version info'),
				'description' => tra('Show the heading "Comparing version X with version Y"'),
				'since' => 15.3,
				'default' => 'n',
				'filter' => 'text',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('No'), 'value' => 'n'],
					['text' => tra('Yes'), 'value' => 'y'],
				]
			],
			'pagedenied_text' => [
				'required' => false,
				'name' => tr('Permission denied message'),
				'description' => tr('Text to show when the page exists but the user has insufficient permissions to see it.'),
				'since' => '18.0',
				'filter' => 'text',
				'default' => '',
			],
			'pagenotapproved_text' => [
				'required' => false,
				'name' => tr('No version approved error message'),
				'description' => tr('Text to show when the page exists but no version is approved.'),
				'since' => '18.0',
				'filter' => 'text',
				'default' => "You don't have permission to see this content, because it hasn't been approved",
			],
		]
	];
}

function wikiplugin_wikidiff($data, $params)
{
	global $prefs;
	// TODO refactor: defaults for plugins?
	$defaults = [];
	$plugininfo = wikiplugin_wikidiff_info();
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults["$key"] = $param['default'];
	}
	$params = array_merge($defaults, $params);

	$tikilib = TikiLib::lib('tiki');
	$perms = $tikilib->get_perm_object($params['object_id'], $params['object_type']);
	if ($perms['tiki_p_view'] != 'y') {
		$text = $params['pagedenied_text'];
		return($text);
	}

	if ($prefs['flaggedrev_approval'] == 'y') {
		$flaggedrevisionlib = TikiLib::lib('flaggedrevision');
		if ($flaggedrevisionlib->page_requires_approval($params['object_id'])) {
			if (! $flaggedrevisionlib->version_is_flagged($params['object_id'], $params['oldver'], 'moderation', 'OK')) {
				// If oldver (required) is not approved, display error message
				$text = $params['pagenotapproved_text'];
				return($text);
			}
			if (isset($params['newver']) && ! empty($params['newver'])) {
				// If specific version is specified, check if it has been approved
				if (! $flaggedrevisionlib->version_is_flagged($params['object_id'], $params['newver'], 'moderation', 'OK')) {
					return($params['pagenotapproved_text']);
				}
			} else {
				// No version provided, get latest approved version
				if ($version_info = $flaggedrevisionlib->get_version_with($params['object_id'], 'moderation', 'OK')) {
					$params['newver'] = (int)$version_info['version'];
					if ($params['newver'] < (int)$params['oldver']) {
						return($params['pagenotapproved_text']);
					}
				}
			}
		}
	}

	// Note: the underlying param is the opposite: hide_version_info
	$params['show_version_info'] = $params['show_version_info'] !== 'n';

	$smarty = TikiLib::lib('smarty');
	$smarty->loadPlugin('smarty_function_wikidiff');

	$ret = smarty_function_wikidiff($params, $smarty->getEmptyInternalTemplate());
	return $ret;
}
