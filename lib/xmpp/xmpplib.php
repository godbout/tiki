<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
require_once 'lib/auth/tokens.php';
require_once dirname(__FILE__) . '/TikiXmppPrebind.php';

class XMPPLib extends TikiLib
{
	private $server_host = '';
	private $server_http_bind = '';

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

	function get_user_password($user)
	{
		$query  = "SELECT value FROM `tiki_user_preferences` WHERE `user`=?";
		$query .= " AND `prefName`='xmpp_password';";

		$result = $this->query($query, [$user]);
		$ret = $result->fetchRow();

		if (count($ret) === 1) {
			return $ret['value'];
		} else {
			return '';
		}
	}

	function get_user_jid($user)
	{
		return sprintf('%s@%s', $user, $this->server_host);
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

		$xmpp_username = $user;

		if (! empty($prefs['xmpp_openfire_use_token']) && $prefs['xmpp_openfire_use_token'] === 'y') {
			$token = $tokenlib->createToken(
				'openfireauthtoken',
				['user' => $user],	// parameters
				[], 				// groups
				[
					'timeout' => 300,
					'createUser' => 'n',
				]
			);
			$xmpp_password = "$token";
		} else {
			$xmpp_password = $this->get_user_password($user);
			if (empty($xmpp_password)) {
				return [];
			}
		}

		$xmppPrebind = new TikiXmppPrebind(
			$this->server_host,
			$this->server_http_bind,
			$resource_name,
			false,
			false
		);

		$xmppPrebind->connect($xmpp_username, $xmpp_password);

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
			Feedback::error(tr('Only one instance of XMPP chat per page'));
			return '';
		}

		$headerlib = TikiLib::lib('header');

		$params = array_merge([
			'view_mode' => 'overlayed',
			'room' => '',
		], $params);

		switch ($params['view_mode']) {
			case 'fullscreen':
				$css_files = ['inverse.css'];
				break;
			case 'embedded':
				$css_files = ['converse.css', 'converse-muc-embedded.css'];
				break;
			case 'mobile':
				$css_files = ['converse.css', 'mobile.css'];
				break;
			case 'overlayed':
			default:
				$css_files = ['converse.css'];
		}

		$cssjs = '';
		foreach ($css_files as $css_file) {
			if (! empty($params['late_css'])) {
				$cssjs .= '$("<link rel=\"stylesheet\">").attr("href", "vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file . '").appendTo("head");';
			} else {
				$headerlib->add_cssfile('vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file);
			}
		}

		$xmpplib = TikiLib::lib('xmpp');

		$options = [
			'bosh_service_url' => $xmpplib->getServerHttpBind(),
			'jid' => $xmpplib->get_user_jid($user),
			'authentication' => 'prebind',
			'prebind_url' => TikiLib::lib('service')->getUrl([
				'controller' => 'xmpp',
				'action' => 'prebind',
			]),
			'whitelisted_plugins' => ['tiki'],
			'view_mode' => $params['view_mode'],
			'debug' => $prefs['xmpp_conversejs_debug'] === 'y',
			'use_emojione' => false
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

		$optionString = json_encode($options, JSON_UNESCAPED_SLASHES);

		$js = '
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
}
