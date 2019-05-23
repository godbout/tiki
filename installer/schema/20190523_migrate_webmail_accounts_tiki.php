<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * Migrate webmail accounts to cypht
 * @param $installer
 * @return bool
 * @throws Exception
 */
function upgrade_20190523_migrate_webmail_accounts_tiki($installer)
{
	$imap_servers = [];
	$pop3_servers = [];
	$smtp_servers = [];
	$users = [];
	$webmail_accounts = $installer->fetchAll("SELECT * FROM `tiki_user_mail_accounts`");
	foreach ($webmail_accounts as $account) {
		$user = $account['user'];
		if (! empty($account['imap'])) {
			$imap_servers[$user][] = [
				'name' => $account['account'],
				'server' => $account['imap'],
				'port' => $account['port'],
				'tls' => $account['useSSL'] == 'y' ? '1' : '0',
				'user' => $account['username'],
				'pass' => $account['pass']
			];
		} elseif (! empty($account['pop'])) {
			$pop3_servers[$user][] = [
				'name' => $account['account'],
				'server' => $account['pop'],
				'port' => $account['port'],
				'tls' => $account['useSSL'] == 'y' ? '1' : '0',
				'user' => $account['username'],
				'pass' => $account['pass']
			];
		}
		if (! empty($account['smtp'])) {
			$smtp_servers[$user][] = [
				'name' => $account['account'],
				'server' => $account['smtp'],
				'port' => $account['smtpPort'],
				'tls' => $account['useSSL'] == 'y' ? '1' : '0',
				'user' => $account['username'],
				'pass' => $account['pass']
			];
		}
		if (! in_array($user, $users)) {
			$users[] = $user;
		}
	}

	$tikilib = TikiLib::lib('tiki');
	$tikilib = new TikiLib;
	foreach ($users as $user) {
		$data = $tikilib->get_user_preference($user, 'cypht_user_config');
		if ($data) {
			$data = json_decode($data, true);
		} else {
			$data = [];
		}
		$data['imap_servers'] = $imap_servers[$user] ?? [];
		$data['pop3_servers'] = $pop3_servers[$user] ?? [];
		$data['smtp_servers'] = $smtp_servers[$user] ?? [];
		$data = json_encode($data);
		$tikilib->set_user_preference($user, 'cypht_user_config', $data);
	}

	$installer->query("DROP TABLE `tiki_user_mail_accounts`");
	$installer->query("DROP TABLE `tiki_webmail_messages`");

	return true;
}
