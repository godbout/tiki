<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_markdown_info() {
	return [
		'name' => tra('Markdown'),
		'documentation' => 'PluginMD',
		'description' => tra('Parse the body of the plugin using a Markdown parser.'),
		'prefs' => ['wikiplugin_md'],
		'body' => tra('Markdown syntax to be parsed'),
		'iconname' => 'code',
		'introduced' => 20,
		'filter' => 'rawhtml_unsafe',
		'format' => 'html',
		'tags' => [ 'advanced' ],
		'params' => [
			// TODO: add some useful params here
		],
	];
}

function wikiplugin_markdown($data, $params) {

	global $prefs;

	/*$defaults = [
		'wrap' => '1',
		'ishtml' => false
	];

	$params = array_merge($defaults, $params);*/

	extract($params, EXTR_SKIP);

	$md = trim($data);

	$md = str_replace('&lt;x&gt;', '', $md);
	$md = str_replace('<x>', '', $md);

// TODO: add the https://packagist.org/packages/league/commonmark parser to composer and make it actually do something here
}

