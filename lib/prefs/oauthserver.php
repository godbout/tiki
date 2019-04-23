<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_oauthserver_list()
{
	return [
		'oauthserver_encryption_key' => [
			'name' => tra('Encryption key for OAuthServer'),
			'description' => tra('A key used to encrypt/decrypt authorization codes'),
			'type' => 'text',
			'default' => '',
		],
	];
}
