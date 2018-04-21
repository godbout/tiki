<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: wikiplugin_jabber.php 64629 2017-11-19 12:06:52Z rjsmelo $

function wikiplugin_xmpp_info()
{
	return [
		'name' => tra('Xmpp'),
		'documentation' => 'PluginXmpp',
		'description' => tra('Chat using Xmpp'),
		'prefs' => [ 'wikiplugin_xmpp' ],
		'iconname' => 'comments',
		'introduced' => 19,
		'params' => [
			'room' => [
				'required' => true,
				'name' => tra('Room Name'),
				'description' => tr('Room to auto-join'),
				'since' => 19,
				'default' => '',
				'filter' => 'text',
			],
			'view_mode' => [
				'required' => false,
				'name' => tra('View Mode'),
				'description' => tra(''),
				'since' => 19,
				'default' => 'embedded',
				'filter' => 'word',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Embedded'), 'value' => 'embedded'],
					['text' => tra('Fullscreen'), 'value' => 'fullscreen'],
					['text' => tra('Mobile'), 'value' => 'mobile'],
					['text' => tra('Overlayed'), 'value' => 'overlayed'],
				],
			],
			'width' => [
				'required' => false,
				'name' => tra('Width'),
				'description' => tra('Chat room width in CSS units'),
				'since' => 19,
				'default' => '100%',
				'filter' => 'imgsize',
			],
			'height' => [
				'required' => false,
				'name' => tra('Height'),
				'description' => tra('Chat room height in CSS units'),
				'since' => 19,
				'default' => '400px',
				'filter' => 'imgsize',
			],
		],
	];
}

function wikiplugin_xmpp($data, $params)
{
	$defaults = [];
	$plugininfo = wikiplugin_xmpp_info();
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults["$key"] = $param['default'];
	}
	$params = array_merge($defaults, $params);

	if (empty($params['room'])) {
		Feedback::error(tr('PluginXMPP Error: No room specified'));
		return '';
	}
	$result = '<div id="conversejs" style="width:' . $params['width'] . ';height:' . $params['height'] . '"></div>';
	unset($params['width'], $params['height']);

	TikiLib::lib('xmpp')->addConverseJSToPage($params);

	return $result;
}
