<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

class Tiki_Hm_Output_HTTP
{
    public function send_response($response, $input = [])
    {
        if (array_key_exists('http_headers', $input)) {
            return $this->output_content($response, $input['http_headers']);
        }
         
        return $this->output_content($response, []);
    }

    protected function output_headers($headers)
    {
        foreach ($headers as $name => $value) {
            Hm_Functions::header($name . ': ' . $value);
        }
    }

    protected function output_content($content, $headers = [])
    {
        $this->output_headers($headers);

        return $content;
    }
}

class Tiki_Hm_Custom_Session extends Hm_Session
{

    /**
     * check for an active session or an attempt to start one
     * @param object $request request object
     * @return bool
     */
    public function check($request)
    {
        $this->active = session_status() == PHP_SESSION_ACTIVE;

        return $this->is_active();
    }

    /**
     * Start the session. This could be an existing session or a new login
     * @param object $request request details
     * @param mixed $existing_session
     * @return void
     */
    public function start($request, $existing_session = false)
    {
        // Tiki handles this
        return;
    }

    /**
     * Call the configured authentication method to check user credentials
     * @param string $user username
     * @param string $pass password
     * @return bool true if the authentication was successful
     */
    public function auth($user, $pass)
    {
        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($user, $pass);

        return $isvalid;
    }

    /**
     * Return a session value, or a user settings value stored in the session
     * @param string $name session value name to return
     * @param mixed $default value to return if $name is not found
     * @param mixed $user
     * @return mixed the value if found, otherwise $defaultHm_Auth
     */
    public function get($name, $default = false, $user = false)
    {
        if ($user) {
            return array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists('user_data', $_SESSION[$this->session_prefix()]) && array_key_exists($name, $_SESSION[$this->session_prefix()]['user_data']) ? $_SESSION[$this->session_prefix()]['user_data'][$name] : $default;
        }
         
        return array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists($name, $_SESSION[$this->session_prefix()]) ? $_SESSION[$this->session_prefix()][$name] : $default;
    }

    /**
     * Save a value in the session
     * @param string $name the name to save
     * @param string $value the value to save
     * @param mixed $user
     * @return void
     */
    public function set($name, $value, $user = false)
    {
        if ($user) {
            $_SESSION[$this->session_prefix()]['user_data'][$name] = $value;
        } else {
            $_SESSION[$this->session_prefix()][$name] = $value;
        }
    }

    /**
     * Delete a value from the session
     * @param string $name name of value to delete
     * @return void
     */
    public function del($name)
    {
        if (array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists($name, $_SESSION[$this->session_prefix()])) {
            unset($_SESSION[$this->session_prefix()][$name]);
        }
    }

    /**
     * End a session after a page request is complete. This only closes the session and
     * does not destroy it
     * @return void
     */
    public function end()
    {
        $this->active = false;

        return true;
    }

    /**
     * Destroy a session for good
     * @param object $request request details
     * @return void
     */
    public function destroy($request)
    {
        if (function_exists('delete_uploaded_files')) {
            delete_uploaded_files($this);
        }
        unset($_SESSION[$this->session_prefix()]);
        $this->active = false;
    }

    public function close_early()
    {
        // noop;
    }

    protected function session_prefix()
    {
        return $this->site_config->get('session_prefix') ?? 'cypht';
    }
}

