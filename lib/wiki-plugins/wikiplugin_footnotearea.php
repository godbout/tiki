<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_footnotearea_info()
{
	return [
		'name' => tra('Footnote Area'),
		'documentation' => 'PluginFootnoteArea',
		'description' => tra('Create automatically numbered footnotes (together with PluginFootnote)'),
		'prefs' => ['wikiplugin_footnote'],
		'iconname' => 'superscript',
		'format' => 'html',
		'introduced' => 3,
		'params' => [
			'class' => [
				'required' => false,
				'name' => tra('Class'),
				'description' => tra('Filter footnotearea by footnote class'),
				'since' => '17.0',
				'default' => '',
				'filter' => 'alnum',
				'accepted' => tra('Valid CSS class'),
			],
		],
	];
}

function wikiplugin_footnotearea($data, $params, $offset, $context)
{
	$footnotes = &$context->footnotes;
	$selectedFootnotes = [];
	foreach ($footnotes as $key => $footnote) {
		if (! $footnote['displayed'] && (! isset($params['class']) || $footnote['class'] == $params['class'])) {
			$selectedFootnotes[$key] = $footnote;
			
			// This prevents multiple calls to FOOTNOTEAREA from displaying the same footnote more than once.
			// This could be made optional, probably by adding a parameter to FOOTNOTE.
			$footnotes[$key]['displayed'] = true;
		}
	}
	$html = genFootnoteArea($selectedFootnotes);

	return $html;
}

/**
 *
 * Generate footnote area HTML
 *
 * @param $list array the array to turn into HTML
 *
 * @return string
 */

function genFootnoteArea($list)
{
	$smarty = TikiLib::lib('smarty');
	$smarty->assign('footnotes', $list);

	return $smarty->fetch('templates/wiki-plugins/wikiplugin_footnotearea.tpl');
}
