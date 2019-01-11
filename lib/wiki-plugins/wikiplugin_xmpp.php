<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

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
	global $prefs, $tiki_p_list_users, $tiki_p_admin;

	$headerlib = TikiLib::lib('header');
	$servicelib = TikiLib::lib('service');
	$smarty = TikiLib::lib('smarty');

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

	$result = '<style type="text/css">#page-bar .dropdown-menu { z-index: 1031; }</style>'
		.'<div id="conversejs"'
		. ' data-view-mode="'.$params['view_mode'].'"'
		. ' style="'."width:{$params['width']}; height:{$params['height']}".'"'
		. '></div>';
	
	unset($params['width'], $params['height']);

	$openfire_api_enabled = !empty($prefs['xmpp_openfire_rest_api']);
	$openfire_api_enabled = $openfire_api_enabled && !empty($prefs['xmpp_openfire_rest_api_username']);
	$openfire_api_enabled = $openfire_api_enabled && !empty($prefs['xmpp_openfire_rest_api_password']);
	$openfire_api_enabled = $openfire_api_enabled && !empty($params['room']);
	$openfire_api_enabled = $openfire_api_enabled && $tiki_p_list_users === 'y';
	$openfire_api_enabled = $openfire_api_enabled && $tiki_p_admin === 'y';

	if ($openfire_api_enabled) {
		$url = $servicelib->getUrl(array('controller' => 'xmpp', 'action' => 'groups_in_room'));
		$item = '<a class="dropdown-item btn btn-link"'
			. ' data-xmpp="'.$params['room'].'"'
			. ' data-xmpp-action="'.$url.'"'
			.'>' . tra('Add a groups to room') . '</a>';
		$smarty->append('tiki_page_bar_more_items', $item);

		$url = $servicelib->getUrl(array('controller' => 'xmpp', 'action' => 'users_in_room'));
		$item = '<a class="dropdown-item btn btn-link"'
			. ' data-xmpp="'.$params['room'].'"'
			. ' data-xmpp-action="'.$url.'"'
			.'>' . tra('Add users to room') . '</a>';
		$smarty->append('tiki_page_bar_more_items', $item);
		unset($url, $item);
	}

	if ($params['view_mode'] === 'fullscreen') {
		// supress to avoid conflict
		$headerlib->cssfiles = [];
		$headerlib->css = [];		
	}

	$javascript = 'lib/jquery_tiki/wikiplugin-xmpp.js';
	$headerlib->add_jsfile_late($javascript . '?_=' . filemtime(TIKI_PATH . "/$javascript"), false);
	TikiLib::lib('xmpp')->addConverseJSToPage($params);

	return $result;
}
