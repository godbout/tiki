<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

function prefs_intertiki_list()
{
	return [
		'intertiki_errfile' => [
			'name' => tra('Errors log file'),
			'size' => 42,
			'type' => 'text',
			'filter' => 'text',
			'description' => tra('location, from your tiki root dir, where you want the error log file stored'),
			'default' => 'temp/intertiki-error.log',
		],
		'intertiki_logfile' => [
			'name' => tra('Access log file'),
			'size' => 42,
			'type' => 'text',
			'description' => tra('location, from your tiki root dir, where you want the access log file stored.'),
			'filter' => 'text',
			'default' => 'temp/intertiki-access.log',
		],
	];
}
