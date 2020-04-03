<?php
/**
 * brings Smarty functionality into Tiki
 *
 * this script may only be included, it will die if called directly.
 *
 * @package TikiWiki
 * @subpackage lib\init
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */
// $Id$

// die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

require_once __DIR__ . '/../setup/third_party.php';

/**
 * extends Smarty_Security
 * @package TikiWiki\lib\init
 */
class Tiki_Security_Policy extends Smarty_Security
{
	/**
	 * needs a proper description
	 * @var array $secure_dir
	 */

	public $trusted_uri = [];

	public $secure_dir = [];

	/**
	 * needs a proper description
	 * @param Smarty $smarty
	 */
	public function __construct($smarty)
	{
		if (class_exists("TikiLib")) {
			$tikilib = TikiLib::lib('tiki');
			// modlib defines zone_is_empty which must exist before smarty initializes to fix bug with smarty autoloader after version 3.1.21
			TikiLib::lib('mod');
		}

		parent::__construct($smarty);


		//With phpunit and command line these don't exist yet for some reason
		if (isset($tikilib) && method_exists($tikilib, "get_preference")) {
			global $url_host;
			$this->trusted_uri[] = '#' . preg_quote("http://$url_host", '$#') . '#';
			$this->trusted_uri[] = '#' . preg_quote("https://$url_host", '$#') . '#';

			$functions = array_filter($tikilib->get_preference('smarty_security_functions', [], true));
			$modifiers = array_filter($tikilib->get_preference('smarty_security_modifiers', [], true));
			$dirs = array_filter($tikilib->get_preference('smarty_security_dirs', [], true));

			$cdns = preg_split('/\s+/', $tikilib->get_preference('tiki_cdn', ''));
			$cdns_ssl = preg_split('/\s+/', $tikilib->get_preference('tiki_cdn_ssl', ''));
			$cdn_uri = array_filter(array_merge($cdns, $cdns_ssl));
			foreach ($cdn_uri as $uri) {
				$this->trusted_uri[] = '#' . preg_quote($uri) . '$#';
			}
		} else {
			$functions = [];
			$modifiers = [];
			$dirs = [];
		}

		// Add defaults
		$this->php_modifiers = array_merge([
			'addslashes',
			'array_filter',
			'array_reverse',
			'count',
			'escape',
			'explode',
			'htmlentities',
			'implode',
			'is_array',
			'json_decode',
			'json_encode',
			'md5',
			'nl2br',
			'preg_split',
			'strip_tags',
			'stristr',
			'strpos',
			'substr',
			'tra',
			'trim',
			'ucfirst',
			'ucwords',
			'urlencode',
			'var_dump',
		], $modifiers);

		$this->php_functions = array_merge(['isset',
			'array',		// not needed? use {$value = []}
			'array_rand',
			'array_key_exists',
			'basename',
			'count',
			'empty',
			'ereg',			// deprecated and removed in php7+ use preg functions instead
			'in_array',
			'is_array',
			'is_numeric',
			'json_encode',
			'min',
			'max',
			'nl2br',
			'preg_match',
			'preg_match_all',
			'preg_replace',
			'sizeof',
			'strlen',
			'stristr',
			'strpos',
			'strstr',
			'str_replace',
			'strtolower',
			'time',
			'tra',
			'trim',
			'zone_is_empty',
		], $functions);

		$this->secure_dir = array_merge($this->secure_dir, $dirs);
	}
}

/**
 * extends Smarty.
 *
 * Centralizing overrides here will avoid problems when upgrading to newer versions of the Smarty library.
 * @package TikiWiki\lib\init
 */
class Smarty_Tiki extends Smarty
{
	/**
	 * needs a proper description
	 * @var array|null
	 */
	public $url_overriding_prefix_stack = null;
	/**
	 * needs a proper description
	 * @var null
	 */
	public $url_overriding_prefix = null;
	/**
	 * needs a proper description
	 * @var null|string
	 */
	public $main_template_dir = null;