class Tiki_Hm_Site_Config_file extends Hm_Site_Config_File
{
    /**
     * Load data based on source
     * Overrides default configuration for Tiki integration
     * @param string $source source location for site configuration
     * @param mixed $session_prefix
     */
    public function __construct($source, $session_prefix = 'cypht')
    {
        global $user;
        parent::__construct($source);
        // override
        $headerlib = TikiLib::lib('header');
        $this->set('session_type', 'custom');
        $this->set('session_class', 'Tiki_Hm_Custom_Session');
        $this->set('session_prefix', $session_prefix);
        $this->set('auth_type', 'custom');
        $this->set('output_class', 'Tiki_Hm_Output_HTTP');
        $this->set('cookie_path', ini_get('session.cookie_path'));
        if ($user && (empty($_SESSION[$session_prefix]['user_data']) || count($_SESSION[$session_prefix]['user_data']) == 2)) {
            $user_config = new Tiki_Hm_User_Config($this);
            $user_config->load($user);
            $_SESSION[$session_prefix]['user_data'] = $user_config->dump();
        }
        $output_modules = $this->get('output_modules');
        $handler_modules = $this->get('handler_modules');
        foreach ($output_modules as $page => $_) {
            unset($output_modules[$page]['header_start']);
            unset($output_modules[$page]['header_content']);
            unset($output_modules[$page]['header_end']);
            unset($output_modules[$page]['content_start']);
            unset($output_modules[$page]['content_end']);
            if (isset($output_modules[$page]['header_css'])) {
                unset($output_modules[$page]['header_css']);
                $headerlib->add_cssfile('lib/cypht/site.css');
                $headerlib->add_cssfile('lib/cypht/modules/tiki/site.css');
            }
            if (isset($output_modules[$page]['page_js'])) {
                unset($output_modules[$page]['page_js']);
                $headerlib->add_jsfile('lib/cypht/jquery.touch.js', true);
                $headerlib->add_jsfile('lib/cypht/site.js', true);
            }
        }
        // cleanup side menu
        unset($output_modules['ajax_hm_folders']['logout_menu_item']);
        unset($output_modules['ajax_hm_folders']['contacts_page_link']);
        unset($output_modules['ajax_hm_folders']['settings_save_link']);
        // show links according to permissions
        if (! Perms::get()->admin_personal_webmail && ! Perms::get()->admin_group_webmail) {
            unset($output_modules['ajax_hm_folders']['settings_servers_link']);
            unset($output_modules['ajax_hm_folders']['folders_page_link']);
            unset($output_modules['home']['welcome_dialog']);
            unset($handler_modules['ajax_imap_folder_expand']['add_folder_manage_link']);
        }
        $this->set('output_modules', $output_modules);
        $this->set('handler_modules', $handler_modules);
        if (empty($_SESSION[$session_prefix]['user_data']['timezone_setting'])) {
            $this->user_defaults['timezone_setting'] = TikiLib::lib('tiki')->get_display_timezone();
            if (isset($_SESSION[$session_prefix]['user_data'])) {
                $_SESSION[$session_prefix]['user_data']['timezone_setting'] = $this->user_defaults['timezone_setting'];
            }
        }
    }
}

/**
 * Override user config handling in Cypht.
 * Store settings in Tiki user preferences and load them from there.
 * Ignore encryption and decryption of the settings due to missing password key when loading.
 */
class Tiki_Hm_User_Config extends Hm_Config
{
    /* username */
    private $username;
    private $site_config;

    /**
     * Load site configuration
     * @param object $config site config
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config->user_defaults);
        $this->site_config = $config;
    }

    /**
     * Load the settings for a user
     * @param string $username username
     * @param string $key key to decrypt the user data (not used)
     * @return void
     */
    public function load($username, $key = null)
    {
        $this->username = $username;
        $data = TikiLib::lib('tiki')->get_user_preference($username, $_SESSION[$this->site_config->get('session_prefix')]['preference_name']);
        if ($data) {
            $data = $this->decode($data);
            $this->config = array_merge($this->config, $data);
            $this->set_tz();
        }
    }

    /**
     * Reload from outside input
     * @param array $data new user data
     * @param string $username
     * @return void
     */
    public function reload($data, $username = false)
    {
        $this->username = $username;
        $this->config = $data;
        $this->set_tz();
    }

    /**
     * Save user settings into Tiki
     * @param string $username username
     * @param string $key encryption key (not used)
     * @return void
     */
    public function save($username, $key = null)
    {
        $this->shuffle();
        $removed = $this->filter_servers();
        $data = json_encode($this->config);
        TikiLib::lib('tiki')->set_user_preference($username, $_SESSION[$this->site_config->get('session_prefix')]['preference_name'], $data);
        $this->restore_servers($removed);
    }

    /**
     * Set a config value
     * @param string $name config value name
     * @param string $value config value
     * @return void
     */
    public function set($name, $value)
    {
        $this->config[$name] = $value;
        $this->save($this->username);
    }
}
