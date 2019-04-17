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

class Tiki_Hm_Output_HTTP {
  public function send_response($response, $input=array()) {
    if (array_key_exists('http_headers', $input)) {
      return $this->output_content($response, $input['http_headers']);
    }
    else {
      return $this->output_content($response, array());
    }
  }

  protected function output_headers($headers) {
    foreach ($headers as $name => $value) {
      Hm_Functions::header($name.': '.$value);
    }
  }

  protected function output_content($content, $headers=array()) {
    $this->output_headers($headers);
    return $content;
  }
}

class Tiki_Hm_Custom_Session extends Hm_Session {

    /**
     * check for an active session or an attempt to start one
     * @param object $request request object
     * @return bool
     */
    public function check($request) {
        $this->active = session_status() == PHP_SESSION_ACTIVE;
        return $this->is_active();
    }

    /**
     * Start the session. This could be an existing session or a new login
     * @param object $request request details
     * @return void
     */
    public function start($request, $existing_session=false) {
        // Tiki handles this
        return;
    }

    /**
     * Call the configured authentication method to check user credentials
     * @param string $user username
     * @param string $pass password
     * @return bool true if the authentication was successful
     */
    public function auth($user, $pass) {
        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($user, $pass);
        return $isvalid;
    }

    /**
     * Return a session value, or a user settings value stored in the session
     * @param string $name session value name to return
     * @param mixed $default value to return if $name is not found
     * @return mixed the value if found, otherwise $defaultHm_Auth
     */
    public function get($name, $default=false, $user=false) {
        if ($user) {
            return array_key_exists('cypht', $_SESSION) && array_key_exists('user_data', $_SESSION['cypht']) && array_key_exists($name, $_SESSION['cypht']['user_data']) ? $_SESSION['cypht']['user_data'][$name] : $default;
        }
        else {
            return array_key_exists('cypht', $_SESSION) && array_key_exists($name, $_SESSION['cypht']) ? $_SESSION['cypht'][$name] : $default;
        }
    }

    /**
     * Save a value in the session
     * @param string $name the name to save
     * @param string $value the value to save
     * @return void
     */
    public function set($name, $value, $user=false) {
        if ($user) {
            $_SESSION['cypht']['user_data'][$name] = $value;
        }
        else {
            $_SESSION['cypht'][$name] = $value;
        }
    }

    /**
     * Delete a value from the session
     * @param string $name name of value to delete
     * @return void
     */
    public function del($name) {
        if (array_key_exists('cypht', $_SESSION) && array_key_exists($name, $_SESSION['cypht'])) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * End a session after a page request is complete. This only closes the session and
     * does not destroy it
     * @return void
     */
    public function end() {
        $this->active = false;
        return true;
    }

    /**
     * Destroy a session for good
     * @param object $request request details
     * @return void
     */
    public function destroy($request) {
        if (function_exists('delete_uploaded_files')) {
            delete_uploaded_files($this);
        }
        unset($_SESSION['cypht']);
        $this->active = false;
    }

    public function close_early() {
        // noop;
    }
}