	/**
	 * needs a proper description
	 */
	public function __construct()
	{
		parent::__construct();
		global $prefs;

		$this->initializePaths();

		$this->setConfigDir(null);
		if (! isset($prefs['smarty_compilation'])) {
			$prefs['smarty_compilation'] = '';
		}
		$this->compile_check = ( $prefs['smarty_compilation'] != 'never' );
		$this->force_compile = ( $prefs['smarty_compilation'] == 'always' );
		$this->assign('app_name', 'Tiki');

		if (! isset($prefs['smarty_security']) || $prefs['smarty_security'] == 'y') {
			$this->enableSecurity('Tiki_Security_Policy');
		} else {
			$this->disableSecurity();
		}
		$this->use_sub_dirs = false;
		$this->url_overriding_prefix_stack = [];
		if (! empty($prefs['smarty_notice_reporting']) and $prefs['smarty_notice_reporting'] === 'y') {
			$this->error_reporting = E_ALL;
		} else {
			$this->error_reporting = E_ALL ^ E_NOTICE;
		}
		if (! empty($prefs['smarty_cache_perms'])) {
			$this->_file_perms = (int) $prefs['smarty_cache_perms'];
		}

		$this->loadFilter('pre', 'tr');
		$this->loadFilter('pre', 'jq');

		include_once(__DIR__ . '/../smarty_tiki/resource.tplwiki.php');
		$this->registerResource('tplwiki', ['smarty_resource_tplwiki_source', 'smarty_resource_tplwiki_timestamp', 'smarty_resource_tplwiki_secure', 'smarty_resource_tplwiki_trusted']);

		include_once(__DIR__ . '/../smarty_tiki/resource.wiki.php');
		$this->registerResource('wiki', ['smarty_resource_wiki_source', 'smarty_resource_wiki_timestamp', 'smarty_resource_wiki_secure', 'smarty_resource_wiki_trusted']);

		global $prefs;
		// Assign the prefs array in smarty, by reference
		$this->assignByRef('prefs', $prefs);

		if (! empty($prefs['log_tpl']) && $prefs['log_tpl'] === 'y') {
			$this->loadFilter('pre', 'log_tpl');
		}
		if (! empty($prefs['feature_sefurl_filter']) && $prefs['feature_sefurl_filter'] === 'y') {
			require_once('tiki-sefurl.php');
			$this->registerFilter('output', 'filter_out_sefurl');
		}

		// restore tiki's own escape function
		$this->loadPlugin('smarty_modifier_escape');
		$this->registerPlugin('modifier', 'escape', 'smarty_modifier_escape');
	}

	/**
	 * Fetch templates from plugins (smarty plugins, wiki plugins, modules, ...) that may need to :
	 * - temporarily override some smarty vars,
	 * - prefix their self_link / button / query URL arguments
	 *
	 * @param      $_smarty_tpl_file
	 * @param null $override_vars
	 *
	 * @return string
	 */
	public function plugin_fetch($_smarty_tpl_file, &$override_vars = null)
	{
		$smarty_orig_values = [];
		if (is_array($override_vars)) {
			foreach ($override_vars as $k => $v) {
				$smarty_orig_values[ $k ] = $this->getTemplateVars($k);
				$this->assignByRef($k, $override_vars[ $k ]);
			}
		}

		$return = $this->fetch($_smarty_tpl_file);

		// Restore original values of smarty variables
		if (count($smarty_orig_values) > 0) {
			foreach ($smarty_orig_values as $k => $v) {
				$this->assignByRef($k, $smarty_orig_values[ $k ]);
			}
		}

		unset($smarty_orig_values);
		return $return;
	}

	/**
	 * needs a proper description
	 * @param null $_smarty_tpl_file
	 * @param null $_smarty_cache_id
	 * @param null $_smarty_compile_id
	 * @param null $parent
	 * @param bool $_smarty_display
	 * @param bool $merge_tpl_vars
	 * @param bool $no_output_filter
	 * @return string
	 */
	public function fetch($_smarty_tpl_file = null, $_smarty_cache_id = null, $_smarty_compile_id = null, $parent = null, $_smarty_display = false, $merge_tpl_vars = true, $no_output_filter = false)
	{
		if (strpos($_smarty_tpl_file, 'extends:') === 0) {
			// temporarily disable extends_recursion which restores smarty < 3.1.28 behaviour
			// see note at vendor_bundled/vendor/smarty/smarty/libs/Smarty.class.php:296 for more

			$this->extends_recursion = false;
		}
		$this->muteExpectedErrors();
		$this->refreshLanguage();

		$this->assign_layout_sections($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);

		$_smarty_tpl_file = $this->get_filename($_smarty_tpl_file);

		if ($_smarty_display) {
			$html = parent::display($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);
		} else {
			$html = parent::fetch($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);
		}

		if (! $this->extends_recursion) {
			$this->extends_recursion = true;
		}

		return $html;
	}

