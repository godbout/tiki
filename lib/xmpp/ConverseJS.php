<?php

class ConverseJS
{
	private $options;

	public function __construct($options = [])
	{
		$this->options = [];
		$this->set_options(array_merge(
			[
				'debug' => false,
				'show_controlbox_by_default' => true,
				'use_emojione' => false,
				'view_mode'	=> 'overlayed',
				'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
			],
			$options
		));
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
		}
			$room = $room . '@' . $domain;

		$this->options['auto_join_rooms'] = [ $room ];
	}

	public function get_css_dependencies()
	{
		$deps = [
			'vendor_bundled/vendor/jcbrand/converse.js/css/converse.css',
		];

		if ($this->get_option('view_mode') === 'fullscreen') {
			$deps[] = 'vendor_bundled/vendor/jcbrand/converse.js/css/fullpage.css';
		}

		return $deps;
	}

	public function get_js_dependencies()
	{
		return [
			'vendor_bundled/vendor/jcbrand/converse.js/dist/converse.js',
			'lib/xmpp/js/conversejs-tiki.js',
			'lib/xmpp/js/conversejs-tiki-oauth.js',
		];
	}

	public function render()
	{
		$output = '';

		if ($this->get_option('view_mode') === 'embedded') {
			// TODO: remove this a line after fixing conversejs
			$output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '"];';
			$output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '-controlbox"];';
		}

		$optionString = json_encode($this->get_options(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		return 'converse.initialize(' . $optionString . ');';
	}
}
