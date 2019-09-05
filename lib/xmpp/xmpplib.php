<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
require_once 'lib/auth/tokens.php';
require_once __DIR__ . '/ConverseJS.php';
require_once __DIR__ . '/TikiXmppChat.php';
require_once __DIR__ . '/TikiXmppPrebind.php';

use Fabiang\Xmpp\Protocol\Presence;
use Fabiang\Xmpp\Protocol\Message;
use Fabiang\Xmpp\Protocol\Invitation;
use Fabiang\Xmpp\Util\JID;

class XMPPLib extends TikiLib
{
	private $server_host = '';
	private $server_http_bind = '';
	private $restapi = null;
	private $xmppapi = null;

	/**
	 * @return string
	 */
	public function getServerHttpBind()
	{
		return $this->server_http_bind;
	}

	function __construct()
	{
		global $prefs;

		$this->server_host = $prefs['xmpp_server_host'];
		$this->server_http_bind = $prefs['xmpp_server_http_bind'];

		if (! class_exists('XmppPrebind')) {
			throw new Exception(
				"class 'XmppPrebind' does not exists."
				. " Install with `composer require candy-chat/xmpp-prebind-php:dev-master`.",
				1
			);
		}
	}

	function get_user_connection_info($user)
	{
		global $prefs;
		global $tikilib;

		$query = 'SELECT'
		. '     MAX(CASE WHEN `prefName`="xmpp_jid" THEN `value` END) AS `jid`,'
		. '     MAX(CASE WHEN `prefName`="xmpp_password" THEN `value` END) AS `password`,'
		. '     MAX(CASE WHEN `prefName`="xmpp_custom_server_http_bind" THEN `value` END) AS `http_bind`,'
		. '     MAX(CASE WHEN `prefName`="realName" THEN `value` END) AS `nickname`'
		. ' FROM `tiki_user_preferences` WHERE `user`=?'
		. '     AND `prefName` IN ("xmpp_jid", "xmpp_password", "xmpp_custom_server_http_bind", "realName")';

		$query = $this->query($query, [$user]);
		$login = $query->fetchRow();

		if (empty($login['jid']) && $user) {
			$login['jid'] = sprintf('%s@%s', $user, $prefs['xmpp_server_host']);
		}

		$info = array(
			'domain'    => $this->server_host,
			'http_bind' => $this->server_http_bind,
			'jid'       => JID::buildJid($login['jid'], $prefs['xmpp_server_host']),
			'password'  => $login['password'] ?: '',
			'username'  => $login['jid'],
			'nickname'  => $login['nickname'] ?: $user,
		);

		$jid_parts = JID::parseJid($login['jid']);
		if ($jid_parts) {
			$info['jid']       = $login['jid'];
			$info['username']  = $jid_parts['node'];
			$info['domain']    = $jid_parts['domain'];
			$info['http_bind'] = $login['http_bind'] ?: $this->server_http_bind;
		}

		return $info;
	}

	function check_token($givenUser, $givenToken)
	{
		global $prefs;

		$tokenlib = AuthTokens::build($prefs);
		$token = $tokenlib->getToken($givenToken);

		if (! $token || $token['entry'] !== 'openfireauthtoken') {
			return false;
		}
		// TODO: figure out how to delete token after n usages
		$tokenlib->deleteToken($token['tokenId']);

		$param = json_decode($token['parameters'], true);
		return is_array($param)
			&& ! empty($param['user'])
			&& $param['user'] === $givenUser;
	}

