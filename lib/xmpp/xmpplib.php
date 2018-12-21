<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
require_once 'lib/auth/tokens.php';
require_once dirname(__FILE__) . '/TikiXmppChat.php';
require_once dirname(__FILE__) . '/TikiXmppPrebind.php';

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
	public function getServerHttpBind(): string
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

		if (empty($login['jid'])) {
			$login['jid'] = $user;
		}

		$info = array(
			'domain'    => $this->server_host,
			'http_bind' => $this->server_http_bind,
			'jid'       => JID::buildJid($login['jid'], $this->server_http_bind),
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
			return [];
		}

		$xmpp = $this->get_user_connection_info($user);
		$xmpp_prebind_class = XmppPrebind;

		$use_tikitoken = $xmpp['username'] === $user
			&& $xmpp['domain'] === $this->server_host
			&& !empty($prefs['xmpp_openfire_use_token'])
			&& $prefs['xmpp_openfire_use_token'] === 'y';

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
	 * Add css and js files and initialising js to the page
	 *
	 * @param array $params :
	 *        view__mode => overlayed | fullscreen | mobile | embedded
	 *
	 * @return string
	 * @throws Exception
	 */
	function addConverseJSToPage($params = [])
	{
		global $user, $prefs;

		static $instance = 0;
		$instance++;

		if ($instance > 1) {
			return '';
		}

		$tikilib = TikiLib::lib('tiki');
		$headerlib = TikiLib::lib('header');
		$xmpplib = TikiLib::lib('xmpp');

		$xmpp = $xmpplib->get_user_connection_info($user);

		$js = '';
		$cssjs = '';

		$params = array_merge([
			'view_mode' => 'overlayed',
			'room' => '',
		], $params);

		switch ($params['view_mode']) {
			case 'fullscreen':
				$css_files = ['inverse.css'];
				break;
			case 'embedded':
				// TODO: remove this a line after fixing conversejs
				$js .= 'delete sessionStorage["converse.chatboxes-' . $xmpp['jid'] . '"];';
				$js .= 'delete sessionStorage["converse.chatboxes-' . $xmpp['jid'] . '-controlbox"];';
				$css_files = ['converse.css', 'converse-muc-embedded.css'];
				break;
			case 'mobile':
				$css_files = ['converse.css', 'mobile.css'];
				break;
			case 'overlayed':
			default:
				$css_files = ['converse.css'];
		}

		foreach ($css_files as $css_file) {
			if (! empty($params['late_css'])) {
				$cssjs .= '$("<link rel=\"stylesheet\">").attr("href", "vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file . '").appendTo("head");';
			} else {
				$headerlib->add_cssfile('vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file);
			}
		}

		$options = [
			'authentication'   => 'prebind',
			'bosh_service_url' => $xmpp['http_bind'],
			'debug'            => $prefs['xmpp_conversejs_debug'] === 'y',
			'jid'              => $xmpp['jid'],
			'nickname'         => $xmpp['nickname'],
			'prebind_url'      => TikiLib::lib('service')->getUrl([
				'action' => 'prebind',
				'controller' => 'xmpp',
			]),
			'use_emojione'     => false,
			'view_mode'        => $params['view_mode'],
			'whitelisted_plugins' => ['tiki'],
		];

		if ($params['room']) {
			$options['auto_login'] = true;
			if (strpos($params['room'], '@') === false && ! empty($prefs['xmpp_muc_component_domain'])) {
				$params['room'] .= '@' . $prefs['xmpp_muc_component_domain'];
			}
			$options['auto_join_rooms'] = [$params['room']];
		}

		if (! empty($prefs['xmpp_conversejs_init_json'])) {
			$extraOptions = json_decode($prefs['xmpp_conversejs_init_json'], true);
			if ($extraOptions) {
				$options = array_merge($options, $extraOptions);
			} else {
				Feedback::warning(tr('Preference "xmpp_conversejs_init_json" does not contain valid JSON'));
			}
		}

		$optionString = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		$js .= '
(function () {
	var error = console && console.error
		? console.error.bind(console)
		: function(msg) { return feedback(msg, "error", false); };

	converse.plugins.add("tiki", {
		"initialize": function () {
			var _converse = this._converse;
			_converse.api.listen.on("noResumeableSession", function (xhr) {
				error(tr("XMPP Module error") + ": " + xhr.statusText);
				$("#conversejs").fadeOut("fast");
			});
		}
	});
	
	converse.initialize(' . $optionString . ');
})();
';
		$js .= $cssjs;

		$headerlib->add_jsfile('vendor_bundled/vendor/jcbrand/converse.js/dist/converse.js')
			->add_jq_onready($js);
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

	public function getRestApi() {
		if($this->restapi == null) {
			return $this->initializeRestApi();
		}
		return $this->restapi;
	}

	public function getXmppApi()
	{
		global $prefs;

		if(empty($this->xmppapi)) {
			$this->xmppapi = new TikiXmppChat(array(
				"scheme" => "tcp",
				"host" => $this->server_host,
				"port" => 5222,
				"user" => $prefs['xmpp_openfire_rest_api_username'],
				"pass" => $prefs['xmpp_openfire_rest_api_password'],
			));
			$this->xmppapi->connect();
		}
		return $this->xmppapi;
	}

	public function addUserToRoom($room, $name, $role='members')
	{
		global $prefs;
		// first, allow myself to join the room
		$owner = preg_replace(',/.*$,', '', $this->getXmppApi()->getJid());
		$this->getRestApi()->addUserRoleToChatRoom($room, $owner, 'owners');

		// the restapi add permission to user join the room
		$result = $this->getRestApi()->addUserRoleToChatRoom($room, $name, $role);

		// the xmppapi invite the user to the room
		$nick = $this->getXmppApi()->getUsername();

		$roomJid = JID::buildJid($room, $this->server_http_bind);
		$userJid = JID::buildJid($name, $prefs['xmpp_muc_component_domain']);

		$this->getXmppApi()
			->sendPresence(1, $roomJid, $nick)
			->sendInvitation($roomJid, $userJid)
		;

		return $result;
	}

	public function addUsersToRoom($params=array(), $defaultRoom='', $defaultRole='members')
	{
		$params = array_map(function($item) use ($defaultRoom, $defaultRole)
		{
			$status = is_array($item);
			$item = $status ? $item : array();

			$status = $status && !empty($item['name']);
			$status = !(empty($item['room']) && empty($defaultRoom));

			return array_merge(array(
				'role' => $defaultRole,
				'room' => $defaultRoom,
				'name' => '',
				'status' => $status
			),  $item);
		}, $params);

		$self = $this;
		return array_map(function($item) use ($self)
		{
			if (empty($item['status'])) {
				return $item;
			}
	
			$response = $self->addUserToRoom($item['room'], $item['name'], $item['role']);
			return array_merge($item, $response);
		}, $params);
	}
	
	function addGroupToRoom ($room, $name, $role='members')
	{
		$restapi = $this->getRestApi();
		return $restapi->addGroupRoleToChatRoom($room, $name, $role);
	}

	function addGroupsToRoom ($params=array(), $defaultRoom='', $defaultRole='members')
	{
		$params = array_map(function($item) use ($defaultRoom, $defaultRole)
		{
			$status = is_array($item);
			$item = $status ? $item : array();

			$status = $status && !empty($item['name']);
			$status = !(empty($item['room']) && empty($defaultRoom));

			return array_merge(array(
				'role' => $defaultRole,
				'room' => $defaultRoom,
				'name' => '',
				'status' => $status
			),  $item);
		}, $params);

		$self = $this;
		return array_map(function($item) use ($self)
		{
			if (empty($item['status'])) {
				return $item;
			}
	
			$response = $self->addGroupToRoom($item['room'], $item['name'], $item['role']);
			return array_merge($item, $response);
		}, $params);
	}

	public function getGroups()
	{
		$restapi = $this->getRestApi();
		$response = $restapi->getGroups();

		$items = [];
		if(!empty($response['data']) && !empty($response['data']->groups)) {
			$items = $response['data']->groups;
		}

		// groups has attr `name` and `description`
		// users  has attr `username` and `name`
		// let's make a common `name` and `fullname`
		return array_map(function($item) {
			return array(
				'name' => $item->name,
				'fullname' => $item->description
			);
		}, $items);
	}

	public function getUsers()
	{
		$restapi = $this->getRestApi();
		$response = $restapi->getUsers();

		$items = [];
		if(!empty($response['data']) && !empty($response['data']->users)) {
			$items = $response['data']->users;
		}

		// groups has attr `name` and `description`
		// users  has attr `username` and `name`
		// let's make a common `name` and `fullname`
		return array_map(function($item) {
			return array(
				'name' => $item->username,
				'fullname' => $item->name,
				'email' => $item->email
			);
		}, $items);
	}
}
