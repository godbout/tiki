<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
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
			'keywords' => 'xmpp jabber converse conversejs chat',
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
			'hint' => tra('https://xmpp.example.org/http-bind/'),
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_auth_method' => [
			'name' => tra('Authentication method'),
			'description' => tra('The authentication method to be used by XMPP client'),
			'keywords' => 'xmpp converse conversejs chat auth',
			'type' => 'list',
			'tags' => ['basic'],
			'default' => '',
			'options' => [
				'' => tra('Plain'),
				'oauth' => tra('OAuth (uses Tiki as provider)'),
				'tikitoken' => tra('Openfire TikiToken'),
			],
		],
		'xmpp_openfire_allow_anonymous' => [
			'name' => tra('Allow anonymous'),
			'description' => tra('Allow anonymous users on Chat'),
			'type' => 'flag',
			'keywords' => 'xmpp jabber anonymous conversejs chat',
			'help' => 'XMPP',
			'tags' => ['basic'],
			'default' => 'n',
			'extensions' => [
			],
		],
		'xmpp_openfire_rest_api' => [
			'name' => tra('Openfire REST API endpoint'),
			'description' => tra('Full URL to API endpoint'),
			'keywords' => 'xmpp openfire restapi rest api chat',
			'type' => 'text',
			'size' => 40,
			'filter' => 'url',
			'hint' => tra('https://xmpp.example.org:9091/plugins/restapi/v1/'),
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_openfire_rest_api_username' => [
			'name' => tra('Rest API username'),
			'description' => tra('Username to allow Openfire API usage'),
			'keywords' => 'xmpp openfire restapi rest api chat',
			'type' => 'text',
			'size' => 40,
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_openfire_rest_api_password' => [
			'name' => tra('Rest API password'),
			'description' => tra('Password to allow Openfire API usage'),
			'keywords' => 'xmpp openfire restapi rest api chat',
			'type' => 'password',
			'size' => 40,
			'tags' => ['basic'],
			'default' => '',
		],
		'xmpp_conversejs_debug' => [
			'name' => tra('ConverseJS Debug Mode'),
			'default' => 'n',
			'description' => tra('Enables more logging, e.g. XML stanzas and error tracebacks to the JavaScript Console'),
			'keywords' => 'xmpp jabber openfire chat',
			'type' => 'flag',
		],
		'xmpp_conversejs_init_json' => [
			'name' => tra('ConverseJS Extra Settings'),
			'description' => tra('JSON format object defining extra optional settings to initialize ConverseJS'),
			'type' => 'textarea',
			'filter' => 'text',
			'keywords' => 'xmpp jabber openfire converse chat',
			'size' => 10,
			'default' => '',
		],
	];
}