	function create_room_from_wikipage($args, $name, $priority)
	{
		global $prefs;
		global $user;

		$info = $this->get_user_connection_info($user);
		$userJid = $info['jid'];

		if (! is_array($args) || empty($args['data'])) {
			return;
		}

		preg_match('/\{xmpp\b[^}]*\}/i', $args['data'], $match)
			&& preg_match_all('/(?:(\w+)=(?:"([^"]*)"|([^\s]+)))/', $match[0], $match);

		if (empty($match)) {
			return;
		}

		$params = array();
		for ($i = 0; $i < count($match[1]); $i += 1) {
			$key = $match[ 1 ][ $i ];
			$val = $match[ 2 ][ $i ] ?: $match[ 3 ][ $i ];
			$params[ $key ] = $val;
		}

		if (empty($params['room'])) {
			return;
		}
		$room = $params['room'];

		$args = [
			'whois' => [
				'moderator',
				'participant',
				'visitor'
			],
		];

		$args['roomname'] = $room;
		$atpos = strpos($room, '@');

		if ($atpos) {
			$args['roomname'] = substr($room, 0, $atpos);
		} else {
			$room = $room . '@' . $prefs['xmpp_muc_component_domain'];
		}

		$args['roomdesc'] = $args['roomname'];
		if (! empty($params['roomdesc'])) {
			$args['roomdesc'] = $params['roomdesc'];
		}

		$args['maxusers'] = 30;
		if (! empty($params['maxUsers']) && is_numeric($params['maxUsers'])) {
			$params['maxUsers'] = (int)$params['maxUsers'];
		}

		if (! empty($params['can_anyone_discover_jid'])) {
			$args['whois'] = ($params['can_anyone_discover_jid'] === 'anyone');
		}

		$args['persistentroom'] = isset($params['persistent']) && $params['persistent'] === 'y';
		$args['moderatedroom'] = isset($params['moderated']) && $params['moderated'] === 'y';
		$args['enablelogging'] = isset($params['archiving']) && $params['archiving'] === 'y';
		$args['membersonly'] = ! empty($params['visibility']) && $params['visibility'] === 'members_only';
		$args['publicroom'] = ! isset($params['secret']) || $params['secret'] !== 'y';
		$args['roomadmins'] = [ $userJid ];

		if ($this->create_room($room, $args)) {
			if (! empty($params['groups'])) {
				$groups = explode(',', $params['groups']);

				foreach ($groups as $group) {
					$this->add_group_to_room($args['roomname'], $group, 'members');
				}
			}
		}
	}

	function create_room($room, $args)
	{
		if (empty($args) || empty($args['roomname'])) {
			return;
		}

		$xmppapi = $this->getXmppApi();
		$return = $xmppapi->createRoom(
			$xmppapi->getJid(),
			$room . '/' . $xmppapi->getUsername(),
			$args
		);
		return $return;
	}

	function sanitize_name($text)
	{
		global $tikilib;
		$result = $tikilib->take_away_accent($text);
		$result = preg_replace('*[ $&`:<>\[\]{}"+#%@/;=?^|~\',]+*', '-', $result);
		$result = strtolower($result);
		$result = trim($result);
		return $result;
	}

	function prebind($user)
	{
		global $prefs;
		global $tikilib;

		$session_id = substr($tikilib->sessionId, 0, 5);
		$browser_title = $prefs['browsertitle'];
		$browser_title = $this->sanitize_name($browser_title);
		$resource_name = "{$browser_title}-{$session_id}";

		$tokenlib = AuthTokens::build($prefs);

		if (empty($this->server_host) ||  empty($this->server_http_bind)) {
			header("HTTP/1.0 500 Internal Server Error");
			header('Content-Type: application/json');
			return ["msg" => "No XMPP server to bind."];
		}

		if ($user === null && $prefs['xmpp_openfire_allow_anonymous'] === 'y') {
			$user = $user ?: 'anonymous_' . $session_id;
		}

		$xmpp = $this->get_user_connection_info($user);
		$xmpp_prebind_class = XmppPrebind;

		$use_tikitoken = $xmpp['username'] === $user;
		$use_tikitoken = $use_tikitoken && $xmpp['domain'] === $this->server_host;
		$use_tikitoken = $use_tikitoken && ! empty($prefs['xmpp_auth_method']);
		$use_tikitoken = $use_tikitoken && $prefs['xmpp_auth_method'] === 'tikitoken';

		if ($use_tikitoken) {
			$token = $tokenlib->createToken(
				'openfireauthtoken',
				['user' => $user],	// parameters
				[], 				// groups
				[
					'timeout' => 300,
					'createUser' => 'n',
				]
			);
			$xmpp['password'] = "$token";
			$xmpp_prebind_class = TikiXmppPrebind;
		} else {
			if (empty($xmpp['password'])) {
				return [];
			}
		}

		$xmppPrebind = new $xmpp_prebind_class(
			$xmpp['domain'],
			$xmpp['http_bind'],
			$resource_name,
			false,
			false
		);

		$xmppPrebind->connect($xmpp['username'], $xmpp['password']);

		try {
			$xmppPrebind->auth();
			$result = $xmppPrebind->getSessionInfo();
		} catch (XmppPrebindException $e) {
			throw new Exception($e->getMessage(), 401);
		}

		return $result;
	}

