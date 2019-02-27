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
		$use_tikitoken = $use_tikitoken && !empty($prefs['xmpp_auth_method']);
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

	public function getOAuthParameters()
	{
		$client_id = 'org.tiki.rtc.internal-conversejs-id';
		$oauthserverlib = TikiLib::lib('oauthserver');
		$accesslib = TikiLib::lib('access');

		$client = $oauthserverlib->getClient($client_id)
			?: $oauthserverlib->createClient([
				'client_id' => $client_id,
				'name' => 'ConverseJS OAuth Client',
				'redirect_uri' => $accesslib->absoluteUrl('lib/xmpp/html/redirect.html')
			]);

		return array(
			'client_id' => $client->getClientId(),
			'name' => $client->getName(),
			'authorize_url' => TikiLib::lib('service')->getUrl([
				'action' => 'authorize',
				'controller' => 'oauthserver',
				'response_type' => 'token'
			])
		);
	}


	public function getConverseAuthOptions()
	{
		$tikilib = TikiLib::lib('tiki');
		$authMethod = $tikilib->get_preference('xmpp_auth_method');

		if ($authMethod === 'tikitoken') {
			return array(
				'auto_login' => true,
				'authentication'   => 'prebind',
				'prebind_url'      => TikiLib::lib('service')->getUrl([
					'action' => 'prebind',
					'controller' => 'xmpp',
				]),
			);
		}

		if ($authMethod === 'oauth') {
			return array(
				'authentication'   => 'login',
				'oauth_providers' => [
					'tiki' => $this->getOAuthParameters(),
			]);
		}

		return array(
			'authentication' => 'login'
		);
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

		if (empty($user))
		{
			$session_timeout = 0;
			if (isset($_SESSION['conversejs-session-timeout']))
			{
				$session_timeout = intval($_SESSION['conversejs-session-timeout']);
			}

			if (time() > $session_timeout)
			{
				$js .= 'sessionStorage.clear();';
				$js .= 'localStorage.clear();';
			}

			$session_timeout = time() + (60 * 15);
			$_SESSION['conversejs-session-timeout'] = $session_timeout;
		}

		$params = array_merge([
			'view_mode' => 'overlayed',
			'room' => '',
		], $params);

		$css_files = ['converse.css'];

		switch ($params['view_mode']) {
			case 'fullscreen':
				$css_files[] = 'fullpage.css';
				break;

			case 'embedded':
				// TODO: remove this a line after fixing conversejs
				$js .= 'delete sessionStorage["converse.chatboxes-' . $xmpp['jid'] . '"];';
				$js .= 'delete sessionStorage["converse.chatboxes-' . $xmpp['jid'] . '-controlbox"];';
				break;
		}

		foreach ($css_files as $css_file) {
			if (! empty($params['late_css'])) {
				$cssjs .= '$("<link rel=\"stylesheet\">").attr("href", "vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file . '").appendTo("head");';
			} else {
				$headerlib->add_cssfile('vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file);
			}
		}

		$options = array_merge([
				'bosh_service_url' => $xmpp['http_bind'],
				'debug'            => $prefs['xmpp_conversejs_debug'] === 'y',
				'jid'              => $xmpp['jid'],
				'nickname'         => $xmpp['nickname'],
				'use_emojione'     => false,
				'view_mode'        => $params['view_mode'],
				'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
			],
			$this->getConverseAuthOptions()
		);

		if ($params['room']) {
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
			->add_jsfile('lib/xmpp/js/conversejs-tiki-oauth.js')
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

	public function addUserToRoom($room, $userJid, $role='members')
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
	
			$response = $self->addUserToRoom($item['room'], $item['jid'], $item['role']);
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
		$cachelib = TikiLib::lib('cache');		

		$cache_key = 'xmppJidList';
		if ($items = $cachelib->getSerialized($cache_key)) {
			return $items;
		}

		$query = 'SELECT'
		.     ' user as username,'
		.     ' MAX(CASE WHEN `prefName`="xmpp_jid" THEN `value` END) AS `jid`,'
		.     ' MAX(CASE WHEN `prefName`="realName" THEN `value` END) AS `name`'
		. ' FROM `tiki_user_preferences` WHERE'
		.     ' `prefName` IN ("xmpp_jid", "realName")'
		. ' GROUP BY user;';

		$items = $this->query($query);
		$items = array_map(function($item) {
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
