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
			'visibility' => [
				'required' => false,
				'name' => tra('Visibility'),
				'description' => tra('This room is visible to anyone or only for members'),
				'since' => 20,
				'filter' => 'alpha',
				'default' => 'anonymous',
				'options' => [
					['text' => tra('Members only'), 'value' => 'members_only'],
					['text' => tra('Anonymous'), 'value' => 'anonymous'],
				],
			],
			'can_anyone_discover_jid' => [
				'required' => false,
				'name' => tra('Show Real JIDs of Occupants to'),
				'description' => tra('If just moderator or anyone else can fetch information about an occupant.')
					. tra('If just "moderator", anonymous user will not be able to change their nicknames.'),
				'since' => 20,
				'filter' => 'alpha',
				'default' => 'anyone',
				'options' => [
					['text' => tra('Anyone'), 'value' => 'anyone'],
					['text' => tra('Moderator'), 'value' => 'moderator'],
				],
			],
			'show_controlbox_by_default' => [
				'required' => false,
				'name' => tra('Show controlbox on load'),
				'description' => tra('If controlbox should be shown after page load.')
					. ' ' . tra('This preference only works when view mode is overlayed'),
				'since' => 20,
				'filter' => 'alpha',
				'default' => 'n',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'show_occupants_by_default' => [
				'required' => false,
				'name' => tra('Show occupants'),
				'description' => tra('If occupants window should be visible by default')
					. ' ' . tra('This preference only works when view mode is embedded'),
				'since' => 20,
				'filter' => 'alpha',
				'default' => 'y',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'groups' => [
				'name' => tra('Groups (comma-separated)'),
				'description' => tra('Allowed groups to use this resource'),
				'default' => '',
				'filter' => 'alpha',
				'required' => false,
				'separator' => ',',
			],
			'secret' => [
				'name' => tra('Is secret?'),
				'description' => tra('If the room will be listed on public chat room list'),
				'default' => 'n',
				'filter' => 'y|n',
				'required' => false,
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'archiving' => [
				'name' => tra('Archiving'),
				'description' => tra('If room messages will be stored'),
				'default' => 'y',
				'filter' => 'y|n',
				'required' => false,
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'persistent' => [
				'name' => tra('Persistent'),
				'description' => tra('If room will continue to exist after last user leaves'),
				'default' => 'y',
				'filter' => 'y|n',
				'required' => false,
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'moderated' => [
				'name' => tra('Moderated'),
				'description' => tra('If room is moderated'),
				'default' => 'y',
				'filter' => 'y|n',
				'required' => false,
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
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
		. '<div id="conversejs"'
		. ' data-view-mode="' . $params['view_mode'] . '"'
		. ' style="' . "width:{$params['width']}; height:{$params['height']}" . '"'
		. '></div>';

	unset($params['width'], $params['height']);

	$openfire_api_enabled = ! empty($prefs['xmpp_openfire_rest_api']);
	$openfire_api_enabled = $openfire_api_enabled && ! empty($prefs['xmpp_openfire_rest_api_username']);
	$openfire_api_enabled = $openfire_api_enabled && ! empty($prefs['xmpp_openfire_rest_api_password']);
	$openfire_api_enabled = $openfire_api_enabled && ! empty($params['room']);
	$openfire_api_enabled = $openfire_api_enabled && $tiki_p_list_users === 'y';
	$openfire_api_enabled = $openfire_api_enabled && $tiki_p_admin === 'y';

	if ($openfire_api_enabled) {
		$url = $servicelib->getUrl(array('controller' => 'xmpp', 'action' => 'groups_in_room'));
		$item = '<a class="dropdown-item btn btn-link"'
			. ' data-xmpp="' . $params['room'] . '"'
			. ' data-xmpp-action="' . $url . '"'
			. '>' . tra('Add a group to room') . '</a>';
		$smarty->append('tiki_page_bar_more_items', $item);

		$url = $servicelib->getUrl(array('controller' => 'xmpp', 'action' => 'users_in_room'));
		$item = '<a class="dropdown-item btn btn-link"'
			. ' data-xmpp="' . $params['room'] . '"'
			. ' data-xmpp-action="' . $url . '"'
			. '>' . tra('Add users to room') . '</a>';
		$smarty->append('tiki_page_bar_more_items', $item);
		unset($url, $item);
	}

	if ($params['view_mode'] === 'fullscreen') {
		// supress to avoid conflict
		$headerlib->cssfiles = [];
		$headerlib->css = [];
	}
	$params['anonymous'] = $params['visibility'] === 'anonymous' ? 'y' : 'n';

	$javascript = 'lib/jquery_tiki/wikiplugin-xmpp.js';
	$headerlib->add_jsfile_late($javascript . '?_=' . filemtime(TIKI_PATH . "/$javascript"), false);

	TikiLib::lib('xmpp')->render_xmpp_client($params);

	$result .= $smarty->fetch('wiki-plugins/wikiplugin_xmpp.tpl');

	return $result;
}