	/**
	 * Add css and js files and initializes xmpp client page
	 *
	 * @param array $params :
	 *        view__mode => overlayed | fullscreen | mobile | embedded
	 *
	 * @return string
	 * @throws Exception
	 */
	function render_xmpp_client($params = [])
	{
		global $user, $prefs;

		static $instance = 0;
		$instance++;

		if ($instance > 1) {
			return '';
		}

		$xmpplib = TikiLib::lib('xmpp');
		$xmpp = $xmpplib->get_user_connection_info($user);

		$params = array_merge([
			'view_mode' => 'overlayed',
			'room' => '',
			'show_controlbox_by_default' => 'y',
			'show_occupants_by_default' => 'y',
		], $params);

		$xmppclient = new ConverseJS();
		$xmppclient->set_auth($params);
		$xmppclient->set_options(
			[
				'bosh_service_url'           => $xmpp['http_bind'],
				'jid'                        => $xmpp['jid'],
				'nickname'                   => $xmpp['nickname'] ?: 'visitor-' . time(),
				'view_mode'                  => $params['view_mode'],
				'show_controlbox_by_default' => $params['show_controlbox_by_default'] === 'y',
				'show_occupants_by_default'  => $params['show_occupants_by_default'] === 'y',
			]
		);
		$xmppclient->set_auto_join_rooms($params['room']);
		$xmppclient->render();
	}

	public function initializeRestApi()
	{
		global $prefs;
		$endpoint = $prefs['xmpp_openfire_rest_api'];
		$username = $prefs['xmpp_openfire_rest_api_username'];
		$password = $prefs['xmpp_openfire_rest_api_password'];

		$url = parse_url($endpoint);

		$ssl = $url['scheme'] === 'https';
		$host = $url['host'];
		$port = $url['port'] ?: ($ssl ? 9091 : 9090);
		$path = rtrim($url['path'], '/');

		$api = new Gidkom\OpenFireRestApi\OpenFireRestApi();
		$api->useBasicAuth = true;
		$api->basicUser = $username;
		$api->basicPwd = $password;
		$api->host = $host;
		$api->port = $port;
		$api->useSSL = $ssl;
		$api->plugin = $path;

		$this->restapi = $api;
		return $api;
	}

	public function getRestApi()
	{
		if ($this->restapi == null) {
			return $this->initializeRestApi();
		}
		return $this->restapi;
	}

	public function getXmppApi()
	{
		global $prefs;

		if (empty($this->xmppapi)) {
			$params = array(
				"scheme" => "tcp",
				"host" => $this->server_host,
				"port" => 5222,
				"user" => $prefs['xmpp_openfire_rest_api_username'],
				"pass" => $prefs['xmpp_openfire_rest_api_password'],
			);

			$this->xmppapi = new TikiXmppChat($params);
			$this->xmppapi->connect();
		}
		return $this->xmppapi;
	}