	/**
	 * Clears the value of an assigned variable
	 * @param $var mixed
	 * @return Smarty_Internal_Data
	 */
	public function clear_assign($var)
	{
		return parent::clearAssign($var);
	}

	/**
	 * This is used to assign() values to the templates by reference instead of making a copy.
	 * @param $var string
	 * @param $value mixed
	 * @return Smarty_Internal_Data
	 */
	public function assign_by_ref($var, &$value)
	{
		return parent::assignByRef($var, $value);
	}

	/**
	 * fetch in a specific language  without theme consideration
	 * @param      $lg
	 * @param      $_smarty_tpl_file
	 * @param null $_smarty_cache_id
	 * @param null $_smarty_compile_id
	 * @param bool $_smarty_display
	 * @return mixed
	 */
	public function fetchLang($lg, $_smarty_tpl_file, $_smarty_cache_id = null, $_smarty_compile_id = null)
	{
		global $prefs;

		$_smarty_tpl_file = $this->get_filename($_smarty_tpl_file);

		$lgSave = $prefs['language'];
		$prefs['language'] = $lg;
		$this->refreshLanguage();
		$res = parent::fetch($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id);
		$prefs['language'] = $lgSave; // Restore the language of the user triggering the notification
		$this->refreshLanguage();

		return preg_replace("/^[ \t]*/", '', $res);
	}

	/**
	 * needs a proper description
	 * @param null   $resource_name
	 * @param null   $cache_id
	 * @param null   $compile_id
	 * @param null   $parent
	 * @param string $content_type
	 * @return Purified|void
	 */
	public function display($resource_name = null, $cache_id = null, $compile_id = null, $parent = null, $content_type = 'text/html; charset=utf-8')
	{

		global $prefs;
		$this->muteExpectedErrors();
		if (! empty($prefs['feature_htmlpurifier_output']) and $prefs['feature_htmlpurifier_output'] == 'y') {
			static $loaded = false;
			static $purifier = null;
			if (! $loaded) {
				require_once('lib/htmlpurifier_tiki/HTMLPurifier.tiki.php');
				$config = getHTMLPurifierTikiConfig();
				$purifier = new HTMLPurifier($config);
				$loaded = true;
			}
		}

		/**
		 * Add security headers. By default there headers are not sent.
		 * To change go to admin > security > site access
		 */
		if (! headers_sent()) {
			if (! isset($prefs['http_header_frame_options'])) {
				$frame = false;
			} else {
				$frame = $prefs['http_header_frame_options'];
			}
			if (! isset($prefs['http_header_xss_protection'])) {
				$xss = false;  // prevent smarty E_NOTICE
			} else {
				$xss = $prefs['http_header_xss_protection'];
			}

			if (! isset($prefs['http_header_content_type_options'])) {
				$content_type_options = false;  // prevent smarty E_NOTICE
			} else {
				$content_type_options = $prefs['http_header_content_type_options'];
			}

			if (! isset($prefs['http_header_content_security_policy'])) {
				$content_security_policy = false;  // prevent smarty E_NOTICE
			} else {
				$content_security_policy = $prefs['http_header_content_security_policy'];
			}

			if (! isset($prefs['http_header_strict_transport_security'])) {
				$strict_transport_security = false;  // prevent smarty E_NOTICE
			} else {
				$strict_transport_security = $prefs['http_header_strict_transport_security'];
			}

			if (! isset($prefs['http_header_public_key_pins'])) {
				$public_key_pins = false;  // prevent smarty E_NOTICE
			} else {
				$public_key_pins = $prefs['http_header_public_key_pins'];
			}

			if ($frame == 'y') {
					$header_value = $prefs['http_header_frame_options_value'];
					header('X-Frame-Options: ' . $header_value);
			}
			if ($xss == 'y') {
					$header_value = $prefs['http_header_xss_protection_value'];
					header('X-XSS-Protection: ' . $header_value);
			}
			if ($content_type_options == 'y') {
				header('X-Content-Type-Options: nosniff');
			}
			if ($content_security_policy == 'y') {
				$header_value = $prefs['http_header_content_security_policy_value'];
				header('Content-Security-Policy: ' . $header_value);
			}

			if ($strict_transport_security == 'y') {
				$header_value = $prefs['http_header_strict_transport_security_value'];
				header('Strict-Transport-Security: ' . $header_value);
			}

			if ($public_key_pins == 'y') {
				$header_value = $prefs['http_header_public_key_pins_value'];
				header('Public-Key-Pins: ' . $header_value);
			}
		}

		/**
		 * By default, display is used with text/html content in UTF-8 encoding
		 * If you want to output other data from smarty,
		 * - either use fetch() / fetchLang()
		 * - or set $content_type to '' (empty string) or another content type.
		 */
		if ($content_type != '' && ! headers_sent()) {
			header('Content-Type: ' . $content_type);
		}

		if (function_exists('current_object') && $obj = current_object()) {
			$attributes = TikiLib::lib('attribute')->get_attributes($obj['type'], $obj['object']);
			if (isset($attributes['tiki.object.layout'])) {
				$prefs['site_layout'] = $attributes['tiki.object.layout'];
			}
		}

		$this->refreshLanguage();

		TikiLib::events()->trigger('tiki.process.render', []);

		$this->assign_layout_sections($resource_name, $cache_id, $compile_id, $parent);

		if (! empty($prefs['feature_htmlpurifier_output']) and $prefs['feature_htmlpurifier_output'] == 'y') {
			return $purifier->purify(parent::display($resource_name, $cache_id, $compile_id));
		} else {
			return parent::display($resource_name, $cache_id, $compile_id);
		}
	}

