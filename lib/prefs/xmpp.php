<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_xmpp_list()
{
	return [
		'xmpp_feature' => [
			'name' => tra('XMPP client (ConverseJS)'),
			'description' => tra('Integration with Converse.js XMPP client.'),
			'type' => 'flag',
			'keywords' => 'xmpp converse conversejs chat',
			'help' => 'XMPP',
			'tags' => ['basic'],
			'default' => 'n',
			'extensions' => [
			],
		],
		'xmpp_server_host' => [
			'name' => tra('XMPP server domain'),
			'description' => tra('XMPP server domain'),
			'type' => 'text',
			'filter' => 'text',
			'hint' => tra('example.org'),
			'keywords' => 'xmpp converse conversejs chat',
			'size' => 40,
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_muc_component_domain' => [
			'name' => tra('XMPP MUC Domain'),
			'description' => tra('Required for auto-joining rooms'),
			'type' => 'text',
			'filter' => 'text',
			'hint' => tra('conference.example.org'),
			'keywords' => 'xmpp converse conversejs chat',
			'size' => 40,
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_server_http_bind' => [
			'name' => tra('XMPP http-bind URL'),
			'description' => tra('Full URL to the http-bind.'),
			'keywords' => 'xmpp converse conversejs chat',
			'type' => 'text',
			'size' => 40,
			'filter' => 'url',
			'hint' => tra('http://xmpp.example.com/http-bind/'),
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_openfire_use_token' => [
			'name' => tra('XMPP Openfire Token'),
			'default' => 'n',
			'description' => tra('Handle user authentication using tokens'),
			'keywords' => 'xmpp openfire token',
			'type' => 'flag',
			'tags' => ['basic'],
		],
		'xmpp_conversejs_debug' => [
			'name' => tra('ConverseJS Debug Mode'),
			'default' => 'n',
			'description' => tra('Enabled more logging, e.g. XML stanzas and error tracebacks to the JavaScript Console'),
			'keywords' => 'xmpp openfire chat',
			'type' => 'flag',
		],
		'xmpp_conversejs_init_json' => [
			'name' => tra('ConverseJS Extra Settings'),
			'description' => tra('JSON format object defining extra optional settings to initialize ConverseJS'),
			'type' => 'textarea',
			'filter' => 'text',
			'keywords' => 'xmpp openfire converse chat',
			'size' => 10,
			'default' => '',
		],
	];
}
