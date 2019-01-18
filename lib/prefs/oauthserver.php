<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_oauthserver_list()
{
	return [
		'oauthserver_client_id' => [
			'name' => tra('The client ID'),
			'description' => tra('The client ID allowed to use this server'),
			'type' => 'text',
			'default' => '',
		],
		'oauthserver_client_secret' => [
			'name' => tra('The client secret'),
			'description' => tra('The client Secret allowed to use this server'),
			'type' => 'text',
			'default' => '',
		],
	];
}