	/**
	 * Since Smarty 3.1.23, display no longer calls fetch function, so we need to have this Tiki layout section assignment
	 * and modules loading called in both places
	 */
	private function assign_layout_sections($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent)
	{
		global $prefs;

		if (($tpl = $this->getTemplateVars('mid')) && ( $_smarty_tpl_file == 'tiki.tpl' || $_smarty_tpl_file == 'tiki-print.tpl' || $_smarty_tpl_file == 'tiki_full.tpl' )) {
			// Set the last mid template to be used by AJAX to simulate a 'BACK' action
			if (isset($_SESSION['last_mid_template'])) {
				$this->assign('last_mid_template', $_SESSION['last_mid_template']);
				$this->assign('last_mid_php', $_SESSION['last_mid_php']);
			}
			$_SESSION['last_mid_template'] = $tpl;
			$_SESSION['last_mid_php'] = $_SERVER['REQUEST_URI'];

			// set the first part of the browser title for admin pages
			if (null === $this->getTemplateVars('headtitle')) {
				$script_name = basename($_SERVER['SCRIPT_NAME']);
				if ($script_name === 'route.php' && ! empty($inclusion)) {
					$script_name = $inclusion;
				}
				if ($script_name != 'tiki-admin.php' && strpos($script_name, 'tiki-admin') === 0) {
					$str = substr($script_name, 10, strpos($script_name, '.php') - 10);
					$str = ucwords(trim(str_replace('_', ' ', $str)));
					$this->assign('headtitle', 'Admin ' . $str);
				} elseif (strpos($script_name, 'tiki-list') === 0) {
					$str = substr($script_name, 9, strpos($script_name, '.php') - 9);
					$str = ucwords(trim(str_replace('_', ' ', $str)));
					$this->assign('headtitle', 'List ' . $str);
				} elseif (strpos($script_name, 'tiki-view') === 0) {
					$str = substr($script_name, 9, strpos($script_name, '.php') - 9);
					$str = ucwords(trim(str_replace('_', ' ', $str)));
					$this->assign('headtitle', 'View ' . $str);
				} elseif ($prefs['urlIndex'] && strpos($script_name, $prefs['urlIndex']) === 0) {
					$this->assign('headtitle', tra($prefs['urlIndexBrowserTitle']));	// Viewing Custom Homepage
				} else { // still not set? guess...
					$str = str_replace(['tiki-', '.php', '_'], ['', '', ' '], $script_name);
					$str = ucwords($str);
					$this->assign('headtitle', tra($str));	// for files where no title has been set or can be reliably calculated - translators: please add comments here as you find them
				}
			}

			if ($_smarty_tpl_file == 'tiki-print.tpl') {
				$this->assign('print_page', 'y');
			}
			$data = $this->fetch($tpl, $_smarty_cache_id, $_smarty_compile_id, $parent);//must get the mid because the modules can overwrite smarty variables

			$this->assign('mid_data', $data);
		} elseif ($_smarty_tpl_file == 'confirm.tpl' || $_smarty_tpl_file == 'error.tpl' || $_smarty_tpl_file == 'error_ticket.tpl' || $_smarty_tpl_file == 'error_simple.tpl') {
			if (! empty(ob_get_status())) {
				ob_end_clean(); // Empty existing Output Buffer that may have been created in smarty before the call of this confirm / error* template
			}
			if ($prefs['feature_obzip'] == 'y') {
				ob_start('ob_gzhandler');
			}
		}

		if (! defined('TIKI_IN_INSTALLER') && ! defined('TIKI_IN_TEST')) {
			require_once 'tiki-modules.php';
		}
	}

