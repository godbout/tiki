<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
require_once 'lib/auth/tokens.php';

class Services_Xmpp_Controller
{
	function setUp()
	{
		Services_Exception_Disabled::check('xmpp_feature');
		Services_Exception_Disabled::check('auth_token_access');
	}

	function action_check_token($input)
	{
		$xmpplib = TikiLib::lib('xmpp');
		$query = $input->stored;

		$user = $input->offsetGet('user');
		$token = $input->offsetGet('token');

		if (empty($user) || empty($token)) {
			return ['valid' => false];
		}

		$valid = (bool) $xmpplib->check_token($user, $token);
		return ['valid' => $valid];
	}

	function action_get_user_info($input)
	{
		$xmpplib = TikiLib::lib('xmpp');
		$userlib = TikiLib::lib('user');

		$authHeader = '';
		$givenKey = null;
		$user = $input->offsetGet('user');

		// check if authorization is sent
		if (! empty($_SERVER['Authorization'])) {
			$authHeader = $_SERVER['Authorization'];
		} elseif (! empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
		} else {
			header("HTTP/1.0 403 Forbidden", true, 403);
			die(tr("Empty authorization"));
		}

		// check if authorization looks like we expect
		$match = null;
		if (preg_match('/^Bearer  *([a-zA-Z0-9]{32})$/', $authHeader, $match)) {
			$givenKey = $match[1];
		} else {
			header("HTTP/1.0 403 Forbidden", true, 403);
			die(tr("Wrong authorization format"));
		}

		if (! $userlib->user_exists($user)) {
			header("HTTP/1.0 404 Not Found", true, 404);
			die(tr('Invalid user'));
		}

		// TODO: Check with jonnybradley if this is a good idea (jonnyb thinks it's fine but is no expert ;)
		global $prefs;
		$tokenlib = AuthTokens::build($prefs);
		$tokens = $tokenlib->getTokens(['entry' => 'openfireaccesskey']);
		$key = ! empty($tokens) ? md5("{$user}{$tokens[0]['token']}") : null;

		$validity = $key !== null
			&& $givenKey !== null
			&& strtoupper($key) === strtoupper($givenKey);

		// final check, if givenKey is really valid
		if ($validity) {
			$details = $userlib->get_user_details($user);
			return isset($details['info']) ? $details['info'] : null;
		}

		header("HTTP/1.0 403 Forbidden", true, 403);
		die(tr('Invalid token'));
	}

	private function block_anonymous() {
		global $user;
		if ($user) {
			return false;
		}
		throw new Services_Exception(tr('Must be authenticated'), 403);
	}

	function action_prebind($input)
	{
		global $user;
		$this->block_anonymous();
		$xmpplib = TikiLib::lib('xmpp');

		try {
			$result = $xmpplib->prebind($user);
		} catch (Exception $e) {
			$code = $e->getCode() ?: 500;
			$msg = $e->getMessage();
			throw new Services_Exception($msg, $code);
		}

		return $result;
	}

	function action_groups_in_room($input)
	{
		global $tiki_p_admin;
		if ($tiki_p_admin != 'y') {
			throw new Services_Exception(tr("You don't have enough privileges"), 403);
		}

		$userlib = TikiLib::lib('user');
		$items = array();

		foreach( $userlib->list_all_groupIds() as $item) {
			if ($item['groupName'] !== 'Anonymous'){
				$items[] = array(
					'id' => $item['id'],
					'name' => $item['groupName']
				);
			}
		}

		return $items;
	}

	function action_users_in_room($input)
	{
		global $tiki_p_list_users, $tiki_p_admin;
		if ($tiki_p_list_users !== 'y' && $tiki_p_admin != 'y') {
			throw new Services_Exception(tr("You don't have enough privileges"), 403);
		}

		$xmpplib = TikiLib::lib('xmpp');
		$userlib = TikiLib::lib('user');
		$return = array();

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$room = $input->room->text();
			$next = rawurldecode($input->next->text());

			$items = $input->item->text();
			$exist = array_map(array($userlib, 'user_exists'), $items);

			foreach($items as $index => $username) {
				$result = array(
					'name' => $username,
					'success' => false
				);

				if(!$exist[ $index ]) {
					$result['output'] = 'User does not exist';
				} else {
					$response = $xmpplib->addUserToRoom($username, $room);
					$result['success'] = $response['response'] === true;
					$result['output'] = $response['output'];
				}

				$return[] = $result;
				if ( $next ) header("Location: $next");
			}
		} else {
			foreach( $userlib->list_all_users() as $id => $name ) {
				$return[] = array(
					'id' => "$id",
					'name' => $name
				);
			}
		}
		return $return;
	}
}
