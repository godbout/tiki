<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_cypht_info()
{
	return [
		'name' => tra('Cypht webmail'),
		'documentation' => 'PluginCypht',
		'description' => tra('Embed Cypht webmail and news reader.'),
		'prefs' => [ 'wikiplugin_cypht' ],
		'body' => tra('text'),
		'iconname' => 'envelope',
		'introduced' => '20.0',
		'format' => 'html',
		'tags' => [ 'basic' ],
		'params' => [
			'imap_name' => [
				'name' => tr('Mailbox name'),
				'description' => tr("UI display presentational purposes only."),
				'required' => false,
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
			'imap_server' => [
				'name' => tr('IMAP server'),
				'description' => tr("E.g. imap.your-domain.com"),
				'required' => false,
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
			'imap_port' => [
				'name' => tr('IMAP port'),
				'description' => tr("Default is 993."),
				'required' => false,
				'default' => '993',
				'filter' => 'text',
				'since' => '20.0',
			],
			'imap_tls' => [
				'name' => tra('IMAP use TLS'),
				'description' => tr('Use secure connection to IMAP server.'),
				'required' => false,
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '20.0',
				'options' => [
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'imap_username' => [
				'name' => tra('IMAP username'),
				'description' => tr('Account mailbox username.'),
				'required' => false,
				'filter' => 'text',
				'default' => '',
				'since' => '20.0',
			],
			'imap_password' => [
				'name' => tra('IMAP password'),
				'description' => tr('Account mailbox password.'),
				'required' => false,
				'filter' => 'text',
				'default' => '',
				'since' => '20.0',
			],
			'smtp_name' => [
				'name' => tr('SMTP connection name'),
				'description' => tr("UI display presentational purposes only."),
				'required' => false,
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
			'smtp_server' => [
				'name' => tr('SMTP server'),
				'description' => tr("E.g. smtp.your-domain.com"),
				'required' => false,
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
			'smtp_port' => [
				'name' => tr('SMTP port'),
				'description' => tr("Default is 587."),
				'required' => false,
				'default' => '587',
				'filter' => 'text',
				'since' => '20.0',
			],
			'smtp_tls' => [
				'name' => tra('SMTP use TLS'),
				'description' => tr('Use secure TLS/SSL connection to SMTP server.'),
				'required' => false,
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '20.0',
				'options' => [
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'smtp_no_auth' => [
				'name' => tra('SMTP no authentication'),
				'description' => tr('Disable SMTP authentication if your server does not support it.'),
				'required' => false,
				'filter' => 'alpha',
				'default' => 'n',
				'since' => '20.0',
				'options' => [
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
			'groupmail' => [
				'name' => tra('Groupmail use'),
				'description' => tr('Share this mailbox for Groupmail usage or keep it private.'),
				'required' => false,
				'filter' => 'alpha',
				'default' => 'n',
				'since' => '20.0',
				'options' => [
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				],
			],
		],
	];
}

function wikiplugin_cypht($data, $params)
{
	global $tikipath, $user;

	static $called = false;
	if( $called ) {
		return tr("Only one cypht plugin per page can be used.");
	}
	$called = true;

	$headerlib = TikiLib::lib('header');

	// TODO: move these hardcoded values to plugin params or module params like it was in mod-webmail_inbox module
	$_SESSION['cypht']['trackerId'] = 48;
	$_SESSION['cypht']['fromFId'] = 348;
	$_SESSION['cypht']['subjectFId'] = 350;
	$_SESSION['cypht']['messageFId'] = 351;
	$_SESSION['cypht']['contentFId'] = 352;
	$_SESSION['cypht']['accountFId'] = 353;
	$_SESSION['cypht']['datetimeFId'] = 354;
	$_SESSION['cypht']['operatorFId'] = 349;

	define('APP_PATH', $tikipath.'/vendor_bundled/vendor/jason-munro/cypht/');
	define('DEBUG_MODE', false);

	// TODO: make these dynamic
	define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D');
	define('SITE_ID', 'Tiki-Integration');

	/* get includes */
	require_once APP_PATH.'lib/framework.php';
	require_once $tikipath.'/cypht/integration/classes.php';

	/* get configuration */
	$config = new Hm_Site_Config_File(APP_PATH.'hm3.rc');

	// override
	$config->set('session_type', 'custom');
	$config->set('session_class', 'Tiki_Hm_Custom_Session');
	$config->set('auth_type', 'custom');
	$config->set('output_class', 'Tiki_Hm_Output_HTTP');
	$config->set('cookie_path', ini_get('session.cookie_path'));
	$output_modules = $config->get('output_modules');
	foreach( $output_modules as $page => $_ ) {
		unset($output_modules[$page]['header_start']);
		unset($output_modules[$page]['header_content']);
		unset($output_modules[$page]['header_end']);
		unset($output_modules[$page]['content_start']);
		unset($output_modules[$page]['content_end']);
		if( isset($output_modules[$page]['header_css']) ) {
			unset($output_modules[$page]['header_css']);
			$headerlib->add_cssfile('cypht/site.css');
			if (!empty($_SESSION['cypht']['user_data']['theme_setting']) && $_SESSION['cypht']['user_data']['theme_setting'] != 'default') {
				$headerlib->add_cssfile('cypht/modules/themes/assets/'.$_SESSION['cypht']['user_data']['theme_setting'].'.css');
			}
		}
		if( isset($output_modules[$page]['page_js']) ) {
			unset($output_modules[$page]['page_js']);
			$headerlib->add_jsfile('cypht/jquery.touch.js', true);
			$headerlib->add_jsfile('cypht/site.js', true);
		}
	}
	$config->set('output_modules', $output_modules);

	if (empty($_SESSION['cypht']['request_key'])) {
		$_SESSION['cypht']['request_key'] = Hm_Crypt::unique_id();
	}
	$_SESSION['cypht']['username'] = $user;
	if(!empty($params['imap_server']) && !empty($params['imap_username']) && !empty($params['imap_password'])) {
		$_SESSION['cypht']['imap_auth_server_settings'] = array(
			'name' => empty($params['imap_name']) ? $params['imap_username'] : $params['imap_name'],
			'server' => $params['imap_server'],
			'port' => $params['imap_port'],
			'tls' => $params['imap_tls'] == 'y' ? '1' : '0',
			'username' => $params['imap_username'],
			'password' => $params['imap_password'],
			'no_caps' => false,
			'blacklisted_extensions' => array('enable')
		);
	} else {
		unset($_SESSION['cypht']['imap_auth_server_settings']);
	}

	if (!empty($params['smtp_server'])) {
		$attributes = array(
			'name' => $params['smtp_name'] ?? 'Default SMTP server',
			'default' => true,
			'server' => $params['smtp_server'],
			'port' => $params['smtp_port'],
			'tls' => $params['smtp_tls'] == 'y',
			'user' => $params['imap_username'],
			'pass' => $params['imap_password']
		);
		if ($params['smtp_no_auth'] == 'y') {
			$attributes['no_auth'] = true;
		}
		if (empty($_SESSION['cypht']['user_data']['smtp_servers'])) {
			$_SESSION['cypht']['user_data']['smtp_servers'] = [];
		}
		$found = false;
		foreach ($_SESSION['cypht']['user_data']['smtp_servers'] as $server) {
			if ($server['server'] == $attributes['server'] && $server['tls'] == $attributes['tls'] && $server['port'] == $attributes['port']) {
				$found = true;
				break;
			}
		}
		if (! $found) {
			$_SESSION['cypht']['user_data']['smtp_servers'][] = $attributes;
		}
	}

	$headerlib->add_css("
.inline-cypht * { box-sizing: content-box; }
.inline-cypht { position: relative; }
	");

	/* process the request */
	$dispatcher = new Hm_Dispatch($config);

	return '<div class="inline-cypht"><input type="hidden" id="hm_page_key" value="'.Hm_Request_Key::generate().'" />'
		. $dispatcher->output
		. "</div>";
}