	/**
	 * Returns the file path associated to the template name
	 * Check if the path is a template inside one of the template dirs and not an arbitrary file
	 * @param $template
	 * @return string
	 */
	public function get_filename($template)
	{
		if (substr($template, 0, 5) === 'file:') {
			$template = substr($template, 5);
		}

		// could be extends: or something else?
		if (preg_match('/^[a-z]+\:/', $template)) {
			return $template;
		}

		//get the list of template directories
		$dirs = array_merge(
			$this->getTemplateDir(),
			['temp/cache'],
			$this->security_policy ? array_map('realpath', $this->security_policy->secure_dir) : []
		);

		// sanity check
		if (file_exists($template)) {
			$valid_path = false;
			foreach ($dirs as $dir) {
				$dirPath = realpath($dir);
				if ($dirPath === false) {
					continue;
				}

				if (strpos(realpath($template), $dirPath) === 0) {
					$valid_path = true;
					break;
				}
			}
			if (! $valid_path) {
				Feedback::error(tr("Invalid template name: %0", $template));
				return "";
			}
			return $template;
		}

		//go through directories in search of the template
		foreach ($dirs as $dir) {
			if (file_exists($dir . $template)) {
				return $dir . $template;
			}
		}
		return "";
	}

	/**
	 * needs a proper description
	 * @param $url_arguments_prefix
	 * @param $arguments_list
	 */
	public function set_request_overriders($url_arguments_prefix, $arguments_list)
	{
		$this->url_overriding_prefix_stack[] = [ $url_arguments_prefix . '-', $arguments_list ];
		$this->url_overriding_prefix =& $this->url_overriding_prefix_stack[ count($this->url_overriding_prefix_stack) - 1 ];
	}

	/**
	 * needs a proper description
	 * @param $url_arguments_prefix
	 * @param $arguments_list
	 */
	public function remove_request_overriders($url_arguments_prefix, $arguments_list)
	{
		$last_override_prefix = empty($this->url_overriding_prefix_stack) ? false : array_pop($this->url_overriding_prefix_stack);
		if (! is_array($last_override_prefix) || $url_arguments_prefix . '-' != $last_override_prefix[0]) {
			trigger_error('URL Overriding prefix stack is in a bad state', E_USER_ERROR);
		}
		$this->url_overriding_prefix =& $this->url_overriding_prefix_stack[ count($this->url_overriding_prefix_stack) - 1 ];
		;
	}

	public function refreshLanguage()
	{
		global $tikidomain, $prefs;

		$lang = $prefs['language'];
		if (empty($lang)) {
			$lang = 'default';
		}

		if (! empty($prefs['site_layout'])) {
			$layout = $prefs['site_layout'];
		} else {
			$layout = 'classic';
		}

		$this->setCompileId("$lang-$tikidomain-$layout");
		$this->initializePaths();
	}

