<?php

class ConverseJS
{
	private $options;

	public function __construct($options = [])
	{
		$this->options = [];
		$this->set_options(array_merge(
			[
				'auto_reconnect' => true,
				'debug' => false,
				'show_controlbox_by_default' => true,
				'show_occupants_by_default' => true,
				'use_emojione' => false,
				'view_mode'	=> 'overlayed',
				'assets_path'	=> 'vendor_bundled/vendor/npm-asset/converse.js/dist/',
				'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
			],
			$options
		));

		$this->load_prefs();
	}

	public function load_prefs()
	{
		global $prefs;
		$this->set_option('debug', $prefs['xmpp_conversejs_debug'] === 'y');

		if (! empty($prefs['xmpp_conversejs_init_json'])) {
			$extraOptions = json_decode($prefs['xmpp_conversejs_init_json'], true);
			if ($extraOptions) {
				$this->set_options($extraOptions);
			}
		}

		if (! empty($prefs['xmpp_muc_component_domain'])) {
			$this->set_option('muc_domain', $prefs['xmpp_muc_component_domain']);
		}
	}

	public function get_oauth_parameters()
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

	public function set_auth($params)
	{
		global $user;
		$authMethod = TikiLib::lib('tiki')->get_preference('xmpp_auth_method');

		if (empty($user) && isset($params['anonymous']) && $params['anonymous'] === 'y') {
			$this->set_options(array(
				'authentication'   => 'anonymous',
				'auto_login'       => true,
			));
		} elseif ($authMethod === 'tikitoken') {
			$this->set_options(array(
				'auto_login' => true,
				'authentication'   => 'prebind',
				'prebind_url'      => TikiLib::lib('service')->getUrl([
					'action' => 'prebind',
					'controller' => 'xmpp',
				]),
			));
		} elseif ($authMethod === 'oauth') {
			$this->set_options(array(
				'authentication'   => 'login',
				'oauth_providers' => [
					'tiki' => $this->get_oauth_parameters(),
				]));
		} else {
			$this->set_options(array(
				'authentication' => 'login'
			));
		}
	}

	public function set_options($options)
	{
		foreach ($options as $name => $value) {
			$this->set_option($name, $value);
		}
	}

	public function get_options()
	{
		return $this->options ?: [];
	}

	public function set_option($name, $value)
	{
		$this->options[ $name ] = $value;
	}

	public function get_option($name, $fallback = null)
	{
		if (isset($this->options[$name])) {
			return $this->options[$name];
		}
		return $fallback;
	}

	public function set_auto_join_rooms($room)
	{
		if (! is_string($room) || empty($room)) {
			return;
		}

		$marker = strrpos($room, '@');
		$domain = $this->get_option('muc_domain');

		if (! $marker && $domain) {
			$room = $room . '@' . $domain;
		}

		$this->options['auto_join_rooms'] = [ $room ];
	}

	public function get_css_dependencies()
	{
		$deps = [
			'vendor_bundled/vendor/npm-asset/converse.js/dist/converse.min.css',
			'lib/xmpp/css/conversejs.css',
		];

		return $deps;
	}

	public function get_js_dependencies()
	{
		return [
			'vendor_bundled/vendor/npm-asset/converse.js/dist/converse.js',
			'lib/xmpp/js/conversejs-tiki.js',
			'lib/xmpp/js/conversejs-tiki-oauth.js',
		];
	}

	public function render()
	{
		global $user;
		$options = $this->get_options();

		array_map(
			function ($file) {
				printf('<link rel="stylesheet" href="%s">', $file);
			},
			$this->get_css_dependencies()
		);

		array_map([TikiLib::lib('header'), 'add_jsfile'], $this->get_js_dependencies());

		$output = '';

		if (empty($_SESSION['chat-session-init'])) {
			$_SESSION['chat-session-init'] = time();
		}
		$chat_session_init = ($user ?: 'anonymous') . $_SESSION['chat-session-init'];

		$output .= ';(function(){';
		$output .= "if (localStorage['chat-session-init'] == '{$chat_session_init}') { return; }";
		$output .= " localStorage['chat-session-init'] = '{$chat_session_init}';";
		$output .= ' for (var key in localStorage) { key.match(/converse/) && localStorage.removeItem(key); }';
		$output .= ' for (var key in sessionStorage) { key.match(/converse/) && localStorage.removeItem(key); }';
		$output .= '})();';

		if ($this->get_option('view_mode') === 'embedded') {
			// TODO: remove this a line after fixing conversejs
			$output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '"];' . PHP_EOL;
			$output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '-controlbox"];' . PHP_EOL;
			$options['singleton'] = true;
		}

		ksort($options);

		$optionString = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$output .= 'converse.initialize(' . $optionString . ');' . PHP_EOL;
		return TikiLib::lib('header')->add_jq_onready($output, 10);
	}
}