	public function addUserToRoom($room, $userJid, $role = 'members')
	{
		global $prefs;
		// first, allow myself to join the room
		$ownerJid = new JID($this->getXmppApi()->getJid());
		$onwerName = $ownerJid->getNode();

		$roomJid = new JID($room);
		$roomName = $roomJid->getNode();

		$result = $this->getRestApi()->addUserRoleToChatRoom($roomName, $onwerName, 'owners');
		$result = $this->getRestApi()->addUserRoleToChatRoom($roomName, $userJid, $role);

		$this->getXmppApi()
			->sendPresence(1, (string) $roomJid, $onwerName)
			->sendInvitation((string) $roomJid, $userJid)
		;

		return $result;
	}

	public function addUsersToRoom($params = array(), $defaultRoom = '', $defaultRole = 'members')
	{
		$params = array_map(function ($item) use ($defaultRoom, $defaultRole) {
			$status = is_array($item);
			$item = $status ? $item : array();

			$status = $status && ! empty($item['name']);
			$status = ! (empty($item['room']) && empty($defaultRoom));

			return array_merge(array(
				'role' => $defaultRole,
				'room' => $defaultRoom,
				'name' => '',
				'status' => $status
			), $item);
		}, $params);

		$self = $this;
		return array_map(function ($item) use ($self) {
			if (empty($item['status'])) {
				return $item;
			}

			$response = $self->addUserToRoom($item['room'], $item['jid'], $item['role']);
			return array_merge($item, $response);
		}, $params);
	}

	function add_group_to_room($room, $name, $role = 'members')
	{
		$restapi = $this->getRestApi();
		return $restapi->addGroupRoleToChatRoom($room, $name, $role);
	}

	function add_groups_to_room($params = array(), $defaultRoom = '', $defaultRole = 'members')
	{
		$params = array_map(function ($item) use ($defaultRoom, $defaultRole) {
			$status = is_array($item);
			$item = $status ? $item : array();

			$status = $status && ! empty($item['name']);
			$status = ! (empty($item['room']) && empty($defaultRoom));

			return array_merge(array(
				'role' => $defaultRole,
				'room' => $defaultRoom,
				'name' => '',
				'status' => $status
			), $item);
		}, $params);

		$self = $this;
		return array_map(function ($item) use ($self) {
			if (empty($item['status'])) {
				return $item;
			}

			$response = $self->add_group_to_room(item['room'], $item['name'], $item['role']);
			return array_merge($item, $response);
		}, $params);
	}

	public function get_groups()
	{
		$restapi = $this->getRestApi();
		$response = $restapi->getGroups();

		$items = [];
		if (! empty($response['data']) && ! empty($response['data']->groups)) {
			$items = $response['data']->groups;
		}

		// groups has attr `name` and `description`
		// users  has attr `username` and `name`
		// let's make a common `name` and `fullname`
		return array_map(function ($item) {
			return array(
				'name' => $item->name,
				'fullname' => $item->description
			);
		}, $items);
	}

	public function getUsers()
	{
		$cachelib = TikiLib::lib('cache');

		$cache_key = 'xmppJidList';
		if ($items = $cachelib->getSerialized($cache_key)) {
			return $items;
		}

		$query = 'SELECT'
		. ' user as username,'
		. ' MAX(CASE WHEN `prefName`="xmpp_jid" THEN `value` END) AS `jid`,'
		. ' MAX(CASE WHEN `prefName`="realName" THEN `value` END) AS `name`'
		. ' FROM `tiki_user_preferences` WHERE'
		. ' `prefName` IN ("xmpp_jid", "realName")'
		. ' GROUP BY user;';

		$items = $this->query($query);
		$items = array_map(function ($item) {
			return array(
				'name' => $item['username'],
				'fullname' => $item['name'],
				'jid' => $item['jid'] ?: "{$item['username']}@{$this->server_host}"
			);
		}, $items->result);

		$cachelib->cacheItem($cache_key, serialize($items));
		return $items;
	}
}