	/*
	Add smarty template paths from where tpl files should be loaded. This function also gets called from lib/setup/theme.php to initialize theme specific paths
	*/
	public function initializePaths()
	{
		global $prefs, $tikidomainslash, $section;

		if (! $this->main_template_dir) {
			// First run only
			$this->main_template_dir = TIKI_PATH . '/templates/';
			$this->setCompileDir(TIKI_PATH . "/temp/templates_c");
			$this->setPluginsDir(
				[	// the directory order must be like this to overload a plugin
					TIKI_PATH . '/' . TIKI_SMARTY_DIR,
					SMARTY_DIR . 'plugins'
				]
			);
		}

		$this->setTemplateDir([]);

		// when called from release.php TikiLib isn't initialised so we can ignore the themes and addons
		if (class_exists('TikiLib')) {
			// Theme templates
			$themelib = TikiLib::lib('theme');
			if (! empty($prefs['theme']) && ! in_array($prefs['theme'], ['custom_url'])) {
				$theme_path = $themelib->get_theme_path($prefs['theme'], $prefs['theme_option'], '', 'templates'); // path to the theme options
				$this->addTemplateDir(TIKI_PATH . "/$theme_path/");
				//if theme_admin is empty, use main theme and site_layout instead of site_layout_admin
				if ($section != "admin" || empty($prefs['theme_admin'])) {
					$layout = TIKI_PATH . "/$theme_path/" . 'layouts/' . $prefs['site_layout'] . '/';
					if (! is_readable($layout)) {
						$layout = TIKI_PATH . "/$theme_path/" . 'layouts/basic/';
					}
					$this->addTemplateDir($layout);
				} else {
					$layout = TIKI_PATH . "/$theme_path/" . 'layouts/' . $prefs['site_layout_admin'] . '/';
					if (! is_readable($layout)) {
						$layout = TIKI_PATH . "/$theme_path/" . 'layouts/basic/';
					}
					$this->addTemplateDir($layout);
				}
				$this->addTemplateDir(TIKI_PATH . "/$theme_path/" . 'layouts/');

				$main_theme_path = $themelib->get_theme_path($prefs['theme'], '', '', 'templates'); // path to the main theme
				$this->addTemplateDir(TIKI_PATH . "/$main_theme_path/");
				//if theme_admin is empty, use main theme and site_layout instead of site_layout_admin
				if ($section != "admin" || empty($prefs['theme_admin'])) {
					$layout = TIKI_PATH . "/$main_theme_path/" . 'layouts/' . $prefs['site_layout'] . '/';
					if (! is_readable($layout)) {
						$layout = TIKI_PATH . "/$main_theme_path/" . 'layouts/basic/';
					}
					$this->addTemplateDir($layout);
				} else {
					$layout = TIKI_PATH . "/$main_theme_path/" . 'layouts/' . $prefs['site_layout_admin'] . '/';
					if (! is_readable($layout)) {
						$layout = TIKI_PATH . "/$main_theme_path/" . 'layouts/basic/';
					}
					$this->addTemplateDir($layout);
				}
			}
			// Tikidomain main template folder
			if (! empty($tikidomainslash)) {
				$this->addTemplateDir(TIKI_PATH . "/themes/{$tikidomainslash}templates/"); // This dir is for all the themes in the tikidomain
				$this->addTemplatedir($this->main_template_dir . '/' . $tikidomainslash); // legacy tpls just in case, for example: /templates/mydomain.ltd/
			}

			$this->addTemplateDir(TIKI_PATH . "/themes/templates/"); //This dir stores templates for all the themes

			//Addon templates
			foreach (\Tiki\Package\ExtensionManager::getPaths() as $path) {
				$this->addTemplateDir($path . '/templates/');
			}
		}

		//Layout templates
		if (! empty($prefs['site_layout']) && ($section != "admin" || empty($prefs['theme_admin']))) { //use the admin layout if in the admin section
			$layout = $this->main_template_dir . '/layouts/' . $prefs['site_layout'] . '/';
			if (! is_readable($layout)) {
				$layout = $this->main_template_dir . '/layouts/basic/';
			}
			$this->addTemplateDir($layout);
		} elseif (! empty($prefs['site_layout_admin'])) {
			$layout = $this->main_template_dir . '/layouts/' . $prefs['site_layout_admin'] . '/';
			if (! is_readable($layout)) {
				$layout = $this->main_template_dir . '/layouts/basic/';
			}
			$this->addTemplateDir($layout);
		}
		$this->addTemplateDir($this->main_template_dir . '/layouts/');
		$this->addTemplateDir($this->main_template_dir);

		//Test templates
		$this->addTemplateDir(TIKI_PATH . '/lib/test/core/Search/');
	}

	/**
	 * When calling directly smarty functions, from PHP, you need to provide a object of type Smarty_Internal_Template
	 * The method signature for smarty functions is: smarty_function_xxxx($params, Smarty_Internal_Template $template)
	 *
	 * @return Smarty_Internal_Template
	 */
	public function getEmptyInternalTemplate()
	{
		global $prefs;
		$tpl = new Smarty_Internal_Template('empty', $this);
		$tpl->assign('app_name', $this->getTemplateVars('app_name'));
		$tpl->assignByRef('prefs', $prefs);
		return $tpl;
	}
}
