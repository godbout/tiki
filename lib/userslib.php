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

/**
 * Lib for user administration, groups and permissions.
 */

// some definitions for helping with authentication
define('USER_VALID', 2);

define('SERVER_ERROR', -1);
define('PASSWORD_INCORRECT', -3);
define('USER_NOT_FOUND', -5);
define('ACCOUNT_DISABLED', -6);
define('ACCOUNT_WAITING_USER', -9);
define('USER_AMBIGOUS', -7);
define('USER_NOT_VALIDATED', -8);
define('USER_PREVIOUSLY_VALIDATED', -10);
define('USER_ALREADY_LOGGED', -11);
define('EMAIL_AMBIGUOUS', -12);
define('TWO_FA_INCORRECT', -13);

//added for Auth v1.3 support
define('AUTH_LOGIN_OK', 0);

use Laminas\Ldap\Exception\LdapException;
use OneLogin\Saml2;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class UsersLib extends TikiLib
{
    // change this to an email address to receive debug emails from the LDAP code
    public $debug = false;

    public $usergroups_cache;
    public $groupperm_cache;
    public $groupinclude_cache;
    public $userobjectperm_cache; // used to cache queries in object_has_one_permission()
    public $get_object_permissions_for_user_cache;
    public static $cas_initialized = false;
    public static $userexists_cache = [];




    public function __construct()
    {
        parent::__construct();

        // Initialize caches
        $this->usergroups_cache = [];
        $this->groupperm_cache = [[]];
        $this->groupinclude_cache = [];
        $this->get_object_permissions_for_user_cache = [];
    }


    public function assign_object_permission($groupName, $objectId, $objectType, $permName)
    {
        $objectId = md5($objectType . TikiLib::strtolower($objectId));

        $query = 'delete from `users_objectpermissions`	where `objectId` = ? and `objectType`=?';
        $bindvars = [$objectId, $objectType];
        if (! empty($groupName)) {
            $query .= ' and `groupName` = ?';
            $bindvars[] = $groupName;
        }
        if (! empty($permName)) {
            $query .= ' and `permName` = ?';
            $bindvars[] = $permName;
        }
        $result = $this->query($query, $bindvars);

        if (! empty($permName) && ! empty($groupName)) {
            $query = 'insert into `users_objectpermissions`' .
                ' (`groupName`, `objectId`, `objectType`, `permName`)' .
                ' values(?, ?, ?, ?)';

            $result = $this->query($query, [$groupName, $objectId, $objectType, $permName]);
        }

        if ($objectType == 'file gallery') {
            $cachelib = TikiLib::lib('cache');
            $cachelib->empty_type_cache('fgals_perms_' . $objectId . '_');
        }

        return true;
    }

    public function object_has_permission($user, $objectId, $objectType, $permName)
    {
        $groups = $this->get_user_groups($user);
        $objectId = md5($objectType . TikiLib::strtolower($objectId));
        $mid = implode(',', array_fill(0, count($groups), '?'));
        $query = "select count(*) from `users_objectpermissions` where `groupName` in ($mid) and `objectId` = ? and `objectType` = ? and `permName` = ?";
        $bindvars = array_merge($groups, [$objectId, $objectType, $permName]);
        $result = $this->getOne($query, $bindvars);
        if ($result > 0) {
            return true;
        }

        return false;
    }

    public function remove_object_permission($groupName, $objectId, $objectType, $permName)
    {
        $objectId = md5($objectType . TikiLib::strtolower($objectId));

        $query = 'delete from `users_objectpermissions`' .
            ' where`objectId` = ? and `objectType` = ?';
        $bindvars = [$objectId, $objectType];
        if (! empty($groupName)) {
            $query .= ' and `groupName` = ? ';
            $bindvars[] = $groupName;
        }
        if (! empty($permName)) {
            $query .= ' and `permName` = ? ';
            $bindvars[] = $permName;
        }

        $result = $this->query($query, $bindvars);

        if ($objectType == 'file gallery') {
            $cachelib = TikiLib::lib('cache');
            $cachelib->empty_type_cache('fgals_perms_' . $objectId . '_');
        }

        return true;
    }

    public function copy_object_permissions($objectId, $destinationObjectId, $objectType)
    {
        $objectId = md5($objectType . TikiLib::strtolower($objectId));

        $query = "select `permName`, `groupName`
			from `users_objectpermissions`
			where `objectId` =? and
			`objectType` = ?";
        $bindvars = [$objectId, $objectType];
        $result = $this->query($query, $bindvars);
        while ($res = $result->fetchRow()) {
            $this->assign_object_permission($res["groupName"], $destinationObjectId, $objectType, $res["permName"]);
        }

        return true;
    }

    public function get_object_permissions($objectId, $objectType, $group = '', $perm = '')
    {
        $objectId = md5($objectType . TikiLib::strtolower($objectId));

        $query = "select `groupName`, `permName`
			from `users_objectpermissions`
			where `objectId` = ? and
			`objectType` = ?";
        $bindvars = [$objectId, $objectType];
        if (! empty($group)) {
            $query .= ' and `groupName`=?';
            $bindvars[] = $group;
        }
        if (! empty($perm)) {
            $query .= ' and `permName`=?';
            $bindvars[] = $perm;
        }

        return $this->fetchAll($query, $bindvars);
    }

    public function get_object_permissions_for_user($objectId, $objectType, $user)
    {
        $params = md5($objectId . $objectType . $user);
        //Check the cache for these parameters
        if (array_key_exists($params, $this->get_object_permissions_for_user_cache)) {
            return $this->get_object_permissions_for_user_cache[$params];
        }
        $objectId = md5($objectType . TikiLib::strtolower($objectId));
        $bindvars = [$objectId, $objectType];
        $groups = $this->get_user_groups($user);
        $bindvars = array_merge($bindvars, $groups);

        $query = 'select `permName` ' .
            ' from `users_objectpermissions`' .
            ' where `objectId` = ? and `objectType` = ?' .
            ' and `groupName` in (' . implode(',', array_fill(0, count($groups), '?')) . ')';

        $result = $this->query($query, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res['permName'];
        }

        //Cache the result for this set of parameters
        $this->get_object_permissions_for_user_cache[$params] = $ret;

        return $ret;
    }

    public function object_has_one_permission($objectId, $objectType)
    {
        $objectId = md5($objectType . TikiLib::strtolower($objectId));

        if (! isset($this->userobjectperm_cache) || ! is_array($this->userobjectperm_cache)
            || ! isset($this->userobjectperm_cache[$objectId])) {
            // i think, we really dont need the "and `objectType`=?" because the objectId should be unique due to the md5()
            $query = 'select count(*) from `users_objectpermissions` where `objectId`=? and `objectType`=?';
            $this->userobjectperm_cache[$objectId] = $this->getOne($query, [$objectId, $objectType]);
        }

        return $this->userobjectperm_cache[$objectId];
    }

    public function user_exists($user)
    {
        if (! isset($userexists_cache[$user])) {
            $query = 'select count(*) from `users_users` where upper(`login`) = ?';
            $result = $this->getOne($query, [TikiLib::strtoupper($user)]);
            $userexists_cache[$user] = $result;
        }

        return $userexists_cache[$user];
    }
    public function user_exists_by_email($email)
    {
        if (! isset($userexists_cache[$email])) {
            $query = 'select count(*) from `users_users` where upper(`email`) = ?';
            $result = $this->getOne($query, [TikiLib::strtoupper($email)]);
            $userexists_cache[$email] = $result;
        }

        return $userexists_cache[$email];
    }
    public function get_user_real_case($user)
    {
        $query = 'select `login` from `users_users` where upper(`login`) = ?';

        return $this->getOne($query, [TikiLib::strtoupper($user)]);
    }

    public function group_exists($group)
    {
        return in_array($group, $this->list_all_groups());
    }

    /**
     * @param string $user : username
     * @param bool $remote_logout : logged out remotely (so do not redirect)
     * @param string $redir : url to redirect to. Uses home page according to prefs if empty
     * @return void : redirects to suitable homepage or redir param if not remote
     */
    public function user_logout($user, $remote_logout = false, $redir = '')
    {
        global $prefs, $user_cookie_site;

        $logslib = TikiLib::lib('logs');
        $logslib->add_log('login', 'logged out');

        $userInfo = $this->get_user_info($user);
        if ($prefs['login_multiple_forbidden'] === 'y') {
            $this->delete_user_cookie($userInfo['userId']);
        } else {
            $secret = explode('.', $_COOKIE[$user_cookie_site]);
            $this->delete_user_cookie($userInfo['userId'], $secret[0]);
        }

        if ($prefs['feature_intertiki'] == 'y' and $prefs['feature_intertiki_sharedcookie'] == 'y' and ! empty($prefs['feature_intertiki_mymaster'])) {
            $remote = $prefs['interlist'][$prefs['feature_intertiki_mymaster']];
            $remote['path'] = preg_replace('/^\/?/', '/', $remote['path']);
            $client = new XML_RPC_Client($remote['path'], $remote['host'], $remote['port']);
            $client->setDebug(0);
            $msg = new XML_RPC_Message(
                'intertiki.logout',
                [
                     new XML_RPC_Value($prefs['tiki_key'], 'string'),
                     new XML_RPC_Value($user, 'string')
                ]
            );
            $client->send($msg);
        }

        // more local cleanup originally from tiki-logout.php

        // go offline in Live Support
        if ($prefs['feature_live_support'] == 'y') {
            $access = TikiLib::lib('access');
            global $lslib;
            include_once('lib/live_support/lslib.php');
            if ($lslib->get_operator_status($user) != 'offline') {
                $lslib->set_operator_status($user, 'offline');
            }
        }

        if ($prefs['auth_method'] === 'saml' && $prefs['saml_options_slo'] == 'y') {
            $saml_instance = $this->get_saml_auth();
            if (isset($saml_instance)) {
                $nameId = null;
                $sessionIndex = null;
                if (isset($_SESSION['saml_nameid'])) {
                    $nameId = $_SESSION['saml_nameid'];
                }
                if (isset($_SESSION['saml_sessionindex'])) {
                    $sessionIndex = $_SESSION['saml_sessionindex'];
                }
                $saml_instance->logout(null, [], $nameId, $sessionIndex);
            }
        }

        setcookie($user_cookie_site, '', -3600, $prefs['feature_intertiki_sharedcookie'] == 'y' ? '/' : $prefs['cookie_path'], $prefs['cookie_domain']);

        /* change group home page or deactivate if no page is set */
        if (! empty($redir)) {
            $url = $redir;
        } elseif (($groupHome = $this->get_group_home('Anonymous')) != '') {
            $url = (preg_match('/^(\/|https?:)/', $groupHome)) ? $groupHome : 'tiki-index.php?page=' . $groupHome;
        } else {
            $url = $prefs['site_tikiIndex'];
        }
        // RFC 2616 defines that the 'Location' HTTP headerconsists of an absolute URI
        if (! preg_match('/^https?\:/i', $url)) {
            global $url_scheme, $url_host, $url_port, $base_url;
            $url = (preg_match('#^/#', $url) ? $url_scheme . '://' . $url_host . (($url_port != '') ? ":$url_port" : '') : $base_url) . $url;
        }
        if (SID) {
            $url .= '?' . SID;
        }

        if ($prefs['auth_method'] === 'cas' && $user !== 'admin' && $user !== '' && $prefs['cas_force_logout'] === 'y') {
            phpCAS::logoutWithRedirectService($url);
        }
        unset($_SESSION['cas_validation_time']);
        unset($_SESSION[$user_cookie_site]);
        session_unset();
        session_destroy();

        if ($remote_logout) {
            return;
        }

        if ($prefs['auth_method'] === 'ws') {
            header('Location: ' . str_replace('//', '//admin:@', $url)); // simulate a fake login to logout the user
        } else {
            header('Location: ' . $url);
        }

        return;
    }

    /**
     * @see TikiLib::genPass()
     * TODO: Merge with the above
     */
    public static function genPass()
    {
        // AWC: enable mixed case and digits, don't return too short password
        global $prefs;

        $vocales = 'AaEeIiOoUu13580';
        $consonantes = 'BbCcDdFfGgHhJjKkLlMmNnPpQqRrSsTtVvWwXxYyZz24679';
        $r = '';
        $passlen = ($prefs['min_pass_length'] > 5) ? $prefs['min_pass_length'] : 5;

        for ($i = 0; $i < $passlen; $i++) {
            if ($i % 2) {
                $r .= $vocales[rand(0, strlen($vocales) - 1)];
            } else {
                $r .= $consonantes[rand(0, strlen($consonantes) - 1)];
            }
        }

        return $r;
    }

    /**
     * Force a logout for the specified user
     * @param $user
     */
    public function force_logout($user)
    {
        if (! empty($user)) {
            // Clear the timestamp for the existing session,
            //	which will force a logout next time the user accesses the session
            $this->query('delete from `tiki_sessions` where `user`=?', [$user]);

            // Add a log entry
            $logslib = TikiLib::lib('logs');
            $logslib->add_log("login", "logged out", $user, '', '', $this->now);
        }
    }

    // For each auth method, validate user in auth, if valid, verify tiki user exists and create if necessary (as configured)
    // Once complete, update_lastlogin and return result, username and login message.
    public function validate_user($user, $pass, $validate_phase = false, $twoFactorCode = null)
    {
        global $prefs;

        $user = str_replace(chr(0), '', $user);
        $pass = str_replace(chr(0), '', $pass);

        if ($user != 'admin' && $prefs['feature_intertiki'] == 'y' && ! empty($prefs['feature_intertiki_mymaster'])) {
            // slave intertiki sites should never check passwords locally, just for admin
            return false;
        }

        // these will help us keep tabs of what is going on
        $userTiki = false;
        $userTikiPresent = false;
        $userLdap = false;
        $userLdapPresent = false;

        // read basic pam options
        $auth_pam = ($prefs['auth_method'] == 'pam');
        $pam_create_tiki = ($prefs['pam_create_user_tiki'] == 'y');
        $pam_skip_admin = ($prefs['pam_skip_admin'] == 'y');

        // read basic LDAP options
        $auth_ldap = ($prefs['auth_method'] == 'ldap');
        $ldap_create_tiki = ($prefs['ldap_create_user_tiki'] == 'y');
        $create_auth = ($prefs['ldap_create_user_ldap'] == 'y');
        $skip_admin = ($prefs['ldap_skip_admin'] == 'y');

        // read basic cas options
        $auth_cas = ($prefs['auth_method'] == 'cas');
        $cas_create_tiki = ($prefs['cas_create_user_tiki'] == 'y');
        $cas_skip_admin = ($prefs['cas_skip_admin'] == 'y');

        // read basic phpbb options
        $auth_phpbb = ($prefs['auth_method'] == 'phpbb');
        $phpbb_create_tiki = ($prefs['auth_phpbb_create_tiki'] == 'y');
        $phpbb_skip_admin = ($prefs['auth_phpbb_skip_admin'] == 'y');
        $phpbb_disable_tikionly = ($prefs['auth_phpbb_disable_tikionly'] == 'y');

        // see if we are to use Shibboleth
        $auth_shib = ($prefs['auth_method'] == 'shib');
        $shib_create_tiki = ($prefs['shib_create_user_tiki'] == 'y');
        $shib_skip_admin = ($prefs['shib_skip_admin'] == 'y');

        // see if we are to use SAML
        $auth_saml = ($prefs['auth_method'] == 'saml');
        $saml_create_tiki = (isset($prefs['saml_options_autocreates']) && $prefs['saml_options_autocreates'] == 'y');
        $saml_skip_admin = (isset($prefs['saml_options_skip_admin']) && $prefs['saml_options_skip_admin'] == 'y');

        // first attempt a login via the standard Tiki system
        //
        if (! ($auth_shib || $auth_cas || $auth_saml) || $user == 'admin') { //redflo: does this mean, that users in cas and shib are not replicated to tiki tables? Does this work well?
            list($result, $user) = $this->validate_user_tiki($user, $pass, $validate_phase);
        } else {
            $result = null;
        }

        // If preference login_multiple_forbidden is set, don't let user login if already logged in
        if ($result == USER_VALID && $prefs['login_multiple_forbidden'] == 'y' && $user != 'admin') {
            $tikilib = TikiLib::lib('tiki');
            $grabSessionOnAlreadyLoggedIn = ! empty($prefs['login_grab_session']) ? $prefs['login_grab_session'] : 'n';
            if ($grabSessionOnAlreadyLoggedIn === 'y') {
                // Log out first, then proceed to log in again
                $this->force_logout($user);
            } else {
                $tikilib->update_session();
                if ($tikilib->is_user_online($user)) {
                    $result = USER_ALREADY_LOGGED;
                }
            }
        }

        switch ($result) {
            case USER_VALID:
                $userTiki = true;
                $userTikiPresent = true;

                break;

            case USER_ALREADY_LOGGED:
                $userTikiPresent = true;

                break;

            case PASSWORD_INCORRECT:
                $userTikiPresent = true;

                break;
        }

        // if we aren't using LDAP this will be quick
        // if we are using tiki auth or if we're using an alternative auth except for admin

        // todo: bad hack. better search for a more general solution here
        if ((! $auth_ldap && ! $auth_pam && ! $auth_cas && ! $auth_shib && ! $auth_saml && ! $auth_phpbb)
                || (
                    (
                            ($auth_ldap && $skip_admin)
                            || ($auth_shib && $shib_skip_admin)
                            || ($auth_saml && $saml_skip_admin)
                            || ($auth_pam && $pam_skip_admin)
                            || ($auth_cas && $cas_skip_admin)
                            || ($auth_phpbb && $phpbb_skip_admin)
                        )
                        && $user == 'admin'
                )
                || ($auth_ldap && ($prefs['auth_ldap_permit_tiki_users'] == 'y' && $userTiki))
            ) {
            // if the user verified ok, log them in
            if ($userTiki) {//user validated in tiki, update lastlogin and be done
                if ($auth_ldap) {
                    return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result, 'tiki'];
                }

                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            // if the user password was incorrect but the account was there, give an error
            } elseif ($userTikiPresent) { //user ixists in tiki but bad password
                return [false, $user, $result];
            } // if the user was not found, give an error
            // this could be for future uses
             
            return [false, $user, $result];
            

        // For the alternate auth methods, attempt to validate user
        // return back one of two conditions
        // Valid User or Bad password
        // next see if we need to check PAM
        } elseif ($auth_pam) {
            $result = $this->validate_user_pam($user, $pass);
            switch ($result) {
                case USER_VALID:
                    $userPAM = true;

                    break;

                case PASSWORD_INCORRECT:
                    $userPAM = false;

                    break;
            }

            // start off easy
            // if the user verified in Tiki and PAM, log in
            if ($userPAM && $userTiki) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            } elseif (! $userTikiPresent && ! $userPAM) { // if the user wasn't found in either system, just fail
                return [false, $user, $result];
            } elseif ($userPAM && ! $userTikiPresent) {	// if the user was logged into PAM but not found in Tiki
                // see if we can create a new account
                if ($pam_create_tiki) {
                    // need to make this better! *********************************************************
                    $result = $this->add_user($user, $pass, '');

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
                    // if the server didn't work, do something!
                    } elseif ($result == SERVER_ERROR) {
                        // check the notification status for this type of error
                        return [false, $user, $result];
                    }
                    // otherwise don't log in.
                    return [false, $user, $result];
                }
                // otherwise
                // just say no!
                return [false, $user, $result];
            } // if the user was logged into PAM and found in Tiki (no password in Tiki user table necessary)
            elseif ($userPAM && $userTikiPresent) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            }
        } elseif ($auth_cas) {
            // next see if we need to check CAS
            $result = $this->validate_user_cas($user);

            switch ($result) {
                case USER_VALID:
                    $userCAS = true;

                    break;

                case PASSWORD_INCORRECT:
                    $userCAS = false;

                    break;
            }

            if ($this->user_exists($user)) {
                $userTikiPresent = true;
            } else {
                $userTikiPresent = false;
            }

            // start off easy
            // if the user verified in Tiki and by CAS, log in
            if ($userCAS && $userTikiPresent) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            } elseif (! $userCAS) {
                // if the user wasn't authenticated through CAS, just fail
                return [false, $user, $result];
            } elseif ($userCAS && ! $userTikiPresent) {
                // if the user was authenticated by CAS but not found in Tiki

                // see if we can create a new account
                if ($cas_create_tiki) {
                    // need to make this better! *********************************************************
                    $randompass = $this->genPass();
                    // in case CAS auth is turned off accidentally;
                    // we don't want ppl to be able to login as any user with blank passwords
                    $result = $this->add_user($user, $randompass, '');

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
                    // if the server didn't work, do something!
                    } elseif ($result == SERVER_ERROR) {
                        // check the notification status for this type of error
                        return [false, $user, $result];
                    }
                    // otherwise don't log in.
                    return [false, $user, $result];
                }
                // otherwise
                // just say no!
                return [false, $user, $result];
            } // if the user was authenticated by CAS and found in Tiki (no password in Tiki user table necessary)
            elseif ($userCAS && $userTikiPresent) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            }
        } elseif ($auth_shib) {
            // next see if we need to check Shibboleth

            if ($this->user_exists($user)) {
                $userTikiPresent = true;
            } else {
                $userTikiPresent = false;
            }

            // Shibboleth login was not successful
            if (! isset($_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'])) {
                return false;
            }

            // Collect the shibboleth related attributes.
            $shibmail = $_SERVER['HTTP_MAIL'];
            $shibaffiliation = $_SERVER['HTTP_SHIB_EP_UNSCOPEDAFFILIATION'];
            $shibproviderid = $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'];

            // Get the affiliation information to log in
            $shibaffiliarray = preg_split('/;/', TikiLib::strtoupper($shibaffiliation));
            $validaffiliarray = preg_split('/,/', TikiLib::strtoupper($prefs['shib_affiliation']));
            $validafil = false;

            foreach ($shibaffiliarray as $affil) {
                if (in_array($affil, $validaffiliarray)) {
                    $validafil = true;
                }
            }

            // start off easy
            // if the user verified in Tiki and by Shibboleth, log in
            if ($userTikiPresent && $validafil) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, USER_VALID];
            }
            $smarty = TikiLib::lib('smarty');
            // see if we can create a new account
            if ($shib_create_tiki) {
                if (! (strlen($user) > 0 and strlen($shibmail) > 0 and strlen($shibaffiliation) > 0)) {
                    $errmsg = 'User registration error: You do not have the required shibboleth attributes (';

                    if (strlen($user) == 0) {
                        $errmsg = $errmsg . 'User ';
                    }

                    if (strlen($shibmail) == 0) {
                        $errmsg = $errmsg . 'Mail ';
                    }

                    if (strlen($shibaffiliation) == 0) {
                        $errmsg = $errmsg . 'Affiliation ';
                    }

                    $errmsg = $errmsg . '). For further information on this error goto the ((ShibReg)) Page';

                    $smarty->assign('msg', $errmsg);
                    $smarty->display('error.tpl');
                    exit;
                }
                if ($validafil) {
                    // Create the user
                    // need to make this better! *********************************************************
                    $randompass = $this->genPass();
                    // in case Shibboleth auth is turned off accidentally;
                    // we don't want ppl to be able to login as any user with blank passwords

                    $result = $this->add_user($user, $randompass, $shibmail);

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // Add to the default Group
                        if ($prefs['shib_usegroup'] == 'y') {
                            $result = $this->assign_user_to_group($user, $prefs['shib_group']);
                        }

                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $randompass), $user, $result];
                    } elseif ($result == SERVER_ERROR) {
                        // if the server didn't work, do something!

                        // check the notification status for this type of error
                        return [false, $user, $result];
                    }
                    // otherwise don't log in.
                    return [false, $user, $result];
                }
                $vaffils = '';
                foreach ($validaffiliarray as $vaffil) {
                    $vaffils = $vaffils . $vaffil . ", ";
                }
                $vaffils = rtrim($vaffils, ", ");
                $errmsg = '<H1 style="text-align: center;">User login error</H1>' .
                                                '<BR/><BR/>You must have one of the following affiliations to get into this wiki.<BR/><BR/>' .
                                                '<B>' . $vaffils . '</B><BR><BR/><BR/>' .
                                                'For further information on this error goto the <a href="./tiki-index.php?page=ShibReg">Shibreg</a> Page';

                $smarty->assign('msg', $errmsg);
                $smarty->display('error.tpl');
                exit;
            }
            $smarty->assign('msg', 'The user [ ' . $user . ' ] is not registered with this wiki.');
            $smarty->display('error.tpl');
            exit;
        } elseif ($auth_saml) {
            // next see if we need to check SAML
            if (isset($_SESSION['samlUserdata']) && ! empty($_SESSION['samlUserdata']) ||
                isset($_SESSION['samlNameId']) && ! empty($_SESSION['samlNameId'])
                ) {
                $saml_username = $saml_email = '';
                $saml_groups = [];

                if (empty($_SESSION['samlUserdata'])) {
                    $saml_username = $_SESSION['samlNameId'];
                    $saml_email = $saml_username;
                } else {
                    $usernameMapping = isset($prefs['saml_attrmap_username']) ? $prefs['saml_attrmap_username'] : '';
                    $emailMapping = isset($prefs['saml_attrmap_mail']) ? $prefs['saml_attrmap_mail'] : '';
                    $groupMapping = isset($prefs['saml_attrmap_group']) ? $prefs['saml_attrmap_group'] : '';

                    if (! empty($usernameMapping) && isset($_SESSION['samlUserdata'][$usernameMapping]) && ! empty($_SESSION['samlUserdata'][$usernameMapping][0])) {
                        $saml_username = $_SESSION['samlUserdata'][$usernameMapping][0];
                    }

                    if (! empty($emailMapping) && isset($_SESSION['samlUserdata'][$emailMapping]) && ! empty($_SESSION['samlUserdata'][$usernameMapping][0])) {
                        $saml_email = $_SESSION['samlUserdata'][$emailMapping][0];
                    }

                    if (! empty($groupMapping) && isset($_SESSION['samlUserdata'][$groupMapping]) && ! empty($_SESSION['samlUserdata'][$groupMapping])) {
                        $group_values = $_SESSION['samlUserdata'][$groupMapping];

                        foreach ($group_values as $group_value) {
                            if (isset($prefs['saml_groupmap_admins']) && ! empty($prefs['saml_groupmap_admins'])) {
                                if (strcasecmp($prefs['saml_groupmap_admins'], $group_value) == 0) {
                                    $saml_groups[] = "Admins";
                                }
                            }
                            if (isset($prefs['saml_groupmap_registered']) && ! empty($prefs['saml_groupmap_registered'])) {
                                if (strcasecmp($prefs['saml_groupmap_registered'], $group_value) == 0) {
                                    $saml_groups[] = "Registered";
                                }
                            }
                        }
                    }

                    // Code SAML Custom role here
                }

                $matcher = isset($prefs['saml_option_account_matcher']) ? $prefs['saml_option_account_matcher'] : 'email';

                if ($matcher == 'email') {
                    if (empty($saml_email)) {
                        Feedback::error(tra("The email could not be retrieved from the IdP and is required"));

                        return [false, $username, SERVER_ERROR];
                    }
                    $username = $this->get_user_by_email($saml_email);
                    if ($this->user_exists($username)) {
                        $userTikiPresent = true;
                    } else {
                        $userTikiPresent = false;
                        if (! isset($prefs['saml_options_autocreate']) || $prefs['saml_options_autocreate'] != 'y') {
                            Feedback::error(tr('The user [ %0 ] is not registered with this wiki and autocreate is disabled.', $saml_email));

                            return [false, $username, USER_NOT_FOUND];
                        }
                    }
                } else {
                    if (empty($saml_username)) {
                        Feedback::error(tra("The username could not be retrieved from the IdP and is required"));

                        return [false, $username, SERVER_ERROR];
                    }
                    $username = $saml_username;
                    if ($this->user_exists($saml_username)) {
                        $userTikiPresent = true;
                    } else {
                        $userTikiPresent = false;

                        if (! isset($prefs['saml_options_autocreate']) || $prefs['saml_options_autocreate'] != 'y') {
                            Feedback::error(tr('The user [ %0 ] is not registered with this wiki and autocreate is disabled.', $saml_username));

                            return [false, $username, USER_NOT_FOUND];
                        }
                    }
                }

                if (empty($username)) {
                    $username = $saml_username;
                }

                $cookie_site = preg_replace("/[^a-zA-Z0-9]/", "", $prefs['cookie_name']);
                $user_cookie_site = 'tiki-user-' . $cookie_site;
                $_SESSION["$user_cookie_site"] = $username;

                $randompass = $this->genPass();
                if (! $userTikiPresent) {
                    // Create user
                    if (empty($saml_groups)) {
                        if (isset($prefs['saml_option_default_group']) && ! empty($prefs['saml_option_default_group'])) {
                            $saml_groups[] = $prefs['saml_option_default_group'];
                        }
                    }

                    $result = $this->add_user($username, $randompass, $saml_email, '', false, null, null, null, $saml_groups);

                    if (! $result) {
                        Feedback::error(tr('The user [ %0|%1 ] is not registered with this wiki and the creation process failed.', $username, $saml_email));

                        return [false, $username, SERVER_ERROR];
                    }

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // before we log in, update the login counter
                        return [$this->update_lastlogin($username), $username, $result];
                    } elseif ($result == SERVER_ERROR) {
                        // check the notification status for this type of error
                        return [false, $username, $result];
                    }
                    // otherwise don't log in.
                    return [false, $username, $result];
                }
                // Update user
                    if ($username != 'admin') { // Prevent change groups of the admin account
                        if (isset($prefs['saml_options_sync_group']) && $prefs['saml_options_sync_group'] == 'y') {
                            if (! empty($saml_groups)) {
                                $this->assign_user_to_groups($username, $saml_groups);
                            }
                        }
                    }

                return [$this->update_lastlogin($username), $username, USER_VALID];
            }
        } elseif ($auth_ldap) {
            // next see if we need to check LDAP
            // check the user account
            $result = $this->validate_user_ldap($user, $pass);

            switch ($result) {
                case USER_VALID:
                    $userLdap = true;
                    $userLdapPresent = true;

                    break;

                case PASSWORD_INCORRECT:
                    $userLdapPresent = true;

                    break;
            }

            // start off easy
            // if the user is in Tiki and password is verified in LDAP, log in
            if ($userLdap && $userTikiPresent) {
                if ($userLdapPresent) {
                    # Sync again user attributes from LDAP (such as the RealName, mail and country) with user data in Tiki to prevent un-sync'ing them in a later stage
                    $this->init_ldap($user, $pass);
                    $this->ldap_sync_user_data($user, $this->ldap->get_user_attributes());
                }

                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            } elseif (! $userTikiPresent && ! $userLdapPresent) {
                // if the user wasn't found in either system, just fail

                return [false, $user, $result];
            } elseif ($userTiki && ! $userLdapPresent) {
                // if the user was logged into Tiki but not found in LDAP

                // see if we can create a new account
                if ($create_auth) {
                    // need to make this better! *********************************************************
                    $result = $this->create_user_ldap($user, $pass);

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
                    } // if the server didn't work, do something!
                    elseif ($result == SERVER_ERROR) {
                        // check the notification status for this type of error
                        return [false, $user, $result];
                    } // otherwise don't log in.
                     
                    return [false, $user, $result];
                }
                // otherwise
                // just say no!
                return [false, $user, $result];
            } elseif ($userLdap && ! $userTikiPresent) {
                // if the user was logged into Auth but not found in Tiki
                // see if we are allowed to create a new account
                if ($ldap_create_tiki) {
                    $ldap_user_attr = $this->ldap->get_user_attributes();
                    // Get user attributes such as the real name, email and country from the data received by the ldap auth
                    $this->ldap_sync_user_data($user, $ldap_user_attr);
                    // Use what was configured in ldap admin config, otherwise assume the attribute name is "mail" as is usual
                    $email = $ldap_user_attr[empty($prefs['auth_ldap_emailattr']) ? 'mail' : $prefs['auth_ldap_emailattr']];
                    $result = $this->add_user($user, $pass, $email);
                    $this->disable_tiki_auth($user); //disable that user's password in tiki - since we use ldap

                    // if it worked ok, just log in
                    if ($result == $user) {
                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
                    } elseif ($result == SERVER_ERROR) {
                        // if the server didn't work, do something!
                        // check the notification status for this type of error
                        return [false, $user, $result];
                    }   					// otherwise don't log in.

                    return [false, $user, $result];
                }   				// otherwise
                // just say no!
                return [false, $user, $result];
            } // if the user was logged into Auth and found in Tiki (no password in Tiki user table necessary)
            elseif ($userLdap && $userTikiPresent) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            }
        } elseif ($auth_phpbb) {
            $result = $this->validate_user_phpbb($user, $pass);

            switch ($result) {
                case USER_VALID:
                    $userPhpbb = true;

                    break;

                case PASSWORD_INCORRECT:
                    $userPhpbb = false;

                    break;
            }

            // start off easy
            // if the user verified in Tiki and phpBB, log in
            if ($userPhpbb && $userTiki) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            } elseif (! $userTikiPresent && ! $userPhpbb) {
                // if the user wasn't found in either system, just fail
                return [false, $user, USER_UNKNOWN];
            } elseif ($userPhpbb && ! $userTikiPresent) {
                // if the user was logged into phpBB but not found in Tiki

                // see if we can create a new account
                if ($phpbb_create_tiki) {
                    // get the user email and then add the user to Tiki
                    $the_email = $this->phpbbauth->grabEmail($user);
                    $result = $this->add_user($user, $pass, $the_email);

                    // if it worked ok, just log in
                    if ($result == USER_VALID) {
                        // before we log in, update the login counter
                        return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
                    } // if the server didn't work, do something!
                    elseif ($result == SERVER_ERROR) {
                        // check the notification status for this type of error
                        return [false, $user, $result];
                    } // otherwise don't log in.
                     
                    return [false, $user, $result];
                }   				// otherwise
                // just say no!
                return [false, $user, $result];
            } elseif ($userTikiPresent && ! $userPhpbb) {
                // if the user was found in Tiki, but not found in phpBB, we should probably disable the user
                if ($phpbb_disable_tikionly) {
                    // would probably be better do flag the user as not active? How do you do that?
                    // and it also would be better to check if the user is active first.. :)
                    $this->invalidate_account($user);
                    $logslib = TikiLib::lib('logs');
                    $logslib->add_log('auth_phpbb', 'NOTICE: Invalidated user ' . $user . ' due to missing phpBB account.');
                }

                return [false, $user, ACCOUNT_DISABLED];
            } // if the user was logged into phpBB and found in Tiki (no password in Tiki user table necessary)
            elseif ($userPhpbb && $userTikiPresent) {
                return [$this->_ldap_sync_and_update_lastlogin($user, $pass), $user, $result];
            }
        }

        // we will never get here
        return [false, $user, $result];
    }

    // validate the user through PAM
    public function validate_user_pam($user, $pass)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');

        // just make sure we're supposed to be here
        if ($prefs['auth_method'] != 'pam') {
            return false;
        }

        // Read page AuthPAM at tw.o, it says about a php module required.
        // maybe and if extension line could be added here... module requires $error
        // as reference.
        $error = '';
        if (pam_auth($user, $pass, $error)) {
            return USER_VALID;
        }
        // Uncomment the following to see errors on that
        // error_log("TIKI ERROR PAM: $error User: $user Pass: $pass");
        return PASSWORD_INCORRECT;
    }

    public function check_cas_authentication($user_cookie_site)
    {
        global $prefs, $webdav_access;
        $tikilib = TikiLib::lib('tiki');

        // Avoid CAS authentication check if the client is not able to handle HTTP redirects to another domain. This includes:
        //  - WebDAV requests
        //  - Javascript/AJAX requests
        //
        if ((isset($webdav_access) && $webdav_access === true)
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        ) {
            return true;
        }

        // just make sure we're supposed to be here
        if (! $this->_init_cas_client()) {
            return false;
        }

        if (! empty($_SESSION['phpCAS']['user'])) {
            $_SESSION[$user_cookie_site] = strtolower($_SESSION['phpCAS']['user']);
        }

        if (isset($_REQUEST['ticket']) && empty($_SESSION[$user_cookie_site])) {
            $cas_user = '';
            $_SESSION['cas_is_validating'] = false;
            $this->validate_user_cas($cas_user, true);
            die();
        }

        // Check for CAS (re-)validation
        //  Only if :
        //   - using CAS auth method
        //   - not calling tiki-login.php nor tiki-logout.php
        //   - not using 'admin' user
        //   - the request is not a POST ( which does not keep its params with CAS redirections )
        //   - either the CAS validation timed out or the validation process has not ended within 5 seconds which often means that the redirection to the CAS server failed
        //
        if (php_sapi_name() !== 'cli'
            && (isset($_SESSION[$user_cookie_site]) || $prefs['cas_autologin'] == 'y')
            && basename($_SERVER['SCRIPT_NAME']) != 'tiki-login.php'
            && basename($_SERVER['SCRIPT_NAME']) != 'tiki-logout.php'
            && (! isset($_SESSION[$user_cookie_site]) || $_SESSION[$user_cookie_site] != 'admin')
            && empty($_POST)
            && (($prefs['cas_authentication_timeout'] && $tikilib->now - $_SESSION['cas_validation_time'] > $prefs['cas_authentication_timeout'])
                || (isset($_SESSION['cas_is_validating']) && $_SESSION['cas_is_validating'] === true && $tikilib->now - $_SESSION['cas_validation_time'] > 5))
        ) {
            unset($_SESSION["$user_cookie_site"]);
            unset($_SESSION['phpCAS']['user']);

            $_SESSION['cas_validation_time'] = $tikilib->now;
            $_SESSION['cas_is_validating'] = true;
            $cas_user = '';

            // phpCAS will always redirect to CAS validate URL
            $this->validate_user_cas($cas_user, true);

            die();
        }
    }

    public function _init_cas_client()
    {
        global $prefs;

        // just make sure we're supposed to be here
        if ($prefs['auth_method'] != 'cas') {
            return false;
        }
        if (self::$cas_initialized === false) {
            // initialize phpCAS
            phpCAS::client($prefs['cas_version'], '' . $prefs['cas_hostname'], (int) $prefs['cas_port'], '' . $prefs['cas_path'], false);
            self::$cas_initialized = true;
        }

        return true;
    }

    // validate the user through CAS
    public function validate_user_cas(&$user, $checkOnly = false)
    {
        global $prefs, $base_url;
        $tikilib = TikiLib::lib('tiki');

        // just make sure we're supposed to be here
        if (! $this->_init_cas_client()) {
            return false;
        }

        // Redirect to this URL after authentication
        if (! empty($prefs['cas_extra_param']) && basename($_SERVER['SCRIPT_NAME']) == 'tiki-login.php') {
            phpCAS::setFixedServiceURL($base_url . 'tiki-login.php?cas=y&' . $prefs['cas_extra_param']);
        }

        // check CAS authentication
        phpCAS::setNoCasServerValidation();
        if ($checkOnly) {
            unset($_SESSION['phpCAS']['auth_checked']);
            $auth = phpCAS::checkAuthentication();
        } else {
            $auth = phpCAS::forceAuthentication();
        }
        $_SESSION['cas_validation_time'] = $tikilib->now;

        // at this step, the user has been authenticated by the CAS server
        // and the user's login name can be read with phpCAS::getUser().
        if ($auth && ($user = strtolower(phpCAS::getUser()))) {
            return USER_VALID;
        }
        $user = null;

        return PASSWORD_INCORRECT;
    }

    /**
     * Get php-saml auth object
     * @param mixed $user_cookie_site
     */
    public function check_saml_authentication($user_cookie_site)
    {
        global $prefs, $base_url;

        if ($prefs['auth_method'] != 'saml' || ! class_exists('\OneLogin\Saml2\Auth')) {
            return;
        }

        $clicked_on_saml_link = false;

        // Check endpoints
        if (array_key_exists('auth', $_REQUEST) && $_REQUEST['auth'] == 'saml') {
            $saml_instance = $this->get_saml_auth();
            $saml_instance->login();
        } elseif (array_key_exists('saml_metadata', $_REQUEST)) {
            $samlSettingsInfo = $this->get_saml_settings();
            $saml_settings = new OneLogin_Saml2_Settings($samlSettingsInfo, true);
            $metadata = $saml_settings->getSPMetadata();
            $errors = $saml_settings->validateMetadata($metadata);
            if (empty($errors)) {
                header('Content-Type: text/xml');
                echo $metadata;
                exit();
            }

            throw new OneLogin_Saml2_Error(
                'Invalid SP metadata: ' . implode(', ', $errors),
                OneLogin_Saml2_Error::METADATA_SP_INVALID
            );
        } elseif (array_key_exists('saml_acs', $_REQUEST)) {
            $clicked_on_saml_link = true;
            $saml_instance = $this->get_saml_auth();

            try {
                $saml_instance->processResponse();
            } catch (Exception $e) {
                Feedback::error($e->getMessage());

                return;
            }
            $errors = $saml_instance->getErrors();
            if (! empty($errors)) {
                Feedback::error(implode(', ', $errors));

                return;
            }
            if (! $saml_instance->isAuthenticated()) {
                Feedback::error(tra("SAML Login failed. User not authenticated"));

                return;
            }

            $_SESSION['samlUserdata'] = $saml_instance->getAttributes();
            $_SESSION['samlNameId'] = $saml_instance->getNameId();
            $_SESSION['samlSessionIndex'] = $saml_instance->getSessionIndex();
        /*
                    if (isset($_POST['RelayState']) && OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState']) {
                        $saml_instance->redirectTo($_POST['RelayState']);
                    }
        */
        } elseif (array_key_exists('saml_sls', $_REQUEST)) {
            $saml_instance = $this->get_saml_auth();

            try {
                $saml_instance->processSLO(false);
            } catch (Exception $e) {
                Feedback::error($e->getMessage());

                return;
            }
            $errors = $saml_instance->getErrors();
            if (! empty($errors)) {
                Feedback::error(implode(', ', $errors));

                return;
            }
            unset($_SESSION['samlUserdata']);
            unset($_SESSION['samlNameId']);
            unset($_SESSION['samlSessionIndex']);
        }

        $already_logged_as_admin = isset($_SESSION["$user_cookie_site"]) && $_SESSION["$user_cookie_site"] == 'admin';
        $force_saml_login = ! (isset($prefs['saml_options_skip_admin']) && $prefs['saml_options_skip_admin'] == 'y');

        if ($clicked_on_saml_link || ($force_saml_login && ! $already_logged_as_admin)) {
            $this->validate_user("", "", "", "");
        }
    }

    /**
     * Get php-saml auth object
     */
    public function get_saml_auth()
    {
        $samlSettingsInfo = $this->get_saml_settings();

        if (! class_exists('\OneLogin\Saml2\Auth')) {
            return;
        }

        try {
            $auth = new Saml2\Auth($samlSettingsInfo);
        } catch (Exception $e) {
            print_r("There is a problem with the SAML settings, review them: " . $e->getMessage());
            exit();
        }

        return $auth;
    }

    /**
     * Build a settingsInfo array based on SAML settings store at Tiki
     */
    public function get_saml_settings()
    {
        global $prefs;
        global $base_url;

        $samlSettingsInfo = [
            'strict' => isset($prefs['saml_advanced_strict']) && $prefs['saml_advanced_strict'] == 'y' ? true : false,
            'debug' => isset($prefs['saml_advanced_debug']) && $prefs['saml_advanced_debug'] == 'y' ? true : false,
            'sp' => [
                'entityId' => (! empty($prefs['saml_advanced_sp_entity_id']) ? $prefs['saml_advanced_sp_entity_id'] : 'php-saml'),
                'assertionConsumerService' => [
                    'url' => $base_url . 'tiki-login.php?saml_acs'
                ],
                'singleLogoutService' => [
                    'url' => $base_url . 'tiki-login.php?saml_sls'
                ],
                'NameIDFormat' => (! empty($prefs['saml_advanced_nameidformat']) ? $prefs['saml_advanced_nameidformat'] : 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'),
                'x509cert' => isset($prefs['saml_advanced_sp_x509cert']) ? $prefs['saml_advanced_sp_x509cert'] : '',
                'privateKey' => isset($prefs['saml_advanced_sp_privatekey']) ? $prefs['saml_advanced_sp_privatekey'] : '',
            ],
            'idp' => [
                'entityId' => isset($prefs['saml_idp_entityid']) ? $prefs['saml_idp_entityid'] : '',
                'singleSignOnService' => [
                    'url' => isset($prefs['saml_idp_sso']) ? $prefs['saml_idp_sso'] : '',
                ],
                'singleLogoutService' => [
                    'url' => isset($prefs['saml_idp_slo']) ? $prefs['saml_idp_slo'] : '',
                ],
                'x509cert' => isset($prefs['saml_idp_x509cert']) ? $prefs['saml_idp_x509cert'] : '',
                'lowercaseUrlencoding' => isset($prefs['saml_advanced_idp_lowercase_url_encoding']) && $prefs['saml_advanced_idp_lowercase_url_encoding'] == 'y' ? true : false,
            ],
            'security' => [
                'signMetadata' => isset($prefs['saml_advanced_metadata_signed']) && $prefs['saml_advanced_metadata_signed'] == 'y' ? true : false,
                'nameIdEncrypted' => isset($prefs['saml_advanced_nameid_encrypted']) && $prefs['saml_advanced_nameid_encrypted'] == 'y' ? true : false,
                'authnRequestsSigned' => isset($prefs['saml_advanced_authn_request_signed']) && $prefs['saml_advanced_authn_request_signed'] == 'y' ? true : false,
                'logoutRequestSigned' => isset($prefs['saml_advanced_logout_request_signed']) && $prefs['saml_advanced_logout_request_signed'] == 'y' ? true : false,
                'logoutResponseSigned' => isset($prefs['saml_advanced_logout_response_signed']) && $prefs['saml_advanced_logout_response_signed'] == 'y' ? true : false,
                'wantMessagesSigned' => isset($prefs['saml_advanced_want_message_signed']) && $prefs['saml_advanced_want_message_signed'] == 'y' ? true : false,
                'wantAssertionsSigned' => isset($prefs['saml_advanced_want_assertion_signed']) && $prefs['saml_advanced_want_assertion_signed'] == 'y' ? true : false,
                'wantAssertionsEncrypted' => isset($prefs['saml_advanced_want_assertion_encrypted']) && $prefs['saml_advanced_want_assertion_encrypted'] == 'y' ? true : false,
                'requestedAuthnContext' => isset($prefs['saml_advanced_requestedauthncontext']) && $prefs['saml_advanced_requestedauthncontext'],
                'signatureAlgorithm' => isset($prefs['saml_advanced_sign_algorithm']) ? $prefs['saml_advanced_sign_algorithm'] : 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
            ]
        ];

        return $samlSettingsInfo;
    }


    /**
     * Initiates the Tiki LDAP library.
     *
     * Passes it a set of options according to Tiki's preferences.
     * FIXME: a similar piece of code can be found at two other places in this file.
     * @param mixed $user
     * @param mixed $pass
     */
    public function init_ldap($user, $pass)
    {
        global $prefs;
        if (! isset($this->ldap)) {
            require_once('auth/ldap.php');
            $ldap_options = [
                    'host' => $prefs['auth_ldap_host'],
                    'port' => $prefs['auth_ldap_port'],
                    'useStartTls' => $prefs['auth_ldap_starttls'],
                    'useSsl' => $prefs['auth_ldap_ssl'],
                    'baseDn' => $prefs['auth_ldap_basedn'],
                    'scope' => $prefs['auth_ldap_scope'],
                    'bind_type' => $prefs['auth_ldap_type'],
                    'username' => $user,
                    'password' => $pass,
                    'userdn' => $prefs['auth_ldap_userdn'],
                    'useroc' => $prefs['auth_ldap_useroc'],
                    'userattr' => $prefs['auth_ldap_userattr'],
                    'fullnameattr' => $prefs['auth_ldap_nameattr'],
                    'emailattr' => $prefs['auth_ldap_emailattr'],
                    'countryattr' => $prefs['auth_ldap_countryattr'],
                    'groupdn' => $prefs['auth_ldap_groupdn'],
                    'groupattr' => $prefs['auth_ldap_groupattr'],
                    'groupoc' => $prefs['auth_ldap_groupoc'],
                    'groupnameattr' => $prefs['auth_ldap_groupnameatr'],
                    'groupdescattr' => $prefs['auth_ldap_groupdescatr'],
                    'groupmemberattr' => $prefs['auth_ldap_memberattr'],
                    'groupmemberisdn' => $prefs['auth_ldap_memberisdn'],
                    'usergroupattr' => $prefs['auth_ldap_usergroupattr'],
                    'groupgroupattr' => $prefs['auth_ldap_groupgroupattr'],
                    'debug' => $prefs['auth_ldap_debug']
            ];
            // print_r($ldap_options);
            $this->ldap = new TikiLdapLib($ldap_options);
        }
    }

    /**
     * Validates the user via LDAP and gets a LDAP connection
     *
     * @param user: username
     * @param pass: password
     * @param mixed $user
     * @param mixed $pass
     */
    public function validate_user_ldap($user, $pass)
    {
        if (! $pass) { // An LDAP password cannot be empty. Treat specially so that Tiki does *NOT* unintentionally request an unauthenticated bind.
            return PASSWORD_INCORRECT;
        }

        global $prefs;
        $logslib = TikiLib::lib('logs');

        if ($prefs['auth_ldap_debug'] == 'y') {
            $logslib->add_log('ldap', 'UserLib::validate_user_ldap()');
        }

        // First connection on the ldap server in anonymous, now we can search the real name of the $user
        // It's required to pass in param the username & password because the username is used to determine the realname (dn)
        $this->init_ldap($user, $pass);

        $err = $this->ldap->bind();

        // Change the default bind_type to use the full, call get_user_attributes function to use the realname (dn) in the credentials test
        $this->ldap->setOption('bind_type', 'full');
        $this->ldap->get_user_attributes();

        // Credentials test! To test it we force the reconnection.
        $err = $this->ldap->bind(true);

        switch ($err) {
            case LdapException::LDAP_INVALID_CREDENTIALS:
                return PASSWORD_INCORRECT;

            case LdapException::LDAP_INVALID_SYNTAX:
            case LdapException::LDAP_NO_SUCH_OBJECT:
            case LdapException::LDAP_INVALID_DN_SYNTAX:
                return USER_NOT_FOUND;

            case LdapException::LDAP_SUCCESS:
                if ($prefs['auth_ldap_debug'] == 'y') {
                    $logslib->add_log('ldap', 'Bind successful.');
                }

                return USER_VALID;

            default:
                return SERVER_ERROR;
        }

        // this should never happen
        die('Assertion failed ' . __FILE__ . ':' . __LINE__);
    }

    // validate the user from a phpBB database
    public function validate_user_phpbb($user, $pass)
    {
        require_once('auth/phpbb.php');
        $this->phpbbauth = new TikiPhpBBLib();

        switch ($this->phpbbauth->check($user, $pass)) {
            case PHPBB_INVALID_CREDENTIALS:
                return PASSWORD_INCORRECT;

                break;

            case PHPBB_INVALID_SYNTAX:
            case PHPBB_NO_SUCH_USER:
                return USER_NOT_FOUND;

                break;

            case PHPBB_SUCCESS:
                //$logslib->add_log('phpbb','PhpBB user validation successful.');
                return USER_VALID;

                break;

            default:
                return SERVER_ERROR;
        }
        // this should never happen
        die('Assertion failed ' . __FILE__ . ':' . __LINE__);
    }

    /**
     * Help function to disable a user's password.
     *
     * Used, whenever the user password shall not be
     * hold in the tiki db but in LDAP or somewhere else.
     * @param mixed $user
     */
    public function disable_tiki_auth($user)
    {
        global $tiki, $prefs;

        if ($prefs['auth_ldap_debug'] == 'y') {
            TikiLib::lib('logs')->add_log('ldap', 'UserLib::disable_tiki_auth()');
        }
        $query = 'update `users_users` set `hash`=? where binary `login` = ?';
        $result = $this->query($query, ['', $user]);
    }

    /**
     * Synchronizes all existing Tiki users to what is in the LDAP directory.
     *
     * Retrieves all users info from LDAP.
     * Creates the corresponding Tiki users from this data.
     */
    public function ldap_sync_all_users()
    {
        global $prefs;
        $logslib = TikiLib::lib('logs');

        if ($prefs['syncUsersWithDirectory'] != 'y') {
            return false;
        }

        require_once('auth/ldap.php');
        if ($prefs['auth_ldap_debug'] == 'y') {
            $logslib->add_log('ldap', 'UsersLib::ldap_sync_all_users(): Syncing all Tiki users to LDAP');
        }

        $bind_type = 'default';

        switch ($prefs['auth_ldap_type']) { // Must be anonymous or admin
            case 'default':
                break;

            default:
                if (! empty($prefs['auth_ldap_adminuser'])) {
                    $bind_type = 'explicit';

                    break;
                }

                return false;

                break;
        }

        // FIXME: Similar to the contents of the init_ldap method:
        $ldap_options = [
                    'host' => $prefs['auth_ldap_host'],
                    'port' => $prefs['auth_ldap_port'],
                    'useStartTls' => $prefs['auth_ldap_starttls'],
                    'useSsl' => $prefs['auth_ldap_ssl'],
                    'baseDn' => $prefs['auth_ldap_basedn'],
                    'scope' => $prefs['auth_ldap_scope'],
                    'bind_type' => $bind_type,
                    'binddn' => $prefs['auth_ldap_adminuser'],
                    'bindpw' => $prefs['auth_ldap_adminpass'],
                    'userdn' => $prefs['auth_ldap_userdn'],
                    'useroc' => $prefs['auth_ldap_useroc'],
                    'userattr' => $prefs['auth_ldap_userattr'],
                    'fullnameattr' => $prefs['auth_ldap_nameattr'],
                    'emailattr' => $prefs['auth_ldap_emailattr'],
                    'countryattr' => $prefs['auth_ldap_countryattr'],
                    'groupdn' => $prefs['auth_ldap_groupdn'],
                    'groupattr' => $prefs['auth_ldap_groupattr'],
                    'groupoc' => $prefs['auth_ldap_groupoc'],
                    'groupnameattr' => $prefs['auth_ldap_groupnameatr'],
                    'groupdescattr' => $prefs['auth_ldap_groupdescatr'],
                    'groupmemberattr' => $prefs['auth_ldap_memberattr'],
                    'groupmemberisdn' => $prefs['auth_ldap_memberisdn'],
                    'usergroupattr' => $prefs['auth_ldap_usergroupattr'],
                    'groupgroupattr' => $prefs['auth_ldap_groupgroupattr'],
                    'debug' => $prefs['auth_ldap_debug']
        ];

        $user_ldap = new TikiLdapLib($ldap_options);

        // Retrieve all users from LDAP:
        if (! ($users_attributes = $user_ldap->get_all_users_attributes())) {
            return false;
        }

        foreach ($users_attributes as $user_attributes) {
            $user = $user_attributes[$prefs['auth_ldap_userattr']];
            $this->add_user($user, '', $user);

            if ($prefs['auth_method'] == 'ldap') {
                $this->disable_tiki_auth($user);
            }

            $this->ldap_sync_user_data($user, $user_attributes);
        }
    }

    /**
     * Synchronize all groups with LDAP directory
     *
     * For each user, makes sure that user is member of the same groups as specified in their LDAP entry.
     */
    public function ldap_sync_all_groups()
    {
        global $prefs;
        if ($prefs['auth_ldap_debug'] == 'y') {
            TikiLib::lib('logs')->add_log('ldap', 'UsersLib::ldap_sync_all_groups()');
        }

        if ($prefs['syncGroupsWithDirectory'] != 'y') {
            return false;
        }

        $users = $this->list_all_users();

        foreach ($users as $user) {
            $this->_ldap_sync_groups($user, null);
        }
    }

    /**
     * Updates the info about the current Tiki user with the info found in the LDAP directory.
     *
     * @see \UsersLib::disable_tiki_auth()
     * @see \UsersLib::ldap_sync_user_data()
     * @param mixed $user
     * @param mixed $pass
     */
    public function ldap_sync_user($user, $pass)
    {
        if ($user == 'admin') {
            return true;
        }

        global $prefs;
        $logslib = TikiLib::lib('logs');
        $ret = true;
        $this->init_ldap($user, $pass);

        if ($prefs['auth_ldap_debug'] == 'y') {
            $logslib->add_log('ldap', 'Syncing user with ldap');
        }

        // sync user information
        if ($prefs['auth_method'] == 'ldap') {
            $this->disable_tiki_auth($user);
        }

        if ($prefs['syncUsersWithDirectory'] == 'y') {
            $this->ldap_sync_user_data($user, $this->ldap->get_user_attributes());
        }

        return $ret;
    }

    /**
     * Sets Tiki user fields with the values found about a given user in LDAP.
     *
     * (name, email, country)
     *
     * @param user: username
     * @param attributes: Name and value for each LDAP attribute of the user.
     * @param mixed $user
     * @param mixed $attributes
     */
    public function ldap_sync_user_data($user, $attributes)
    {
        global $prefs;

        if ($prefs['auth_ldap_debug'] == 'y') {
            TikiLib::lib('logs')->add_log('ldap', 'UsersLib::ldap_sync_user_data()');
        }
        $u = ['login' => $user];

        $userPreferenceToLdapPreferenceMap = [
            'realName' => 'auth_ldap_nameattr',
            'email' => 'auth_ldap_emailattr',
            'country' => 'auth_ldap_countryattr',
        ];

        foreach ($userPreferenceToLdapPreferenceMap as $preference => $ldapPreference) {
            if ($preference == 'email') {
                $userPreferenceValue = $this->get_user_email($user);
            } else {
                $userPreferenceValue = $this->get_user_preference($user, $preference);
            }
            $isSetLdapPreferenceValue = isset($attributes[$prefs[$ldapPreference]]);
            $ldapPreferenceValue = $isSetLdapPreferenceValue ? $attributes[$prefs[$ldapPreference]] : '';

            if ($userPreferenceValue && empty($ldapPreferenceValue)) {
                $u[$preference] = '';
            } else {
                if ($isSetLdapPreferenceValue) {
                    // Ldap attributes can (by default) have multiple values, check if the current user preference is one of
                    // the values of the attribute, in that case keep the same value
                    if (is_array($ldapPreferenceValue)
                        && $userPreferenceValue
                        && in_array($userPreferenceValue, $ldapPreferenceValue)
                    ) {
                        $u[$preference] = $userPreferenceValue;

                        continue;
                    }
                    if (is_array($ldapPreferenceValue)) {
                        // Ldap attributes can (by default) have multiple values
                        // so we always take the first from the list in case of a multi value field
                        $ldapPreferenceValue = reset($ldapPreferenceValue);
                    }
                    if ($isSetLdapPreferenceValue) {
                        $u[$preference] = $ldapPreferenceValue;
                    }
                }
            }
        }

        if (count($u) > 1) {
            $this->set_user_fields($u);
        }
    }

    /**
     * For a given user, makes sure this user is member of all the groups they should be,
     * according to their entry in the LDAP directory.
     *
     * @param user: username
     * @param pass: password (might be null)
     * @param mixed $user
     * @param mixed $pass
     */
    private function _ldap_sync_groups($user, $pass)
    {
        if ($user == 'admin') {
            return true;
        }

        global $prefs;
        $logslib = TikiLib::lib('logs');
        static $ldap_group_options = [];
        static $ext_dir = null;
        $ret = true;

        $this->init_ldap($user, $pass);
        $this->ldap->setOption('username', $user);
        $this->ldap->setOption('password', $pass);

        if ($prefs['auth_ldap_debug'] == 'y') {
            $logslib->add_log('ldap', 'UsersLib::_ldap_sync_groups(): Syncing group with ldap');
        }
        $userattributes = $this->ldap->get_user_attributes(true);

        if ($prefs['syncGroupsWithDirectory'] == 'y' && $userattributes[$prefs['auth_ldap_group_corr_userattr']] != null) {
            // sync external group information of user
            $ldapgroups = [];

            if ($prefs['auth_ldap_group_external'] == 'y') {
                // External directory for groups
                if (! isset($ext_dir)) {
                    $ldap_group_options = [
                            'host' => $prefs['auth_ldap_group_host'],
                            'port' => $prefs['auth_ldap_group_port'],
                            'useStartTls' => $prefs['auth_ldap_group_starttls'],
                            'useSsl' => $prefs['auth_ldap_group_ssl'],
                            'baseDn' => $prefs['auth_ldap_group_basedn'],
                            'scope' => $prefs['auth_ldap_group_scope'],
                            'userdn' => $prefs['auth_ldap_group_userdn'],
                            'useroc' => $prefs['auth_ldap_group_useroc'],
                            'userattr' => $prefs['auth_ldap_group_userattr'],
                            'username' => $userattributes[$prefs['auth_ldap_group_corr_userattr']],
                            'groupdn' => $prefs['auth_ldap_groupdn'],
                            'groupattr' => $prefs['auth_ldap_groupattr'],
                            'groupoc' => $prefs['auth_ldap_groupoc'],
                            'groupnameattr' => $prefs['auth_ldap_groupnameatr'],
                            'groupdescattr' => $prefs['auth_ldap_groupdescatr'],
                            'groupmemberattr' => $prefs['auth_ldap_memberattr'],
                            'groupmemberisdn' => $prefs['auth_ldap_memberisdn'],
                            'usergroupattr' => $prefs['auth_ldap_usergroupattr'],
                            'groupgroupattr' => $prefs['auth_ldap_groupgroupattr'],
                            'debug' => $prefs['auth_ldap_group_debug']
                    ];

                    if (empty($prefs['auth_ldap_group_adminuser'])) {
                        // Anonymous
                        $ldap_group_options['bind_type'] = 'default';
                    } else {
                        // Explicit
                        $ldap_group_options['bind_type'] = 'explicit';
                        $ldap_group_options['binddn'] = $prefs['auth_ldap_group_adminuser'];
                        $ldap_group_options['bindpw'] = $prefs['auth_ldap_group_adminpass'];
                    }

                    $ext_dir = new TikiLdapLib($ldap_group_options);
                }

                $ext_dir->setOption('username', $userattributes[$prefs['auth_ldap_group_corr_userattr']]);
                $ldapgroups = $ext_dir->get_groups(true);
            } else {
                if (! empty($prefs['auth_ldap_adminuser'])) {
                    $this->ldap->setOption('bind_type', 'explicit');
                    $this->ldap->setOption('binddn', $prefs['auth_ldap_adminuser']);
                    $this->ldap->setOption('bindpw', $prefs['auth_ldap_adminpass']);
                    $this->ldap->bind(true);
                }

                $ldapgroups = $this->ldap->get_groups(true);

                if (! empty($prefs['auth_ldap_adminuser'])) {
                    $this->ldap->setOption('bind_type', $prefs['auth_ldap_type']);
                    $this->ldap->bind(true);
                }
            }

            $this->_ldap_sync_group_data($user, $ldapgroups);
        }

        return $ret;
    }

    /**
     * Sync Tiki groups with LDAP groups data
     *
     * For each group, assigns the user to it if it is not already a member of it.
     *
     * Called from \UsersLib::_ldap_sync_groups()
     *
     * @param user: username
     * @param ldapgroups: list of LDAP group names
     * @param mixed $user
     * @param mixed $ldapgroups
     */
    private function _ldap_sync_group_data($user, $ldapgroups)
    {
        global $prefs;
        $logslib = TikiLib::lib('logs');

        if (! count($ldapgroups)) {
            return;
        }

        $ldapgroups_simple = [];
        $tikigroups = $this->get_user_groups($user);
        foreach ($ldapgroups as $group) {
            $gname = $group[$prefs['auth_ldap_groupattr']];
            $ldapgroups_simple[] = $gname; // needed later
            if ($this->group_exists($gname) && ! $this->group_is_external($gname)) { // group exists
                //check if we need to sync group information
                if (isset($group[$prefs['auth_ldap_groupdescattr']])) {
                    $ginfo = $this->get_group_info($gname);
                    if ($group[$prefs['auth_ldap_groupdescattr']] != $ginfo['groupDesc']) {
                        $this->set_group_description($gname, $group[$prefs['auth_ldap_groupdescattr']]);
                    }
                }
            } elseif (! $this->group_exists($gname)) { // create group
                if (isset($group[$prefs['auth_ldap_groupdescattr']])) {
                    $gdesc = $group[$prefs['auth_ldap_groupdescattr']];
                } else {
                    $gdesc = '';
                }
                $logslib->add_log('ldap', 'Creating external group ' . $gname);
                $this->add_group($gname, $gdesc, '', 0, 0, '', '', 0, '', 0, 0, 'y');
            }

            // add user
            if (! in_array($gname, $tikigroups)) {
                $logslib->add_log('ldap', 'Adding user ' . $user . ' to external group ' . $gname);
                $this->assign_user_to_group($user, $gname);
            }
        }

        // now clean up group membership if user has been unassigned from a group in ldap
        $extgroups = $this->get_user_external_groups($user);
        foreach ($extgroups as $eg) {
            if (! in_array($eg, $ldapgroups_simple)) {
                $logslib->add_log('ldap', 'Removing user ' . $user . ' from external group ' . $eg);
                $this->remove_user_from_group($user, $eg);
            }
        }
    }

    /**
     * Update infos for a user, and which groups it is in, reading from LDAP.
     *
     * Called after a user has been created or logged from LDAP.
     *
     * @param user: username
     * @param pass: password
     * @param mixed $user
     * @param mixed $pass
     * @see \UserLib::_ldap_sync_user()
     * @see \UserLib::_ldap_sync_groups()
     */
    public function _ldap_sync_user_and_groups($user, $pass)
    {
        global $prefs;

        if ($prefs['auth_ldap_debug'] == 'y') {
            TikiLib::lib('logs')->add_log('ldap', 'UsersLib::_ldap_sync_user_and_groups()');
        }

        $ret = true;
        $ret &= $this->ldap_sync_user($user, $pass);
        $ret &= $this->_ldap_sync_groups($user, $pass);

        // Invalidate cache
        $cachelib = TikiLib::lib('cache');
        $cacheKey = 'user_details_' . $user;
        $cachelib->invalidate($cacheKey);

        return($ret);
    }

    public function set_group_description($group, $description)
    {
        $query = 'update `users_groups` set `groupDesc`=? where `groupName`=?';
        $result = $this->query($query, [$description, $group]);
    }

    public function group_is_external($group)
    {
        $gi = $this->get_group_info($group);
        if ($gi['isExternal'] == 'y') {
            return true;
        }

        return false;
    }

    // simple function - no group inclusion or intertiki
    public function get_user_external_groups($user)
    {
        $userid = $this->get_user_id($user);
        $query = 'select u.`groupName`' .
                        ' from `users_usergroups` u, `users_groups` g' .
                        ' where u.`groupName`=g.`groupName` and u.`userId`=? and g.`isExternal`=?';

        $result = $this->query($query, [(int) $userid, 'y']);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res['groupName'];
        }

        return $ret;
    }

    /**
     * Validate the user in the Tiki database

     * @param user: username
     * @param pass: password
     * @param mixed $user
     * @param mixed $pass
     * @param mixed $validate_phase
     */
    public function validate_user_tiki($user, $pass, $validate_phase = false)
    {
        global $prefs;

        $userUpper = TikiLib::strtoupper($user);
        // first verify that the user exists
        $query = 'select `userId`,`login`,`waiting`, `hash`, `email`,`valid` from `users_users` where upper(`login`) = ?';
        $result = $this->query($query, [$userUpper]);


        switch ($result->numRows()) {
            case 0:
                if ($prefs['login_allow_email'] == 'y') {								//if no users found, check check if email is being used to login
                    $query = 'select `userId`,`login`,`waiting`, `hash`, `email`,`valid` from `users_users` where upper(`email`) = ?';
                    $result = $this->query($query, [$userUpper]);
                    if ($result->numRows() > 1) {
                        return [EMAIL_AMBIGUOUS, $user];					// if there is more than one user with that email
                    } elseif ($result->numRows() == 1) {
                        break;													// if there is only one user, exit switch
                    }
                }

                return [USER_NOT_FOUND, $user];

            case 1:
                break;

            default:
                return [USER_AMBIGOUS, $user];
        }



        $res = $result->fetchRow();
        $user = $res['login'];

        // check for account flags
        if ($res['waiting'] === 'u') {				// if account is in validation mode.
            if (!empty($pass) && $pass === $res['valid']) { 			// if user successfully provides code from email
                return [USER_VALID, $user];
            }

            return [ACCOUNT_WAITING_USER, $user];  // if code validation fails, (or user tries to log in before verifying)
        } elseif ($res['waiting'] === 'a') {         // if account needs administrator validation
            if (!empty($res['valid']) && $pass === $res['valid']) { 			// if admin successfully validates account
                return [USER_VALID, $user];
            }

            return [ACCOUNT_DISABLED, $user];
        }

        if ($validate_phase) {
            return [USER_PREVIOUSLY_VALIDATED, $user];		// if email verification code is used an a validated account, deny.
        }


        // next verify the password with every hashes methods


        if ($res['hash'][0] == '$') {				// if password was created by crypt (old tiki hash) or password_hash (current tiki hash)
            if (password_verify($pass, $res['hash'])) {
                if (password_needs_rehash($res['hash'], PASSWORD_DEFAULT)) {
                    $this->set_user_password($res['userId'], $pass);			//if its a old hash style, rehash it in a more secure way
                }

                return [USER_VALID, $user];
            }

            return [PASSWORD_INCORRECT, $user];      // if the password was incorrect, dont give the md5's a spin
        }

        if (! empty($pass) && $res['hash'] === md5($pass)) { 								// very method md5(pass), for compatibility
            $this->set_user_password($res['userId'], $pass);

            return [USER_VALID, $user];
        }
        if (! empty($pass) && $res['hash'] === md5($user . $pass)) { 					// ancient method md5(user.pass), for compatibility
            $this->set_user_password($res['userId'], $pass);

            return [USER_VALID, $user];
        }
        if (! empty($pass) && $res['hash'] === md5($user . $pass . trim($res['email']))) { // very ancient method md5(user.pass.email), for compatibility
            $this->set_user_password($res['userId'], $pass);

            return [USER_VALID, $user];
        }

        return [PASSWORD_INCORRECT, $user];
    }


    /**
     * Stores a users password in the database
     *
     * @param userId: the id of the user.
     * @param pass: the clear text password to be hashed and stored
     * @param mixed $userId
     * @param mixed $pass
     */
    private function set_user_password($userId, $pass)
    {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $query = 'update `users_users` set `hash`=? where `userId`=?';
        $result = $this->query($query, [$hash, $userId]);

        //todo: a little error checking would be nice.
    }

    /**
     * Synchronizes Tiki user and group info from LDAP.
     *
     * @param user: User name.
     * @param pass: Password.
     * @param mixed $user
     * @param mixed $pass
     */
    private function _ldap_sync_and_update_lastlogin($user, $pass)
    {
        global $prefs;
        global $tikilib;

        if ($prefs['auth_ldap_debug'] == 'y') {
            TikiLib::lib('logs')->add_log('ldap', 'UsersLib::_ldap_sync_and_update_lastlogin()');
        }

        $ret = $this->update_lastlogin($user);

        if (empty($current)) {
            // First time
            $current = 0;
        }

        if ($prefs['auth_method'] === 'ldap' && ($prefs['syncGroupsWithDirectory'] == 'y' || $prefs['syncUsersWithDirectory'] == 'y')) {
            $ret &= $this->_ldap_sync_user_and_groups($user, $pass);
        }

        return $ret;
    }

    /**
     * Updates date and time of current and last (previous) login.
     *
     * Called when the user logs in.
     * The updated fields are: currentLogin and lastLogin.
     * Resets unsuccessful_logins field.
     *
     * @param user: Username
     * @param mixed $user
     */
    public function update_lastlogin($user)
    {
        $previous = $this->getOne('select `currentLogin` from `users_users` where `login`= ?', [$user]);
        if (is_null($previous)) {
            // First login
            $previous = $this->now; // TODO: Should we really set lastLogin on the first login?
        }

        $query = 'update `users_users` set `lastLogin`=?, `currentLogin`=?, `unsuccessful_logins`=? where `login`=? and (`waiting` <> \'a\' OR `waiting` IS NULL)';	// don't update last login if waiting for admin
        $this->query(
            $query,
            [
                (int)$previous,
                (int)$this->now,
                0,
                $user
            ]
        );

        return true;
    }

    /**
     * Creates a new user in the LDAP directory
     *
     * @param user: username
     * @param pass: password
     * @param mixed $user
     * @param mixed $pass
     */
    public function create_user_ldap($user, $pass)
    {
        // todo: no more pear::auth! all in pear::ldap2
        global $prefs;
        $tikilib = TikiLib::lib('tiki');

        $options = [];
        $options['url'] = $prefs['auth_ldap_url'];
        $options['host'] = $prefs['auth_ldap_host'];
        $options['port'] = $prefs['auth_ldap_port'];
        $options['scope'] = $prefs['auth_ldap_scope'];
        $options['baseDn'] = $prefs['auth_ldap_basedn'];
        $options['userdn'] = $prefs['auth_ldap_userdn'];
        $options['userattr'] = $prefs['auth_ldap_userattr'];
        $options['useroc'] = $prefs['auth_ldap_useroc'];
        $options['groupdn'] = $prefs['auth_ldap_groupdn'];
        $options['groupattr'] = $prefs['auth_ldap_groupattr'];
        $options['groupoc'] = $prefs['auth_ldap_groupoc'];
        $options['memberattr'] = $prefs['auth_ldap_memberattr'];
        $options['memberisdn'] = ($prefs['auth_ldap_memberisdn'] == 'y');
        $options['binduser'] = $prefs['auth_ldap_adminuser'];
        $options['bindpw'] = $prefs['auth_ldap_adminpass'];

        // set additional attributes here
        $userattr = [];
        $userattr['email'] = ($prefs['login_is_email'] == 'y')
                                                ? $user
                                                : $this->getOne('select `email` from `users_users` where `login`=?', [$user]);


        // set the Auth options
        $a = new Auth('LDAP', $options);

        // check if the login correct
        if ($a->addUser($user, $pass, $userattr) === true) {
            $status = USER_VALID;
        } else {
            // otherwise use the error status given back
            $status = $a->getStatus();
        }

        return $status;
    }


    /**
    * This is a lighter version of get_users_names designed for AJAX checking of userrealnames
    * @param mixed $offset
    * @param mixed $maxRecords
    * @param mixed $sort_mode
    * @param mixed $find
    * @param mixed $group
    */
    public function get_users_light($offset = 0, $maxRecords = -1, $sort_mode = 'login_asc', $find = '', $group = '')
    {
        global $prefs, $tiki_p_list_users, $tiki_p_admin;

        if ($tiki_p_list_users !== 'y' && $tiki_p_admin != 'y') {
            return [];
        }

        $mid = '';
        $bindvars = [];
        if (! empty($group)) {
            if (! is_array($group)) {
                $group = [$group];
            }
            $mid = ', `users_usergroups` uug where uu.`userId`=uug.`userId` and uug.`groupName` in (' .
                            implode(',', array_fill(0, count($group), '?')) . ')';

            $bindvars = $group;
        }
        if (! empty($find)) {
            $findesc = '%' . $find . '%';
            if (empty($mid)) {
                $mid .= ' where uu.`login` like ?';
            } else {
                $mid .= ' and uu.`login` like ?';
            }
            $bindvars[] = $findesc;
        }

        $query = "select uu.`login` from `users_users` uu $mid order by " . $this->convertSortMode($sort_mode);
        $result = $this->fetchAll($query, $bindvars, $maxRecords, $offset);

        $ret = [];

        foreach ($result as $res) {
            $ret[$res['login']] = $this->clean_user($res['login']);
        }

        if (! empty($findesc) && $prefs['user_show_realnames'] == 'y') {
            $query = "select `user` from `tiki_user_preferences` where `prefName` = 'realName' and `value` like ?";
            $result = $this->fetchAll($query, [$findesc], $maxRecords, $offset);
            foreach ($result as $res) {
                if (! isset($ret[$res['user']])) {
                    $ret[$res['user']] = $this->clean_user($res['user']);
                }
            }
        }
        asort($ret);

        return($ret);
    }

    public function get_users_names($offset = 0, $maxRecords = -1, $sort_mode = 'login_asc', $find = '')
    {
        global $tiki_p_list_users, $tiki_p_admin;

        if ($tiki_p_list_users !== 'y' && $tiki_p_admin != 'y') {
            return [];
        }

        // This function gets an array of user login names.
        if (! empty($find)) {
            $findesc = '%' . $find . '%';
            $mid = ' where `login` like ?';
            $bindvars = [$findesc];
        } else {
            $mid = '';
            $bindvars = [];
        }

        $query = "select `login` from `users_users` $mid order by " . $this->convertSortMode($sort_mode);
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res['login'];
        }

        return ($ret);
    }

    public function get_members($group)
    {
        $group_results = true;
        if (! is_array($group)) {
            $group = [$group];
            $group_results = false;
        } elseif (count($group) == 0) {
            return [];
        }
        $users = $this->fetchAll('SELECT ug.groupName, u.login FROM `users_usergroups` ug INNER JOIN `users_users` u ON u.userId = ug.userId WHERE ug.groupName IN ('
            . implode(',', array_fill(0, count($group), '?')) . ')', $group);

        if (! $group_results) {
            return array_map(function ($row) {
                return $row['login'];
            }, $users);
        } else {
            $grouped = [];
            foreach ($users as $row) {
                $grouped[$row['groupName']][] = $row['login'];
            }

            return $grouped;
        }
    }

    public function get_users(
        $offset = 0,
        $maxRecords = -1,
        $sort_mode = 'login_asc',
        $find = '',
        $initial = '',
        $inclusion = false,
        $group = '',
        $email = '',
        $notconfirmed = false,
        $notvalidated = false,
        $neverloggedin = false
    ) {
        $hasPermission = function ($group) {
            $perms = Perms::get(['type' => 'group', 'object' => $group]);
            if (! $perms->group_view_members && ! $perms->list_users && ! $perms->admin_users) {
                return false;
            }

            return true;
        };

        if (is_array($group)) {
            $group = array_filter($group, $hasPermission);

            if (empty($group)) {
                return [];
            }
        } elseif (! $hasPermission($group)) {
            return [];
        }

        $mid = '';
        $bindvars = [];
        $mmid = '';
        $mbindvars = [];
        // Return an array of users indicating name, email, last changed pages, versions, lastLogin

        //TODO : recurse included groups
        if (! empty($group)) {
            if (! is_array($group)) {
                $group = [$group];
            }
            $mid = ', `users_usergroups` uug where uu.`userId`=uug.`userId` and uug.`groupName` in (' . implode(',', array_fill(0, count($group), '?')) . ')';
            $mmid = $mid;
            $bindvars = $group;
            $mbindvars = $bindvars;
        }
        if (! empty($email)) {
            $mid .= $mid == '' ? ' where' : ' and';
            $mid .= ' uu.`email` like ?';
            $mmid = $mid;
            $bindvars[] = '%' . $email . '%';
            $mbindvars[] = '%' . $email . '%';
        }

        if (! empty($find)) {
            $mid .= $mid == '' ? ' where' : ' and';
            $mid .= ' uu.`login` like ?';
            $mmid = $mid;
            $bindvars[] = '%' . $find . '%';
            $mbindvars[] = '%' . $find . '%';
        }

        if (! empty($initial)) {
            $mid = ' where `login` like ?';
            $mmid = $mid;
            $bindvars = [$initial . '%'];
            $mbindvars = $bindvars;
        }

        if ($notconfirmed && $notvalidated) {
            $mid .= $mid == '' ? ' where' : ' and';
            $mid .= ' (uu.`waiting` = \'u\' or uu.`waiting` = \'a\')';
            $mmid = $mid;
        } else {
            if ($notconfirmed) {
                $mid .= $mid == '' ? ' where' : ' and';
                $mid .= ' uu.`waiting` = \'u\'';
                $mmid = $mid;
            }

            if ($notvalidated) {
                $mid .= $mid == '' ? ' where' : ' and';
                $mid .= ' uu.`waiting` = \'a\'';
                $mmid = $mid;
            }
        }

        if ($neverloggedin) {
            $mid .= $mid == '' ? ' where' : ' and';
            $mid .= ' (uu.`lastLogin` is null or uu.`lastLogin` = 0)';
            $mmid = $mid;
        }
        $query = "select uu.* from `users_users` uu $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `users_users` uu $mmid";
        $ret = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $mbindvars);

        foreach ($ret as &$res) {
            if (! $perms->admin_users) {
                // Filter out sensitive data
                unset($res['email']);
                unset($res['hash']);
                unset($res['provpass']);
            }

            $res['user'] = $res['login'];
            $user = $res['user'];

            if ($inclusion) {
                $groups = $this->get_user_groups_inclusion($user);
            } else {
                $groups = $this->get_user_groups($user);
            }

            $res['groups'] = $groups;
            $res['age'] = $this->now - $res['registrationDate'];
            $res['user_information'] = $this->get_user_preference($user, 'user_information', 'public');
            $res['editable'] = $this->user_can_be_edited($user);
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    /**
     * @param string $edited_user : username (login) of the user that might be edited
     * @param string $editing_user : username of user doing the editing (or logged-in user if omitted)
     * @return bool : true if $editing_user can edit $edited_user
     */
    public function user_can_be_edited($edited_user, $editing_user = '')
    {
        global $user;

        if (empty($editing_user)) {
            $editing_user = $user;
        }

        $editable = false;
        if ($this->user_has_permission($editing_user, 'tiki_p_admin')) {
            $editable = true;
        } elseif ($this->user_has_permission($editing_user, 'tiki_p_admin_users') && ! $this->user_has_permission($edited_user, 'tiki_p_admin')) {
            $editable = true;
        }

        return $editable;
    }

    public function group_inclusion($group, $include)
    {
        $query = 'insert into `tiki_group_inclusion`(`groupName`,`includeGroup`) values(?,?)';
        $result = $this->query($query, [$group, $include]);
    }

    public function get_included_container_groups($group, $recur = true)
    {
        $includedGroups = $this->get_included_groups($group, $recur);
        $groups = $this->get_group_info($includedGroups);

        return array_filter($groups, function ($item) {
            return $item["isTplGroup"] == "y";
        });
    }


    public function get_included_groups($group, $recur = true)
    {
        $engroup = urlencode($group);
        if (! $recur || ! isset($this->groupinclude_cache[$engroup])) {
            $query = 'select `includeGroup` from `tiki_group_inclusion` where `groupName`=?';
            $result = $this->query($query, [$group]);
            $ret = [];

            while ($res = $result->fetchRow()) {
                $ret[] = $res['includeGroup'];
                if ($recur) {
                    $ret2 = $this->get_included_groups($res['includeGroup']);
                    $ret = array_merge($ret, $ret2);
                }
            }

            $back = array_unique($ret);

            if ($recur) {
                $this->groupinclude_cache[$engroup] = $back;
            }

            return $back;
        }

        return $this->groupinclude_cache[$engroup];
    }
    public function get_including_groups($group, $recur = 'n')
    {
        $query = 'select `groupName` from `tiki_group_inclusion` where `includeGroup`=? order by `groupName`';
        $result = $this->query($query, [$group]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res['groupName'];
            if ($recur == 'y') {
                $ret = array_merge($ret, $this->get_including_groups($res['groupName']));
            }
        }
        if ($recur == 'y') {
            sort($ret);
        }

        return $ret;
    }
    public function user_is_in_group($user, $group)
    {
        $user_groups = $this->get_user_groups($user);
        if (in_array($group, $user_groups)) {
            return true;
        }

        return false;
    }

    /**
     * @param      $user
     * @param      $group
     * @param bool $bulk
     *
     * @throws Exception
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_user_from_group($user, $group, $bulk = false)
    {
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $cachelib = TikiLib::lib('cache');

        $cachelib->invalidate('user_details_' . $user);
        $tikilib->invalidate_usergroups_cache($user);
        $this->invalidate_usergroups_cache($user); // this is needed as cache is present in this instance too

        $userid = $this->get_user_id($user);

        $query = 'delete from `users_usergroups` where `userId` = ? and `groupName` = ?';
        $result = $this->query($query, [$userid, $group]);

        $query = 'update `users_users` set `default_group`=? where `login`=? and `default_group`=?';
        $this->query($query, ['Registered', $user, $group]);

        TikiLib::events()->trigger('tiki.user.groupleave', [
            'type' => 'user',
            'object' => $user,
            'group' => $group,
            'bulk_import' => $bulk,
        ]);

        $_SESSION['u_info']['group'] = 'Registered';

        return $result;
    }

    public function remove_user_from_all_groups($user)
    {
        global $prefs;
        $userid = $this->get_user_id($user);
        $query = 'delete from `users_usergroups` where `userId` = ?';
        $result = $this->query($query, [$userid]);
        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);
    }

    public function get_groups_userchoice()
    {
        $ret = [];
        $groups = $this->get_groups(0, -1, '', '', '', 'n', '', 'y');
        foreach ($groups['data'] as $g) {
            $ret[] = $g['groupName'];
        }

        return $ret;
    }

    public function get_groups_for_permissions()
    {
        $query = "SELECT * from users_groups 
					where isTplGroup <> 'y' and groupName not in (select groupName 
					from  tiki_group_inclusion where includeGroup in (SELECT groupName 
					from users_groups where isTplGroup = 'y')) order by id asc;";
        $ret = $this->fetchAll($query);
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = count($ret);

        return $retval;
    }

    public function get_template_groups_containers()
    {
        $query = "SELECT * from users_groups 
					where isTplGroup = 'y' order by id asc;";
        $ret = $this->fetchAll($query);
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = count($ret);

        return $retval;
    }

    public function get_group_children($groupName)
    {
        $query = "SELECT DISTINCT *  from users_groups as ug
					where ug.groupName  in (select groupName from  tiki_group_inclusion where includeGroup = ?) order by id asc;";
        $ret = $this->fetchAll($query, [$groupName]);
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = count($ret);

        return $retval;
    }

    public function get_group_children_with_permissions($groupName)
    {
        $query = "SELECT DISTINCT ug.*  from users_groups as ug join users_grouppermissions as up on ug.groupName = up.groupName
					where ug.groupName  in (select groupName from  tiki_group_inclusion where includeGroup = ?) order by id asc;";
        $ret = $this->fetchAll($query, [$groupName]);
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = count($ret);

        return $retval;
    }

    public function get_groups($offset = 0, $maxRecords = -1, $sort_mode = 'groupName_asc', $find = '', $initial = '', $details = "y", $inGroups = '', $userChoice = '')
    {
        $mid = '';
        $bindvars = [];
        if ($find) {
            $mid = ' where `groupName` like ?';
            $bindvars[] = '%' . $find . '%';
        }

        if ($initial) {
            $mid = ' where `groupName` like ?';
            $bindvars = [$initial . '%'];
        }

        if ($inGroups) {
            $mid .= $mid ? ' and ' : ' where ';
            $mid .= '`groupName` in (';
            $cpt = 0;
            foreach ($inGroups as $grp => $value) {
                if ($cpt++) {
                    $mid .= ',';
                }
                $mid .= '?';
                $bindvars[] = $grp;
            }
            $mid .= ')';
        }

        if ($userChoice) {
            $mid .= $mid ? ' and ' : ' where ';
            $mid .= "`userChoice` = 'y'";
        }

        $query = "select * from `users_groups` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `users_groups` $mid";
        $ret = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);

        foreach ($ret as &$res) {
            if ($details == 'y') {
                $perms = $this->get_group_permissions($res['groupName']);
                $res['perms'] = $perms;
                $res['permcant'] = count($perms);
                $groups = $this->get_included_groups($res['groupName']);
                $res['included'] = $groups;
                $res['included_direct'] = $this->get_included_groups($res['groupName'], false);
            }
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    public function list_all_users()
    {
        global $tiki_p_list_users, $tiki_p_admin;
        $cachelib = TikiLib::lib('cache');

        if ($tiki_p_list_users !== 'y' && $tiki_p_admin != 'y') {
            return [];
        }

        if (! $users = $cachelib->getSerialized('userslist')) {
            $users = [];
            $result = $this->query('select `login`,`userId` from `users_users` order by `login`', []);
            while ($res = $result->fetchRow()) {
                $users["{$res['userId']}"] = $res['login'];
            }
            $cachelib->cacheItem('userslist', serialize($users));
        }

        return $users;
    }

    public function list_regular_groups()
    {
        $groups = [];
        $result = $this->query('select `id`, `groupName` from `users_groups` where isRole <> ?  order by `groupName`', ['y']);
        while ($res = $result->fetchRow()) {
            $groups[] = ["groupName" => $res['groupName'], "id" => $res['id']];
        }

        return $groups;
    }

    public function list_role_groups()
    {
        return $this->fetchAll("
            SELECT ug.id, ug.groupName FROM `users_groups` ug
			WHERE
			ug.isRole = 'y'
			group by ug.id, ug.GroupName");
    }


    public function list_all_groups()
    {
        $cachelib = TikiLib::lib('cache');

        if (! $groups = $cachelib->getSerialized('grouplist')) {
            $groups = [];
            $result = $this->query('select `groupName` from `users_groups` order by `groupName`', []);
            while ($res = $result->fetchRow()) {
                $groups[] = $res['groupName'];
            }
            $cachelib->cacheItem('grouplist', serialize($groups));
        }

        return $groups;
    }

    public function list_all_groupIds()
    {
        $cachelib = TikiLib::lib('cache');

        if (! $groups = $cachelib->getSerialized('groupIdlist')) {
            $groups = $this->fetchAll('select `id`, `groupName`, isRole from `users_groups` order by `groupName`', []);
            $cachelib->cacheItem('groupIdlist', serialize($groups));
        }

        return $groups;
    }

    public function list_can_include_groups($group)
    {
        $list = [];
        $query = 'select `groupName` from `users_groups`';
        $result = $this->query($query);
        while ($res = $result->fetchRow()) {
            if ($res['groupName'] != $group) {
                $includedGroups = $this->get_included_groups($res['groupName']);
                if (! in_array($group, $includedGroups)) {
                    $list[] = $res['groupName'];
                }
            }
        }

        return $list;
    }

    public function list_all_groups_with_permission()
    {
        $groups = array_map(function ($g) {
            return ['groupName' => $g];
        }, $this->list_all_groups());

        $filtered = Perms::filter(
            ['type' => 'group'],
            'object',
            $groups,
            ['object' => 'groupName'],
            'group_view'
        );

        return array_map(function ($g) {
            return $g['groupName'];
        }, $filtered);
    }


    public function remove_user($user)
    {
        $cachelib = TikiLib::lib('cache');

        if ($user == 'admin') {
            return false;
        }

        $userexists_cache[$user] = null;

        $userId = $this->getOne('select `userId` from `users_users` where `login` = ?', [$user]);

        $groupTracker = $this->get_tracker_usergroup($user);
        if ($groupTracker && $groupTracker['usersTrackerId']) {
            $trklib = TikiLib::lib('trk');

            $itemId = $trklib->get_item_id($groupTracker['usersTrackerId'], $groupTracker['usersFieldId'], $user);
            if ($itemId) {
                $trklib->remove_tracker_item($itemId);
            }
        }

        $tracker = $this->get_usertracker($userId);
        if ($tracker && $tracker['usersTrackerId']) {
            $trklib = TikiLib::lib('trk');

            $itemId = $trklib->get_item_id($tracker['usersTrackerId'], $tracker['usersFieldId'], $user);
            if ($itemId) {
                $trklib->remove_tracker_item($itemId);
            }
        }

        $query = 'delete from `users_users` where binary `login` = ?';
        $result = $this->query($query, [ $user ]);
        $query = 'delete from `users_usergroups` where `userId`=?';
        $result = $this->query($query, [ $userId ]);
        $query = 'delete from `tiki_user_login_cookies` where `userId`=?';
        $result = $this->query($query, [ $userId ]);
        $query = 'delete from `tiki_user_watches` where binary `user`=?';
        $result = $this->query($query, [$user]);
        $query = 'delete from `tiki_user_preferences` where binary `user`=?';
        $result = $this->query($query, [$user]);
        $query = 'delete from `tiki_newsletter_subscriptions` where binary `email`=? and `isUser`=?';
        $result = $this->query($query, [$user, 'y']);
        $this->query('delete from `tiki_user_reports` where `user` = ?', [$user]);
        $this->query('delete from `tiki_user_reports_cache` where `user` = ?', [$user]);
        $this->query('delete from `tiki_user_mailin_struct` where `username` = ?', [$user]);

        $cachelib->invalidate('userslist');

        TikiLib::events()->trigger('tiki.user.delete', ['type' => 'user', 'object' => $user]);

        return true;
    }

    public function change_login($from, $to)
    {
        global $user;
        $cachelib = TikiLib::lib('cache');

        if ($from == 'admin') {
            return false;
        }

        $userexists_cache[$user] = null;

        $userId = $this->getOne('select `userId` from `users_users` where `login` = ?', [$from]);

        if ($userId) {
            $this->query('update `users_users` set `login`=? where `userId` = ?', [$to, (int)$userId]);
            $this->query('update `tiki_wiki_attachments` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_webmail_contacts` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_webmail_contacts_fields` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_userpoints` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_userfiles` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_watches` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_votings` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_tasks` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_tasks` set `creator`=? where `creator`=?', [$to, $from]);
            $this->query('update `tiki_user_tasks_history` set `lasteditor`=? where `lasteditor`=?', [$to, $from]);
            $this->query('update `tiki_user_taken_quizzes` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_quizzes` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_preferences` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_postings` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_notes` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_menus` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_bookmarks_urls` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_bookmarks_folders` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_user_assigned_modules` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_tags` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_suggested_faq_questions` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_submissions` set `author`=? where `author`=?', [$to, $from]);
            $this->query('update `tiki_shoutbox` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_sessions` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_semaphores` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_received_pages` set `receivedFromUser`=? where `receivedFromUser`=?', [$to, $from]);
            $this->query('update `tiki_received_articles` set `author`=? where `author`=?', [$to, $from]);
            $this->query('update `tiki_private_messages` set `poster`=? where `poster`=?', [$to, $from]);
            $this->query('update `tiki_private_messages` set `toNickname`=? where `toNickname`=?', [$to, $from]);
            $this->query('update `tiki_pages` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_pages` set `creator`=? where `creator`=?', [$to, $from]);
            $this->query('update `tiki_page_footnotes` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_newsletters` set `author`=? where `author`=?', [$to, $from]);
            $this->query('update `tiki_minical_events` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_minical_topics` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_mailin_accounts` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_live_support_requests` set `operator`=? where `operator`=?', [$to, $from]);
            $this->query('update `tiki_live_support_requests` set `tiki_user`=? where `tiki_user`=?', [$to, $from]);
            $this->query('update `tiki_live_support_requests` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_live_support_operators` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_live_support_messages` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_live_support_messages` set `username`=? where `username`=?', [$to, $from]);
            $this->query('update `tiki_images` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_history` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_galleries` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_forums_reported` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_forums_queue` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_forums` set `moderator`=? where `moderator`=?', [$to, $from]);
            $this->query('update `tiki_forum_reads` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_files` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_files` set `lastModifUser`=? where `lastModifUser`=?', [$to, $from]);
            $this->query('update `tiki_files` set `lockedby`=? where `lockedby`=?', [$to, $from]);
            $this->query('update `tiki_file_galleries` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_file_drafts` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_copyrights` set `userName`=? where `userName`=?', [$to, $from]);
            $this->query('update `tiki_comments` set `userName`=? where `userName`=?', [$to, $from]);
            $this->query('update `tiki_chat_users` set `nickname`=? where `nickname`=?', [$to, $from]);
            $this->query('update `tiki_chat_messages` set `poster`=? where `poster`=?', [$to, $from]);
            $this->query('update `tiki_chat_channels` set `moderator`=? where `moderator`=?', [$to, $from]);
            $this->query('update `tiki_calendars` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_calendar_roles` set `username`=? where `username`=?', [$to, $from]);
            $this->query('update `tiki_calendar_items` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_blogs` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_blog_posts` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_banning` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `tiki_banners` set `client`=? where `client`=?', [$to, $from]);
            $this->query('update `tiki_articles` set `author`=? where `author`=?', [$to, $from]);
            $this->query('update `tiki_actionlog` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `messu_messages` set `user`=? where `user`=?', [$to, $from]);
            $this->query('update `messu_messages` set `user_from`=? where `user_from`=?', [$to, $from]);
            $this->query('update `tiki_newsletter_subscriptions` set `email`=? where `email`=? and `isUser`=?', [$to, $from, 'y']);
            $this->query('update `tiki_object_relations` set `source_itemId`=? where source_type="user" and `source_itemId`=?', [$to, $from]);
            $this->query('update `tiki_object_relations` set `target_itemId`=? where target_type="user" and `target_itemId`=?', [$to, $from]);
            $this->query('update `tiki_freetagged_objects` set `user`=? where `user`=?', [$to, $from]);

            $this->query(
                'update `tiki_tracker_item_fields`ttif' .
                ' left join `tiki_tracker_fields` ttf on (ttif.`fieldId`=ttf.`fieldId`)' .
                ' set `value`=? where ttif.`value`=? and ttf.`type`=?',
                [$to, $from, 'u']
            );
            $this->query('update `tiki_tracker_items` set `createdBy`=? where `createdBy`=?', [$to, $from]);
            $this->query('update `tiki_tracker_items` set `lastModifBy`=? where `lastModifBy`=?', [$to, $from]);

            $result = $this->query("select `fieldId`, `itemChoices` from `tiki_tracker_fields` where `type`='u'");

            while ($res = $result->fetchRow()) {
                $this->query('update `tiki_tracker_item_fields` set `value`=? where `value`=? and `fieldId`=?', [$to, $from, $res['fieldId']]);

                $u = ($res['itemChoices'] != '') ? unserialize($res['itemChoices']) : [];

                if ($value = array_search($from, $u)) {
                    $u[$value] = $to;
                    $u = serialize($u);
                    $this->query('update `tiki_tracker_fields` set `itemChoices`=? where `fieldId`=?', [$u, $res['fieldId']]);
                }
            }
            $cachelib->invalidate('userslist');
            TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $from]);
            TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $to]);

            return true;
        }

        return false;
    }

    public function remove_group($group)
    {
        if ($group == 'Anonymous' || $group == 'Registered') {
            return false;
        }
        $info = $this->get_group_info($group);

        $query = 'delete from `tiki_group_inclusion` where `groupName` = ? or `includeGroup` = ?';
        $result = $this->query($query, [$group, $group]);

        $query = [];
        $query[] = 'delete from `users_groups` where `groupName` = ?';
        $query[] = 'delete from `users_usergroups` where `groupName` = ?';
        $query[] = 'delete from `users_grouppermissions` where `groupName` = ?';
        $query[] = 'delete from `users_objectpermissions` where `groupName` = ?';
        $query[] = 'delete from `tiki_newsletter_groups` where `groupName` = ?';
        $query[] = 'delete from `tiki_group_watches` where `group` = ?';

        foreach ($query as $q) {
            $this->query($q, [$group]);
        }

        $this->query('update `users_users` set `default_group`=? where `default_group`=?', ['Registered', $group]);

        if (isset($info['id'])) {
            TikiLib::lib('categ')->detach_managed_category($info['id']);

            TikiLib::lib('attribute')->delete_objects_with('tiki.category.templatedgroupid', $info['id']);
            TikiLib::lib('attribute')->delete_objects_with('tiki.menu.templatedgroupid', $info['id']);
        }

        TikiLib::events()->trigger('tiki.group.delete', [
            'type' => 'group',
            'object' => $group,
        ]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('grouplist');
        $cachelib->invalidate('group_theme_' . $group);

        return $result;
    }

    public function get_user_default_group($user)
    {
        if (! isset($user)) {
            return 'Anonymous';
        }
        if (isset($_SESSION['u_info']) && $user == $_SESSION['u_info']['login']) {
            if (isset($_SESSION['u_info']['group']) && is_string($_SESSION['u_info']['group'])) {
                return $_SESSION['u_info']['group'];
            } elseif (isset($_SESSION['u_info']['group']['groupName']) && is_string($_SESSION['u_info']['group']['groupName'])) {
                return $_SESSION['u_info']['group']['groupName'];
            }
        }
        $query = 'select `default_group` from `users_users` where `login` = ?';
        $result = $this->getOne($query, [$user]);
        $ret = '';
        if (! is_null($result) && $result != '') {
            $ret = $result;
        } else {
            $groups = $this->get_user_groups($user);
            foreach ($groups as $gr) {
                if ($gr != 'Anonymous' and $gr != 'Registered' and $gr != '') {
                    $ret = $gr;

                    break;
                }
            }
            if (! $ret) {
                $ret = 'Registered';
            }
        }

        return $ret;
    }

    /**
     * Returns the wiki page name for the current user and checks for useGroupHome pref
     *
     * @param string $user  current logged in user
     * @return string       page name
     */
    public function get_user_default_homepage($user)
    {
        global $prefs;

        if ($prefs['useGroupHome'] !== 'y') {
            return $prefs['wikiHomePage'];
        }

        $home = '';
        $group = $this->get_user_default_group($user);

        if ($group) {
            $home = $this->get_group_home($group);
        }
        if (! $home) {	// work through the other groups this user is a member of
            $query = "select g.`groupHome`, g.`groupName`" .
                " from `users_usergroups` as gu, `users_users` as u, `users_groups`as g" .
                " where gu.`userId`= u.`userId` and u.`login`=? and gu.`groupName`= g.`groupName` and g.`groupHome` != '' and g.`groupHome` is not null";

            $result = $this->query($query, [$user]);

            while ($res = $result->fetchRow()) {
                if ($home != '') {
                    $groups = $this->get_included_groups($res['groupName']);
                    if (in_array($group, $groups)) {
                        $home = $res['groupHome'];
                        $group = $res['groupName'];
                    }
                } else {
                    $home = $res['groupHome'];
                    $group = $res['groupName'];
                }
            }
        }
        $home = $this->best_multilingual_page($home);

        $validHome = substr($home, 0, 1) === '/'
            || preg_match(',^https?://,', $home)
            || TikiLib::lib('tiki')->page_exists($home);

        if (! $validHome && $prefs['tikiIndex'] === 'tiki-index.php') {
            $home = $prefs['wikiHomePage'];
        }

        return $home;
    }

    public function best_multilingual_page($page)
    {
        global $prefs;

        if ($prefs['feature_multilingual'] != 'y') {
            return ($page);
        }

        $info = $this->get_page_info($page);
        $multilinguallib = TikiLib::lib('multilingual');
        $bestLangPageId = $multilinguallib->selectLangObj('wiki page', $info['page_id'], $prefs['language']);

        if ($info['page_id'] == $bestLangPageId) {
            return $page;
        }

        return $this->get_page_name_from_id($bestLangPageId);
    }

    /* Returns a theme/style for this ithe default group of the current user. */
    public function get_user_group_theme()
    {
        global $user;
        $group = $this->get_user_default_group($user);

        $cachelib = TikiLib::lib('cache');
        $k = 'group_theme_' . $group;

        if ($data = $cachelib->getCached($k)) {
            $return = $data;
        } elseif (! empty($group)) {
            $query = 'select `groupTheme` from `users_groups` where `groupName` = ?';
            $return = $this->getOne($query, [$group]);
            $cachelib->cacheItem($k, $return);
        }

        return $return;
    }

    /* Returns a default category for user's default_group
    */
    public function get_user_group_default_category($user)
    {
        $query = 'select `groupDefCat` from `users_groups` ug, `users_users` uu where `login` = ? and ug.`groupName` = uu.`default_group`';
        $result = $this->getOne($query, [$user]);

        return $result;
    }

    //modified get_user_groups() to know if the user is part of the group directly or through groups inclusion
    public function get_user_groups_inclusion($user)
    {
        $userid = $this->get_user_id($user);

        $query = 'select `groupName` from `users_usergroups` where `userId`=?';
        $result = $this->query($query, [(int)$userid]);
        $real = []; //really assigned groups (not (only) included)
        $ret = [];

        while ($res = $result->fetchRow()) {
            $real[] = $res['groupName'];
            foreach ($this->get_included_groups($res['groupName']) as $group) {
                $ret[$group] = 'included';
            }
        }

        foreach ($real as $group) {
            $ret[$group] = 'real';
        }

        return $ret;
    }

    public function get_group_home($group)
    {
        $query = 'select `groupHome` from `users_groups` where `groupName`=?';
        $result = $this->getOne($query, [$group]);
        $ret = '';

        if (! is_null($result)) {
            $ret = $result;
        }

        return $ret;
    }

    /**
     * Return information about users that belong to a
     * specific group
     *
     * @param string $group group name
     * @param int $offset
     * @param int $max
     * @param string $what which user fields to retrieve
     * @param string $sort_mode
     * @return array list of users
     */
    public function get_group_users($group, $offset = 0, $max = -1, $what = 'login', $sort_mode = 'login_asc')
    {
        if (empty($group)) {
            return [];
        }

        $w = $what == '*' ? 'uu.*, ug.`created`, ug.`expire` ' : "uu.`$what`";

        if (strpos($sort_mode, 'created_') !== false) {
            $sort_mode = 'ug.' . $sort_mode;	// avoid ambiguity of created column
        }
        $query = "select $w from `users_users` uu, `users_usergroups` ug where uu.`userId`=ug.`userId` and `groupName`=? order by " .
                        $this->convertSortMode($sort_mode);

        $result = $this->fetchAll($query, $group, $max, $offset);
        $ret = [];

        foreach ($result as $res) {
            $ret[] = ($what == '*') ? $res : $res[$what];
        }

        return $ret;
    }

    public function get_recur_group_users($group, $recur = 0, $what = 'login')
    {
        $users = $this->get_group_users($group, 0, -1, $what);
        if ($recur > 0) {
            $includings = $this->get_including_groups($group, 'n');
            --$recur;
            foreach ($includings as $including) {
                $users = array_merge($users, $this->get_recur_group_users($including, $recur, $what));
            }
        }

        return $users;
    }

    public function get_user_info($user, $inclusion = false, $field = 'login')
    {
        global $prefs;
        if ($field == 'userId') {
            $user = (int)$user;
        } elseif ($field != 'login') {
            return false;
        }

        $result = $this->query("select * from `users_users` where `$field`=?", [$user]);
        if ($res = $result->fetchRow()) {
            $res['groups'] = ($inclusion) ? $this->get_user_groups_inclusion($res['login']) : $this->get_user_groups($res['login']);
            $res['age'] = (! isset($res['registrationDate'])) ? 0 : $this->now - $res['registrationDate'];

            if ($prefs['login_is_email'] == 'y' && isset($res['login']) && $res['login'] != 'admin') {
                $res['email'] = $res['login'];
            }

            $res['editable'] = $this->user_can_be_edited($res['login']);

            return $res;
        }
    }

    public function get_userid_info($user, $inclusion = false)
    {
        return $this->get_user_info($user, $inclusion, 'userId');
    }

    /**
     * Helps recognize nicknames which anonymous users have chosen
     * A leading tab is for recognizing anonymous entries. Real usernames don't start with a tab
     * @param string $username	user nickname or name
     * @return string				string which should be displayed
     */
    public function distinguish_anonymous_users($username = "")
    {
        if (empty($username)) {
            return "";
        }
        if ($username[0] == "\t") {
            $username = $username . ' (' . tra('unverified') . ')';
        }

        return $username;
    }


    /**
     * Creates DOM tag for user info with popup or not depending on prefs etc
     * @param string $auser     user to find info for (current user if empty)
     * @param string $body      content of the anchor tag (user name if empty)
     * @param string $class		add a class to the a tag (default userlink)
     * @param mixed $show_popup
     * @param mixed $elementId
     * @return string           HTML anchor tag
     */
    public function build_userinfo_tag($auser = '', $body = '', $class = 'userlink', $show_popup = 'y', $elementId = '')
    {
        global $user, $prefs;

        if (! $auser) {
            $auser = $user;
        }
        $realn = $this->clean_user($auser);

        if (! $body) {
            $body = $realn;
        }

        if ($elementId) {
            $idStr = " id=\"$elementId\"";
        } else {
            $idStr = '';
        }

        $isSelf = ($auser === $user) ? true : false;
        // Only process if feature_friends enabled, user_information public or we query ourselfs
        if (($this->get_user_preference($auser, 'user_information', 'public') != 'public') && ($prefs['feature_friends'] != 'y') && ! $isSelf) {
            return "<span{$idStr}>$body</span>";
        }


        $id = $this->get_user_id($auser);
        if ($id == -1) {
            return $body;
        }

        $extra = '';
        if ($show_popup == "n") {
            //do nothing for adding a tip
            $title = '';
        } elseif ($prefs['feature_community_mouseover'] == 'y' && ($this->get_user_preference($auser, 'show_mouseover_user_info', 'y') == 'y' || $prefs['feature_friends'] == 'y')) {
            $data = TikiLib::lib('service')->getUrl([
                'controller' => 'user',
                'action' => 'info',
                'username' => $auser,
            ]);
            $extra .= ' data-ajaxtips="' . htmlspecialchars($data, ENT_QUOTES) . '"';
            $class .= ' ajaxtips';

            if ($auser === $user) {
                $title = tra('Your Information');
            } else {
                $title = tra('User Information');
            }
        } elseif ($prefs['user_show_realnames'] == 'y') {
            $class .= ' tips';
            $title = tr('User') . ':' . $realn;
        } else {
            $class .= ' tips';
            $title = tr('User') . ':' . $auser;
        }

        if (empty($prefs['urlOnUsername'])) {
            $url = 'tiki-user_information.php?userId=' . $id;
            if ($prefs['feature_sefurl'] == 'y') {
                include_once('tiki-sefurl.php');
                $url = filter_out_sefurl($url);
            }
        } else {
            $url = preg_replace(
                ['/%userId%/', '/%user%/'],
                [$id, $auser],
                $prefs['urlOnUsername']
            );
        }

        $lat = $this->get_user_preference($auser, 'lat');
        $lon = $this->get_user_preference($auser, 'lon');
        $zoom = $this->get_user_preference($auser, 'zoom');

        if (! ($lat == 0 && $lon == 0)) {
            $class .= ' geolocated';
            $extra .= ' data-geo-lat="' . $lat . '" data-geo-lon="' . $lon . '"';

            if ($zoom) {
                $extra .= ' data-geo-zoom="' . $zoom . '"';
            }
        }

        if ($title) {
            $titleStr = ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"';
        } else {
            $titleStr = '';
        }

        $body = "<a{$idStr}{$titleStr} href=\"$url\" class=\"$class\"$extra>" . $body . '</a>';

        return $body;
    }



    // UNRELIABLE. In particular, lastLogin and currentLogin aren't properly maintained due to missing user_details_ cache invalidation
    // refactoring to use new cachelib instead of global var in memory - batawata 2006-02-07
    public function get_user_details($login, $useCache = true)
    {
        $cachelib = TikiLib::lib('cache');

        $cacheKey = 'user_details_' . $login;

        if (! $useCache || ! $user_details = $cachelib->getSerialized($cacheKey)) {
            $user_details = [];

            $query = 'SELECT `userId`, `login`, `email`, `lastLogin`, `currentLogin`,' .
                            ' `registrationDate`, `created`, `avatarName`, `avatarSize`,' .
                            ' `avatarFileType`, `avatarLibName`, `avatarType`' .
                            ' FROM `users_users` WHERE `login` = ?';

            $result = $this->query($query, [$login]);

            $user_details['info'] = $result->fetchRow();

            $query = 'SELECT `prefName` , `value` FROM `tiki_user_preferences` WHERE `user` = ?';
            $result = $this->query($query, [$login]);

            $user_details['preferences'] = [];
            $aUserPrefs = ['realName', 'homePage', 'country'];

            while ($row = $result->fetchRow()) {
                $user_details['preferences'][$row['prefName']] = $row['value'];

                // atention: this is redundant, for intertiki slave mode
                // we insert, delete and insert again this information,
                // because of nature of user information as being preferences
                if (in_array($row['prefName'], $aUserPrefs)) {
                    $user_details['info'][$row['prefName']] = $row['value'];
                }
            }

            $user_details['groups'] = $this->get_user_groups($login);

            $cachelib->cacheItem($cacheKey, serialize($user_details));

            global $user_preferences;
            $user_preferences[$login] = $user_details['preferences'];
        }

        return $user_details;
    }

    public function set_default_group($user, $group)
    {
        // if user is not in group, assign user to group before setting default group
        $user_groups = $this->get_user_groups($user);
        if (! in_array($group, $user_groups) && ! empty($group)) {
            $this->assign_user_to_group($user, $group);
        }
        $query = 'update `users_users` set `default_group` = ? where `login` = ?';
        $result = $this->query($query, [$group, $user]);

        if ($user == $_SESSION['u_info']['login']) {
            $_SESSION['u_info']['group'] = $group;
        }

        return $result;
    }

    public function set_email_group($user, $email)
    {
        $query = 'select `id`, `groupName`, `emailPattern` from `users_groups` where `emailPattern`!=?';
        $groups = $this->fetchAll($query, ['']);
        $nb = 0;

        if (empty($groups)) {
            return 0;
        }

        $userGroups = $this->get_user_groups_inclusion($user);
        foreach ($groups as $group) {
            if (! isset($userGroups[$group['groupName']]) && preg_match($group['emailPattern'], $email)) {
                $this->assign_user_to_group($user, $group['groupName']);
                $this->set_default_group($user, $group['groupName']);
                ++$nb;
            }
        }

        return $nb;
    }

    public function refresh_set_email_group()
    {
        $users = $this->list_users();
        $nb = 0;
        foreach ($users['data'] as $user) {
            $nb += $this->set_email_group($user['login'], $user['email']);
        }

        return $nb;
    }

    public function change_permission_level($perm, $level)
    {
        $query = 'update `users_permissions` set `level` = ? where `permName` = ?';
        $this->query($query, [$level, $perm]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('fgals_perms');

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();
    }

    public function assign_level_permissions($group, $level)
    {
        $query = 'select `permName` from `users_permissions` where `level` = ?';
        $result = $this->query($query, [$level]);

        while ($res = $result->fetchRow()) {
            $this->assign_permission_to_group($res['permName'], $group);
        }

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('fgals_perms');
        $cachelib->invalidate("groupperms_$group");

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();
    }

    public function remove_level_permissions($group, $level)
    {
        $query = 'select `permName` from `users_permissions` where `level` = ?';
        $result = $this->query($query, [$level]);

        while ($res = $result->fetchRow()) {
            $this->remove_permission_from_group($res['permName'], $group);
        }

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('fgals_perms');
        $cachelib->invalidate("groupperms_$group");

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();
    }

    public function create_dummy_level($level)
    {
        $query = 'delete from `users_permissions` where `permName` = ?';
        $result = $this->query($query, ['']);
        $query = "insert into `users_permissions`(`permName`, `level`) values('', ?)";
        $this->query($query, [$level]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('fgals_perms');

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();
    }

    public function get_permission_levels()
    {
        $query = 'select distinct(`level`) from `users_permissions`';

        $result = $this->query($query);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res['level'];
        }

        return $ret;
    }

    public function get_tracker_usergroup($user)
    {
        $lastRes = '';
        $group = $this->get_user_default_group($user);
        if (! empty($group)) {
            $lastRes = $this->get_usertrackerid($group);
        }
        if (! $lastRes) {
            $groups = $this->get_user_groups($user);
            $query = 'select `groupName`, `usersTrackerId`, `usersFieldId`' .
                            ' from `users_groups`' .
                            ' where `groupName` in ( ' . implode(' , ', array_fill(0, count($groups), '?')) .
                            ' ) and `groupName` != ? and `usersTrackerId` > 0';

            $groups[] = 'Anonymous';
            $result = $this->query($query, $groups);

            while ($res = $result->fetchRow()) {
                $lastRes = $res;
                if ($res['groupName'] != 'Registered') {
                    return 	$res ;
                }
            }
        }

        return $lastRes;
    }

    public function get_grouptrackerid($group)
    {
        $res = $this->query('select `groupTrackerId`,`groupFieldId` from `users_groups` where `groupName`=?', [$group]);
        $ret = $res->fetchRow();

        if (! $ret['groupTrackerId'] or ! $ret['groupFieldId']) {
            $groups = $this->get_included_groups($group);
            foreach ($groups as $gr) {
                $res = $this->query('select `groupTrackerId`,`groupFieldId` from `users_groups` where `groupName`=?', [$gr]);
                $ret = $res->fetchRow();
                if ($ret['groupTrackerId'] and $ret['groupFieldId']) {
                    return $ret;
                }
            }
        } else {
            return $ret;
        }

        return false;
    }

    /**
     * @param $group
     * @param $include_groups
     * @throws Exception
     */
    public function manage_group($group, $include_groups): void
    {
        $oldIncluded = $this->get_included_groups($group, false);
        $this->remove_all_inclusions($group);
        $info = $this->get_group_info($group);

        if (isset($include_groups) and is_array($include_groups)) {
            $oldIncludes = array_diff($oldIncluded, $include_groups);
            $oldGroups = $this->get_group_info(array_values($oldIncludes));
            $parentGroupsIds = array_map(function ($item) {
                return $item["id"];
            }, array_filter($oldGroups, function ($item) {
                return $item["isTplGroup"] == "y";
            }));
            if (!empty($parentGroupsIds)) {
                TikiLib::lib('categ')->detach_managed_category($info["id"], $parentGroupsIds);
            }

            foreach ($include_groups as $include) {
                if ($include && $group != $include) {
                    $this->group_inclusion($group, $include);
                }
            }

            $groups = $this->get_group_info($include_groups);
            $templateGroups = array_filter($groups, function ($item) {
                return $item["isTplGroup"] == "y";
            });
            foreach ($templateGroups as $templateGroup) {
                $categories = TikiLib::lib('categ')->get_managed_categories($templateGroup["id"]);
                $managedIds = array_unique(array_map(function ($item) {
                    return $item["categId"];
                }, $categories));

                foreach ($managedIds as $managedId) {
                    TikiLib::lib('categ')->manage_sub_categories($managedId);
                }
            }
        }
    }

    public function get_usertrackerid($group)
    {
        $res = $this->query('select `usersTrackerId`,`usersFieldId` from `users_groups` where `groupName`=?', [$group]);
        $ret = $res->fetchRow();

        if (! $ret['usersTrackerId'] or ! $ret['usersFieldId']) {
            $groups = $this->get_included_groups($group);
            foreach ($groups as $gr) {
                $res = $this->query('select `usersTrackerId`,`usersFieldId` from `users_groups` where `groupName`=?', [$gr]);
                $ret = $res->fetchRow();
                if ($ret['usersTrackerId'] and $ret['usersFieldId']) {
                    return $ret;
                }
            }
        } else {
            return $ret;
        }

        return false;
    }


    public function get_usertracker($uid)
    {
        if ($utr = $this->get_userid_info($uid)) {
            $utr['usersTrackerId'] = '';
            foreach ($utr['groups'] as $gr) {
                $utrid = $this->get_usertrackerid($gr);
                if (! empty($utrid['usersTrackerId']) && ! empty($utrid['usersFieldId'])) {
                    $utrid['group'] = $gr;
                    $utrid['user'] = $utr['login'];
                    $utr = $utrid;

                    break;
                }
            }

            return $utr;
        }
    }

    public function get_enabled_permissions()
    {
        global $prefs;

        $raw = $this->get_raw_permissions();
        $out = [];

        foreach ($raw as $permission) {
            $valid = empty($permission['prefs']);

            if (! $valid) {
                foreach ($permission['prefs'] as $name) {
                    if (isset($prefs[$name]) && $prefs[$name] == 'y') {
                        $valid = true;

                        break;
                    }
                }
            }

            if ($valid) {
                $out[$permission['name']] = $permission;
            }
        }

        return $out;
    }

    public function get_permission_names_for($type)
    {
        // Compatibility hack without which no article results are returned by mysql full-text searches (basic search)
        if ($type == "cms") {
            $type = "articles";
        }
        $raw = $this->get_permissions(0, -1, 'permName_asc', '', $type);
        $out = [];

        foreach ($raw['data'] as $permission) {
            $out[] = $permission['name'];
        }

        return $out;
    }

    /**
     * Function for sorting permission in tiki-objectpermissions.php
     * @param $permissions
     * @return array $permissions
     */
    private function getSortingPermissions($permissions)
    {
        $file = 'db/config/tiki-objectpermissions_order.yml';

        if (! file_exists($file)) {
            return $permissions;
        }

        $content = file_get_contents($file);

        try {
            $order = Yaml::parse($content);
        } catch (ParseException $e) {
            $logslib = TikiLib::lib('logs');
            $logslib->add_log('System', $file . ' - ' . $e->getMessage());

            return $permissions;
        }

        $step = 100;
        $maxTypes = 0;
        $typeWeights = [];
        $permissionWeights = [];
        $checkYaml = [];

        foreach ($permissions as $perCheck) {
            $checkYaml[$perCheck['type']][$perCheck['name']] = ['result' => 1];
        }

        foreach ($order as $key => $permissionList) {
            if (! isset($typeWeights[$key])) {
                $typeWeights[$key] = ['weight' => $maxTypes, 'next' => 0];
                $maxTypes += $step;
            }
            $weight = $typeWeights[$key]['weight'];
            $next = $typeWeights[$key]['next'];
            foreach ($permissionList as $permission) {
                if (! array_key_exists('tiki_p_' . $permission, $checkYaml[$key])) {
                    continue;
                }

                if ($checkYaml[$key]['tiki_p_' . $permission]['result'] == 1) {
                    $permissionWeights['tiki_p_' . $permission] = $weight + $next++;
                }
            }
            $typeWeights[$key]['next'] = $next;
        }

        foreach ($permissions as $permission) {
            if (isset($permissionWeights[$permission['name']])) {
                continue;
            }
            if (! isset($typeWeights[$permission['type']])) {
                $typeWeights[$permission['type']] = ['weight' => $maxTypes, 'next' => 0];
                $maxTypes += $step;
            }
            $permissionWeights[$permission['name']] = $typeWeights[$permission['type']]['weight'] + $typeWeights[$permission['type']]['next']++;
        }

        usort($permissions, function ($a, $b) use ($permissionWeights) {
            $result = $permissionWeights[$a['name']] - $permissionWeights[$b['name']];

            return $result;
        });

        return $permissions;
    }

    private function get_raw_permissions()
    {
        static $permissions;

        // Avoid multiple unserialize per page
        if ($permissions) {
            return $permissions;
        }

        global $prefs;
        $cachelib = TikiLib::lib('cache');

        if ($permissions = $cachelib->getSerialized('rawpermissions' . $prefs['language'])) {
            return $permissions;
        }

        /**
         * Define master permissions array
         *
         * NOTE: This is the order they appear in tiki-objectpermissions.php
         *       and it's important to keep them grouped by 'type'
         *
         */
        $permissions = [
            [
                'name' => 'tiki_p_acct_create_book',
                'description' => tra('Can create/close a book'),
                'level' => 'admin',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_manage_accounts',
                'description' => tra('Can create/edit/lock accounts'),
                'level' => 'admin',
                'type' => 'accounting',
                'admin' => true,
                'prefs' => ['feature_accounting' ],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_book',
                'description' => tra('Create a new transaction'),
                'level' => 'editors',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_view',
                'description' => tra('Permission to view the journal'),
                'level' => 'registered',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting' ],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_book_stack',
                'description' => tra('Can book into the stack where statements can be changed'),
                'level' => 'editors',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_book_import',
                'description' => tra('Can import statements from external accounts'),
                'level' => 'editors',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting' ],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_acct_manage_template',
                'description' => tra('Can manage templates for recurring transactions'),
                'level' => 'editors',
                'type' => 'accounting',
                'admin' => false,
                'prefs' => ['feature_accounting'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_cms',
                'description' => tra('Can admin the articles'),
                'level' => 'admin',
                'type' => 'articles',
                'admin' => true,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_approve_submission',
                'description' => tra('Can approve submissions'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_articles_admin_topics',
                'description' => tra('Can admin article topics'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_articles_admin_types',
                'description' => tra('Can admin article types'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_articles_read_heading',
                'description' => tra('Can read article headings'),
                'level' => 'basic',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_autoapprove_submission',
                'description' => tra('Submitted articles are automatically approved'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_article',
                'description' => tra('Can edit articles'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_article_user',
                'description' => tra('Can edit the user (owner) of articles'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_submission',
                'description' => tra('Can edit submissions'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_read_article',
                'description' => tra('Can read articles (applies to article or topic level)'),
                'level' => 'basic',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_article',
                'description' => tra('Can remove articles'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_submission',
                'description' => tra('Can remove submissions'),
                'level' => 'editors',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_submit_article',
                'description' => tra('Can submit articles'),
                'level' => 'basic',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_rate_article',
                'description' => tra('Can rate articles'),
                'level' => 'basic',
                'type' => 'articles',
                'admin' => false,
                'prefs' => ['feature_articles'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_bigbluebutton_view_rec',
                'description' => tra('Can view recordings from past meetings'),
                'level' => 'basic',
                'type' => 'bigbluebutton',
                'admin' => false,
                'prefs' => ['bigbluebutton_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_bigbluebutton_join',
                'description' => tra('Can join a meeting'),
                'level' => 'basic',
                'type' => 'bigbluebutton',
                'admin' => false,
                'prefs' => ['bigbluebutton_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_bigbluebutton_moderate',
                'description' => tra('Can moderate a meeting'),
                'level' => 'admin',
                'type' => 'bigbluebutton',
                'admin' => false,
                'prefs' => ['bigbluebutton_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_bigbluebutton_create',
                'description' => tra('Can create a meeting'),
                'level' => 'admin',
                'type' => 'bigbluebutton',
                'admin' => false,
                'prefs' => ['bigbluebutton_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_xmpp_chat',
                'description' => tra('Can use XMPP chat'),
                'level' => 'admin',
                'type' => 'xmpp',
                'admin' => false,
                'prefs' => ['xmpp_feature'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_blog_admin',
                'description' => tra('Can admin blogs'),
                'level' => 'editors',
                'type' => 'blogs',
                'admin' => true,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_assign_perm_blog',
                'description' => tra('Can assign perms to blog'),
                'level' => 'admin',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_blog_post',
                'description' => tra('Can post to a blog'),
                'level' => 'registered',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_create_blogs',
                'description' => tra('Can create a blog'),
                'level' => 'editors',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_read_blog',
                'description' => tra('Can read blogs'),
                'level' => 'basic',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_blog_post_view_ref',
                'description' => tra('Can view in module and feed the blog posts'),
                'level' => 'basic',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_blog_view_ref',
                'description' => tra('Can view in module and feed the blog'),
                'level' => 'basic',
                'type' => 'blogs',
                'admin' => false,
                'prefs' => ['feature_blogs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_calendar',
                'description' => tr('Can create/admin calendars'),
                'level' => 'admin',
                'type' => 'calendar',
                'admin' => true,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_add_events',
                'description' => tra('Can add events in the calendar'),
                'level' => 'registered',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_change_events',
                'description' => tra('Can edit events in the calendar'),
                'level' => 'registered',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_calendar',
                'description' => tra('Can browse the calendar'),
                'level' => 'basic',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_events',
                'description' => tra('Can view event details'),
                'level' => 'registered',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_calendar_add_my_particip',
                'description' => tra('Can add himself or herself to the participants'),
                'level' => 'registered',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_calendar_add_guest_particip',
                'description' => tra('Can add guest to the participants'),
                'level' => 'registered',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_calendar'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_tiki_calendar',
                'description' => tra('Can view Tiki tools calendar'),
                'level' => 'basic',
                'type' => 'calendar',
                'admin' => false,
                'prefs' => ['feature_action_calendar'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_categories',
                'description' => tra('Can admin categories'),
                'level' => 'admin',
                'type' => 'category',
                'admin' => true,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_category',
                'description' => tra('Can see the category in a listing'),
                'level' => 'basic',
                'type' => 'category',
                'admin' => false,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_add_object',
                'description' => tra('Can add objects to the category (tiki_p_modify_object_categories permission required)'),
                'level' => 'editors',
                'type' => 'category',
                'admin' => false,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_object',
                'description' => tra('Can remove objects from the category (tiki_p_modify_object_categories permission required)'),
                'level' => 'editors',
                'type' => 'category',
                'admin' => false,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_assign_perm_category',
                'description' => tra('Can assign perms to category'),
                'level' => 'admin',
                'type' => 'category',
                'admin' => false,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
            ],
            //array(
            //	'name' => 'tiki_p_create_category',
            //	'description' => tra('Can create new categories'),
            //	'level' => 'admin',
            //	'type' => 'category',
            //	'admin' => false,
            //	'prefs' => array('feature_categories'),
            //	'scope' => 'global',
            //),
            [
                'name' => 'tiki_p_admin_chat',
                'description' => tra('Administrator can create channels, remove channels, etc'),
                'level' => 'editors',
                'type' => 'chat',
                'admin' => true,
                'prefs' => ['feature_minichat', 'feature_live_support'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_chat',
                'description' => tra('Can use the chat system'),
                'level' => 'registered',
                'type' => 'chat',
                'admin' => false,
                'prefs' => ['feature_minichat', 'feature_live_support'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_received_articles',
                'description' => tra('Can admin received articles'),
                'level' => 'editors',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_received_pages',
                'description' => tra('Can admin received pages'),
                'level' => 'editors',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_send_articles',
                'description' => tra('Can send articles to other sites'),
                'level' => 'editors',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_sendme_articles',
                'description' => tra('Can send articles to this site'),
                'level' => 'registered',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_sendme_pages',
                'description' => tra('Can send pages to this site'),
                'level' => 'registered',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_send_pages',
                'description' => tra('Can send pages to other sites'),
                'level' => 'registered',
                'type' => 'comm',
                'admin' => false,
                'prefs' => ['feature_comm'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_post_comments',
                'description' => tra('Can post new comments'),
                'level' => 'registered',
                'type' => 'comments',
                'admin' => false,
                'prefs' => [
                                        'feature_wiki_comments',
                                        'feature_blogposts_comments',
                                        'feature_file_galleries_comments',
                                        'feature_image_galleries_comments',
                                        'feature_article_comments',
                                        'feature_faq_comments',
                                        'feature_poll_comments',
                                        'map_comments'
                ],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_read_comments',
                'description' => tra('Can read comments'),
                'level' => 'basic',
                'type' => 'comments',
                'admin' => false,
                'prefs' => [
                                        'feature_wiki_comments',
                                        'feature_blogposts_comments',
                                        'feature_file_galleries_comments',
                                        'feature_image_galleries_comments',
                                        'feature_article_comments',
                                        'feature_faq_comments',
                                        'feature_poll_comments',
                                        'map_comments'
                ],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_admin_comments',
                'description' => tra('Can admin comments'),
                'level' => 'admin',
                'type' => 'comments',
                'admin' => true,
                'prefs' => [
                                        'feature_wiki_comments',
                                        'feature_blogposts_comments',
                                        'feature_file_galleries_comments',
                                        'feature_image_galleries_comments',
                                        'feature_article_comments',
                                        'feature_faq_comments',
                                        'feature_poll_comments',
                                        'map_comments'
                ],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_edit_comments',
                'description' => tra('Can edit all comments'),
                'level' => 'editors',
                'type' => 'comments',
                'admin' => false,
                'prefs' => ['
										feature_wiki_comments',
                                        'feature_blogposts_comments',
                                        'feature_file_galleries_comments',
                                        'feature_image_galleries_comments',
                                        'feature_article_comments',
                                        'feature_faq_comments',
                                        'feature_poll_comments',
                                        'map_comments'
                ],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_remove_comments',
                'description' => tra('Can delete comments'),
                'level' => 'editors',
                'type' => 'comments',
                'admin' => false,
                'prefs' => [
                                        'feature_wiki_comments',
                                        'feature_blogposts_comments',
                                        'feature_file_galleries_comments',
                                        'feature_image_galleries_comments',
                                        'feature_article_comments',
                                        'feature_faq_comments',
                                        'feature_poll_comments',
                                        'map_comments'
                ],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_vote_comments',
                'description' => tra('Can vote on comments'),
                'level' => 'registered',
                'type' => 'comments',
                'admin' => false,
                'prefs' => ['comments_vote'],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'blogs'],
            ],
            [
                'name' => 'tiki_p_admin_content_templates',
                'description' => tra('Can admin content templates'),
                'level' => 'admin',
                'type' => 'content templates',
                'admin' => true,
                'prefs' => ['feature_wiki_templates', 'feature_cms_templates'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_content_templates',
                'description' => tra('Can edit content templates'),
                'level' => 'editors',
                'type' => 'content templates',
                'admin' => false,
                'prefs' => ['feature_wiki_templates', 'feature_cms_templates', 'feature_file_galleries_templates'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_lock_content_templates',
                'description' => tra('Can lock content templates'),
                'level' => 'editors',
                'type' => 'content templates',
                'admin' => false,
                'prefs' => ['feature_wiki_templates', 'feature_cms_templates', 'lock_content_templates'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_use_content_templates',
                'description' => tra('Can use content templates'),
                'level' => 'registered',
                'type' => 'content templates',
                'admin' => false,
                'prefs' => ['feature_wiki_templates', 'feature_cms_templates'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_contribution',
                'description' => tra('Can admin contributions'),
                'level' => 'admin',
                'type' => 'contribution',
                'admin' => true,
                'prefs' => ['feature_contribution'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_directory',
                'description' => tra('Can admin the directory'),
                'level' => 'editors',
                'type' => 'directory',
                'admin' => true,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_directory_cats',
                'description' => tra('Can admin directory categories'),
                'level' => 'editors',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_directory_sites',
                'description' => tra('Can admin directory sites'),
                'level' => 'editors',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_autosubmit_link',
                'description' => tra('Submitted links are valid'),
                'level' => 'editors',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_submit_link',
                'description' => tra('Can submit sites to the directory'),
                'level' => 'basic',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_validate_links',
                'description' => tra('Can validate submitted links'),
                'level' => 'editors',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_directory',
                'description' => tra('Can use the directory'),
                'level' => 'basic',
                'type' => 'directory',
                'admin' => false,
                'prefs' => ['feature_directory'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_dsn_query',
                'description' => tra('Can execute arbitrary queries on a given DSN'),
                'level' => 'admin',
                'type' => 'dsn',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_faqs',
                'description' => tra('Can admin FAQs'),
                'level' => 'editors',
                'type' => 'faqs',
                'admin' => true,
                'prefs' => ['feature_faqs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_suggest_faq',
                'description' => tra('Can suggest FAQ questions'),
                'level' => 'basic',
                'type' => 'faqs',
                'admin' => false,
                'prefs' => ['feature_faqs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_faqs',
                'description' => tra('Can view FAQs'),
                'level' => 'basic',
                'type' => 'faqs',
                'admin' => false,
                'prefs' => ['feature_faqs'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_download_files',
                'description' => tra('Can download files'),
                'level' => 'basic',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_upload_files',
                'description' => tra('Can upload files'),
                'level' => 'registered',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_list_file_galleries',
                'description' => tra('Can list file galleries'),
                'level' => 'basic',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_file_gallery',
                'description' => tra('Can view file galleries'),
                'level' => 'basic',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_file_galleries',
                'description' => tra('Can admin file galleries'),
                'level' => 'admin',
                'type' => 'file galleries',
                'admin' => true,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_assign_perm_file_gallery',
                'description' => tra('Can assign permissions to file galleries'),
                'level' => 'admin',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_batch_upload_file_dir',
                'description' => tra('Can use Directory Batch Load'),
                'level' => 'editors',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries_batch'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_batch_upload_files',
                'description' => tra('Can upload .zip file packages'),
                'level' => 'editors',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_create_file_galleries',
                'description' => tra('Can create file galleries'),
                'level' => 'editors',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_gallery_file',
                'description' => tra('Can edit a gallery file'),
                'level' => 'editors',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_files',
                'description' => tra('Can remove files'),
                'level' => 'registered',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_fgal_explorer',
                'description' => tra('Can view file galleries explorer'),
                'level' => 'basic',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['fgal_show_explorer'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_fgal_path',
                'description' => tra('Can view file galleries path'),
                'level' => 'basic',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['fgal_show_path'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_upload_javascript',
                'description' => tra('Can upload files containing JavaScript'),
                'level' => 'admin',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['feature_file_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_upload_svg',
                'description' => tra('Can upload SVG files'),
                'level' => 'admin',
                'type' => 'file galleries',
                'admin' => false,
                'prefs' => ['fgal_allow_svg'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_forum',
                'description' => tra('Can admin forums'),
                'level' => 'admin',
                'type' => 'forums',
                'admin' => true,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_attach',
                'description' => tra('Can attach files to forum posts'),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_autoapp',
                'description' => tra('Auto approve forum posts'),
                'level' => 'editors',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_edit_own_posts',
                'description' => tra("Can edit one's own forum posts"),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_post',
                'description' => tra('Can post in forums'),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_post_topic',
                'description' => tra('Can start threads in forums'),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_read',
                'description' => tra('Can read forums'),
                'level' => 'basic',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forums_report',
                'description' => tra('Can report posts to moderator'),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_forum_vote',
                'description' => tra('Can vote on comments in forums'),
                'level' => 'registered',
                'type' => 'forums',
                'admin' => false,
                'prefs' => ['feature_forums'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_freetags',
                'description' => tra('Can browse tags'),
                'level' => 'basic',
                'type' => 'freetags',
                'admin' => false,
                'prefs' => ['feature_freetags'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_freetags',
                'description' => tra('Can admin tags'),
                'level' => 'admin',
                'type' => 'freetags',
                'admin' => true,
                'prefs' => ['feature_freetags'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_freetags_tag',
                'description' => tra('Can tag objects'),
                'level' => 'registered',
                'type' => 'freetags',
                'admin' => false,
                'prefs' => ['feature_freetags'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_unassign_freetags',
                'description' => tra('Can unassign tags from an object'),
                'level' => 'basic',
                'type' => 'freetags',
                'admin' => false,
                'prefs' => ['feature_freetags'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_subscribe_groups',
                'description' => tra('Can subscribe to groups'),
                'level' => 'registered',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_invite_to_my_groups',
                'description' => tra('Can invite user to my groups'),
                'level' => 'editors',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_group_view',
                'description' => tra('Can view the group'),
                'level' => 'basic',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_group_view_members',
                'description' => tra('Can view the group members'),
                'level' => 'basic',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_group_add_member',
                'description' => tra('Can add group members'),
                'level' => 'admin',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_group_remove_member',
                'description' => tra('Can remove group members'),
                'level' => 'admin',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_group_join',
                'description' => tra('Can join or leave the group'),
                'level' => 'admin',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_h5p_view',
                'description' => tra('Can view H5P content'),
                'level' => 'registered',
                'type' => 'h5p',
                'admin' => false,
                'prefs' => ['h5p_enabled'],
                'scope' => 'global',    // adding as global to start with, probably will need to be object type eventually?
            ],
            [
                'name' => 'tiki_p_h5p_edit',
                'description' => tra('Can edit H5P content'),
                'level' => 'editors',
                'type' => 'h5p',
                'admin' => false,
                'prefs' => ['h5p_enabled'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_h5p_admin',
                'description' => tra('Can administer H5P content'),
                'level' => 'admins',
                'type' => 'h5p',
                'admin' => false,
                'prefs' => ['h5p_enabled'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_html_pages',
                'description' => tra('Can edit HTML pages'),
                'level' => 'editors',
                'type' => 'html pages',
                'admin' => false,
                'prefs' => ['feature_html_pages'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_html_pages',
                'description' => tra('Can view HTML pages'),
                'level' => 'basic',
                'type' => 'html pages',
                'admin' => false,
                'prefs' => ['feature_html_pages'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_galleries',
                'description' => tra('Can admin Image Galleries'),
                'level' => 'editors',
                'type' => 'image galleries',
                'admin' => true,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_assign_perm_image_gallery',
                'description' => tra('Can assign permissions to image galleries'),
                'level' => 'admin',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_batch_upload_image_dir',
                'description' => tra('Can use Directory Batch Load'),
                'level' => 'editors',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_batch_upload_images',
                'description' => tra('Can upload .zip files of images'),
                'level' => 'editors',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_create_galleries',
                'description' => tra('Can create image galleries'),
                'level' => 'editors',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_list_image_galleries',
                'description' => tra('Can list image galleries'),
                'level' => 'basic',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_upload_images',
                'description' => tra('Can upload images'),
                'level' => 'registered',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_image_gallery',
                'description' => tra('Can view image galleries'),
                'level' => 'basic',
                'type' => 'image galleries',
                'admin' => false,
                'prefs' => ['feature_galleries'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_kaltura',
                'description' => tra('Can admin Kaltura video feature'),
                'level' => 'admin',
                'type' => 'media',
                'admin' => true,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_upload_videos',
                'description' => tra('Can upload video or record from webcam'),
                'level' => 'editors',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_videos',
                'description' => tra('Can edit media information'),
                'level' => 'editors',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_delete_videos',
                'description' => tra('Can delete media'),
                'level' => 'editors',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_download_videos',
                'description' => tra('Can download media'),
                'level' => 'registered',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_list_videos',
                'description' => tra('Can list media'),
                'level' => 'basic',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_videos',
                'description' => tra('Can view media'),
                'level' => 'basic',
                'type' => 'media',
                'admin' => false,
                'prefs' => ['feature_kaltura'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_broadcast_all',
                'description' => tra('Can broadcast messages to all users'),
                'level' => 'admin',
                'type' => 'messages',
                'admin' => false,
                'prefs' => ['feature_messages'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_broadcast',
                'description' => tra('Can broadcast messages to groups'),
                'level' => 'admin',
                'type' => 'group',
                'admin' => false,
                'prefs' => ['feature_messages'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_messages',
                'description' => tra('Can use the messaging system'),
                'level' => 'registered',
                'type' => 'messages',
                'admin' => false,
                'prefs' => ['feature_messages'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_newsletters',
                'description' => tra('Can admin newsletters'),
                'level' => 'admin',
                'type' => 'newsletters',
                'admin' => true,
                'prefs' => ['feature_newsletters'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_batch_subscribe_email',
                'description' => tra('Can subscribe multiple email addresses at once (requires tiki_p_subscribe email)'),
                'level' => 'editors',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_send_newsletters',
                'description' => tra('Can send newsletters'),
                'level' => 'editors',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_subscribe_email',
                'description' => tra('Can subscribe any email address to newsletters'),
                'level' => 'editors',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_subscribe_newsletters',
                'description' => tra('Can subscribe to newsletters'),
                'level' => 'basic',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_newsletter',
                'description' => tra('Can view the archive of a newsletters'),
                'level' => 'basic',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_list_newsletters',
                'description' => tra('Can list newsletters'),
                'level' => 'basic',
                'type' => 'newsletters',
                'admin' => false,
                'prefs' => ['feature_newsletters'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_payment_admin',
                'description' => tra('Can administer payments'),
                'level' => 'admin',
                'type' => 'payment',
                'admin' => true,
                'prefs' => ['payment_feature'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_payment_view',
                'description' => tra('Can view payment requests and details'),
                'level' => 'admin',
                'type' => 'payment',
                'admin' => false,
                'prefs' => ['payment_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_payment_manual',
                'description' => tra('Can enter manual payments'),
                'level' => 'admin',
                'type' => 'payment',
                'admin' => false,
                'prefs' => ['payment_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_payment_request',
                'description' => tra('Can request a payment'),
                'level' => 'admin',
                'type' => 'payment',
                'admin' => false,
                'prefs' => ['payment_feature'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_perspective_view',
                'description' => tra('Can view the perspective'),
                'level' => 'basic',
                'type' => 'perspective',
                'admin' => false,
                'prefs' => ['feature_perspective'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_perspective_edit',
                'description' => tra('Can edit the perspective'),
                'level' => 'basic',
                'type' => 'perspective',
                'admin' => false,
                'prefs' => ['feature_perspective'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_perspective_create',
                'description' => tra('Can create a perspective'),
                'level' => 'basic',
                'type' => 'perspective',
                'admin' => false,
                'prefs' => ['feature_perspective'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_perspective_admin',
                'description' => tra('Can admin perspectives'),
                'level' => 'admin',
                'type' => 'perspective',
                'admin' => true,
                'prefs' => ['feature_perspective'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_polls',
                'description' => tra('Can admin polls'),
                'level' => 'admin',
                'type' => 'polls',
                'admin' => true,
                'prefs' => ['feature_polls'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_poll_results',
                'description' => tra('Can view poll results'),
                'level' => 'basic',
                'type' => 'polls',
                'admin' => false,
                'prefs' => ['feature_polls'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_poll_choices',
                'description' => tra('Can view poll user choices'),
                'level' => 'basic',
                'type' => 'polls',
                'admin' => false,
                'prefs' => ['feature_polls'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_vote_poll',
                'description' => tra('Can vote in polls'),
                'level' => 'basic',
                'type' => 'polls',
                'admin' => false,
                'prefs' => ['feature_polls'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_poll_voters',
                'description' => tra('Can view poll voters'),
                'level' => 'basic',
                'type' => 'polls',
                'admin' => false,
                'prefs' => ['feature_polls'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_quizzes',
                'description' => tra('Can admin quizzes'),
                'level' => 'editors',
                'type' => 'quizzes',
                'admin' => true,
                'prefs' => ['feature_quizzes'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_take_quiz',
                'description' => tra('Can take quizzes'),
                'level' => 'basic',
                'type' => 'quizzes',
                'admin' => false,
                'prefs' => ['feature_quizzes'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_quiz_stats',
                'description' => tra('Can view quiz stats'),
                'level' => 'basic',
                'type' => 'quizzes',
                'admin' => false,
                'prefs' => ['feature_quizzes'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_user_results',
                'description' => tra('Can view user quiz results'),
                'level' => 'editors',
                'type' => 'quizzes',
                'admin' => false,
                'prefs' => ['feature_quizzes'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_sheet',
                'description' => tra('Can admin spreadsheets'),
                'level' => 'admin',
                'type' => 'sheet',
                'admin' => true,
                'prefs' => ['feature_sheet'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_sheet',
                'description' => tra('Can create and edit spreadsheets'),
                'level' => 'editors',
                'type' => 'sheet',
                'admin' => false,
                'prefs' => ['feature_sheet'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_sheet',
                'description' => tra('Can view spreadsheets'),
                'level' => 'basic',
                'type' => 'sheet',
                'admin' => false,
                'prefs' => ['feature_sheet'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_sheet_history',
                'description' => tra('Can view spreadsheets history'),
                'level' => 'admin',
                'type' => 'sheet',
                'admin' => false,
                'prefs' => ['feature_sheet'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_shoutbox',
                'description' => tra('Can admin the shoutbox (edit/remove messages)'),
                'level' => 'editors',
                'type' => 'shoutbox',
                'admin' => true,
                'prefs' => ['feature_shoutbox'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_post_shoutbox',
                'description' => tra('Can post messages in the shoutbox'),
                'level' => 'basic',
                'type' => 'shoutbox',
                'admin' => false,
                'prefs' => ['feature_shoutbox'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_shoutbox',
                'description' => tra('Can view the shoutbox'),
                'level' => 'basic',
                'type' => 'shoutbox',
                'admin' => false,
                'prefs' => ['feature_shoutbox'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_socialnetworks',
                'description' => tra('Can use social network integration'),
                'level' => 'registered',
                'type' => 'socialnetworks',
                'admin' => false,
                'prefs' => ['feature_socialnetworks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_socialnetworks',
                'description' => tra('Can register this site with social networks'),
                'level' => 'admin',
                'type' => 'socialnetworks',
                'admin' => true,
                'prefs' => ['feature_socialnetworks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_live_support_admin',
                'description' => tra('Admin live support system'),
                'level' => 'admin',
                'type' => 'support',
                'admin' => true,
                'prefs' => ['feature_live_support'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_live_support',
                'description' => tra('Can use live support system'),
                'level' => 'basic',
                'type' => 'support',
                'admin' => false,
                'prefs' => ['feature_live_support'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_surveys',
                'description' => tra('Can admin surveys'),
                'level' => 'editors',
                'type' => 'surveys',
                'admin' => true,
                'prefs' => ['feature_surveys'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_take_survey',
                'description' => tra('Can take surveys'),
                'level' => 'basic',
                'type' => 'surveys',
                'admin' => false,
                'prefs' => ['feature_surveys'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_survey_stats',
                'description' => tra('Can view survey stats'),
                'level' => 'basic',
                'type' => 'surveys',
                'admin' => false,
                'prefs' => ['feature_surveys'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_tikitests',
                'description' => tra('Can admin TikiTests'),
                'level' => 'admin',
                'type' => 'tikitests',
                'admin' => false,
                'prefs' => ['feature_tikitests'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_tikitests',
                'description' => tra('Can edit TikiTests'),
                'level' => 'editors',
                'type' => 'tikitests',
                'admin' => false,
                'prefs' => ['feature_tikitests'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_play_tikitests',
                'description' => tra('Can replay TikiTests'),
                'level' => 'registered',
                'type' => 'tikitests',
                'admin' => false,
                'prefs' => ['feature_tikitests'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_trackers',
                'description' => tra('Can admin trackers'),
                'level' => 'admin',
                'type' => 'trackers',
                'admin' => true,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_attach_trackers',
                'description' => tra('Can attach files to tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_view_attachments',
                'description' => tra('Can view tracker item attachments and download them'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_comment_tracker_items',
                'description' => tra('Can post tracker item comments'),
                'level' => 'basic',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_view_comments',
                'description' => tra('Can view tracker item comments'),
                'level' => 'basic',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_create_tracker_items',
                'description' => tra('Can create new tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_list_trackers',
                'description' => tra('Can list trackers'),
                'level' => 'basic',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_modify_tracker_items',
                'description' => tra('Can change tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_modify_tracker_items_pending',
                'description' => tra('Can change pending tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_modify_tracker_items_closed',
                'description' => tra('Can change closed tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_tracker_items',
                'description' => tra('Can remove tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_tracker_items_pending',
                'description' => tra('Can remove pending tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove_tracker_items_closed',
                'description' => tra('Can remove closed tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_view_ratings',
                'description' => tra('Can view rating result for tracker items'),
                'level' => 'basic',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_vote_ratings',
                'description' => tra('Can rate tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_revote_ratings',
                'description' => tra('Can re-rate tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_trackers',
                'description' => tra('Can view trackers'),
                'level' => 'basic',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_trackers_closed',
                'description' => tra('Can view closed trackers items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_trackers_pending',
                'description' => tra('Can view pending trackers items'),
                'level' => 'editors',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_watch_trackers',
                'description' => tra('Can watch a tracker'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_export_tracker',
                'description' => tra('Can export tracker items'),
                'level' => 'registered',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tracker_dump',
                'description' => tra('Can save a CSV backup of all trackers'),
                'level' => 'admin',
                'type' => 'trackers',
                'admin' => false,
                'prefs' => ['feature_trackers'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_tabular_admin',
                'description' => tr('Manage tracker views'),
                'level' => 'admin',
                'type' => 'tabular',
                'admin' => true,
                'prefs' => ['tracker_tabular_enabled'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tabular_list',
                'description' => tr('View list view of tracker tabular data. Tracker item permissions apply.'),
                'level' => 'registered',
                'type' => 'tabular',
                'admin' => false,
                'prefs' => ['tracker_tabular_enabled'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tabular_export',
                'description' => tr('Export a tracker tabular view to CSV. Tracker permissions may not apply.'),
                'level' => 'editors',
                'type' => 'tabular',
                'admin' => false,
                'prefs' => ['tracker_tabular_enabled'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_tabular_import',
                'description' => tr('Import a CSV file into a tabular tracker. Tracker permissions may not apply.'),
                'level' => 'editors',
                'type' => 'tabular',
                'admin' => false,
                'prefs' => ['tracker_tabular_enabled'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_trigger_transition',
                'description' => tra('Can trigger the transition between two states'),
                'level' => 'admin',
                'type' => 'transition',
                'admin' => false,
                'prefs' => ['feature_group_transition', 'feature_category_transition'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_users',
                'description' => tra('Can admin users'),
                'level' => 'admin',
                'type' => 'user',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_cache_bookmarks',
                'description' => tra('Can cache user bookmarks'),
                'level' => 'admin',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_user_bookmarks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_configure_modules',
                'description' => tra('Can configure modules'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_modulecontrols'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_create_bookmarks',
                'description' => tra('Can create user bookmarks'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_user_bookmarks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_minical',
                'description' => tra('Can use the mini event calendar'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_minical'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_notepad',
                'description' => tra('Can use the notepad'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_notepad'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_tasks_admin',
                'description' => tra('Can admin public tasks'),
                'level' => 'admin',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_tasks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_tasks',
                'description' => tra('Can use tasks'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_tasks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_tasks_receive',
                'description' => tra('Can receive tasks from other users'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_tasks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_tasks_send',
                'description' => tra('Can send tasks to other users'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_tasks'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_userfiles',
                'description' => tra('Can upload personal files'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_userfiles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_usermenu',
                'description' => tra('Can create items in personal menu'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_usermenu'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_list_users',
                'description' => tra('Can list registered users'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_invite',
                'description' => tra('Can invite users by email, and include them in groups'),
                'level' => 'registered',
                'type' => 'user',
                'admin' => false,
                'prefs' => ['feature_invite'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_delete_account',
                'description' => tra('Can delete his/her own account'),
                'level' => 'admin',
                'type' => 'user',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_use_webmail',
                'description' => tra('Can use webmail'),
                'level' => 'registered',
                'type' => 'webmail',
                'admin' => false,
                'prefs' => ['feature_webmail', 'feature_contacts'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_use_group_webmail',
                'description' => tra('Can use group webmail'),
                'level' => 'registered',
                'type' => 'webmail',
                'admin' => false,
                'prefs' => ['feature_webmail', 'feature_contacts'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_group_webmail',
                'description' => tra('Can admin group webmail accounts'),
                'level' => 'admin',
                'type' => 'webmail',
                'admin' => false,
                'prefs' => ['feature_webmail', 'feature_contacts'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_use_personal_webmail',
                'description' => tra('Can use personal webmail accounts'),
                'level' => 'registered',
                'type' => 'webmail',
                'admin' => false,
                'prefs' => ['feature_webmail', 'feature_contacts'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_personal_webmail',
                'description' => tra('Can admin personal webmail accounts'),
                'level' => 'registered',
                'type' => 'webmail',
                'admin' => false,
                'prefs' => ['feature_webmail', 'feature_contacts'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view',
                'description' => tra('Can view page/pages'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit',
                'description' => tra('Can edit pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_inline',
                'description' => tra('Can inline-edit pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_history',
                'description' => tra('Can view wiki history'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_history'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_wiki',
                'description' => tra('Can admin the wiki'),
                'level' => 'admin',
                'type' => 'wiki',
                'admin' => true,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_assign_perm_wiki_page',
                'description' => tra('Can assign permissions to wiki pages'),
                'level' => 'admin',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_copyrights',
                'description' => tra('Can edit copyright notices'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['wiki_feature_copyrights'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_dynvar',
                'description' => tra('Can edit dynamic variables'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_export_wiki',
                'description' => tra('Can export wiki pages using the export feature'),
                'level' => 'admin',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_export'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_lock',
                'description' => tra('Can lock pages'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_usrlock'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_minor',
                'description' => tra('Can save as a minor edit'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['wiki_edit_minor'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_remove',
                'description' => tra('Can remove'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_rename',
                'description' => tra('Can rename pages'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_rollback',
                'description' => tra('Can roll back pages'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_upload_picture',
                'description' => tra('Can upload pictures to wiki pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_pictures'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_use_as_template',
                'description' => tra('Can use the page as a template for a tracker or unified search'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_ref',
                'description' => tra('Can view in module and feed the wiki pages reference'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_admin_attachments',
                'description' => tra('Can admin attachments on wiki pages'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_attachments'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_admin_ratings',
                'description' => tra('Can add and change ratings on wiki pages'),
                'level' => 'admin',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_ratings'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_wiki_attach_files',
                'description' => tra('Can attach files to wiki pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_attachments'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_attachments',
                'description' => tra('Can view and download wiki page attachments'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_attachments'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_comments',
                'description' => tra('Can view wiki comments'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_comments'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_ratings',
                'description' => tra('Can view rating of wiki pages'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_ratings'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_source',
                'description' => tra('Can view source of wiki pages'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_source'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_vote_ratings',
                'description' => tra('Can participate in rating of wiki pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_wiki_ratings'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_similar',
                'description' => tra('Can view similar wiki pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_likePages'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_view_backlink',
                'description' => tra('View page backlinks'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_backlinks'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_view_latest',
                'description' => tra('Can view unapproved revisions of pages'),
                'level' => 'registered',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['flaggedrev_approval'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_wiki_approve',
                'description' => tra('Can approve revisions of pages'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['flaggedrev_approval'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_page_contribution_view',
                'description' => tra('Can view contributions to a page'),
                'level' => 'basic',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_page_contribution'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_use_references',
                'description' => tra('Can use reference library items'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_references'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_references',
                'description' => tra('Can add to, edit and remove reference library items'),
                'level' => 'editors',
                'type' => 'wiki',
                'admin' => false,
                'prefs' => ['feature_references'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_admin_structures',
                'description' => tra('Can administer structures'),
                'level' => 'admin',
                'type' => 'wiki structure',		// NB "wiki structure" objects use the perms set on the top "wiki page"
                'admin' => true,
                'prefs' => ['feature_wiki_structure'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_edit_structures',
                'description' => tra('Can create and edit structures'),
                'level' => 'editors',
                'type' => 'wiki structure',		// NB "wiki structure" objects use the perms set on the top "wiki page"
                'admin' => false,
                'prefs' => ['feature_wiki_structure'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_lock_structures',
                'description' => tra('Can lock structures'),
                'level' => 'editors',
                'type' => 'wiki structure',		// NB "wiki structure" objects use the perms set on the top "wiki page"
                'admin' => false,
                'prefs' => ['feature_wiki_structure', 'lock_wiki_structures'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_watch_structure',
                'description' => tra('Can watch structures'),
                'level' => 'registered',
                'type' => 'wiki structure',		// NB "wiki structure" objects use the perms set on the top "wiki page"
                'admin' => false,
                'prefs' => ['feature_wiki_structure'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin',
                'description' => tra('Administrator can manage users, groups and permissions and all features'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => true,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_grouplimitedinfo',
                'description' => tra('Can edit the name and description of a group.'),
                'level' => 'admin',
                'type' => 'group',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_access_closed_site',
                'description' => tra('Can access site when closed'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_banners',
                'description' => tra('Administrator can admin banners'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_banners'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_banning',
                'description' => tra('Can ban users or IP addresses'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_banning'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_dynamic',
                'description' => tra('Can admin the dynamic content system'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_dynamic_content'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_integrator',
                'description' => tra('Can admin integrator repositories and rules'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_integrator'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_send_mailin',
                'description' => tra('Can send email to a mail-in accounts, and have the email integrated. Only applies when the mail-in setting "anonymous" = n'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_mailin', 'feature_wiki'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_mailin',
                'description' => tra('Can admin mail-in accounts'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_mailin'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_objects',
                'description' => tra('Can edit object permissions'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_rssmodules',
                'description' => tra('Can admin external feeds'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_clean_cache',
                'description' => tra('Can clean cache'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_create_css',
                'description' => tra('Can create a new CSS file (style sheet) appended with -user'),
                'level' => 'registered',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_editcss'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_detach_translation',
                'description' => tra('Can remove the association between two pages in a translation set'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_multilingual'],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles'],
            ],
            [
                'name' => 'tiki_p_edit_cookies',
                'description' => tra('Can admin cookies'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_languages',
                'description' => tra('Can edit translations and create new languages'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_menu',
                'description' => tra('Can edit menus'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_menu_option',
                'description' => tra('Can edit menu options'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_templates',
                'description' => tra('Can edit site templates'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_edit_templates'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_search',
                'description' => tra('Can search'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [], // This could depend on feature_search when FULLTEXT search (feature_search_fulltext) is removed
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_site_report',
                'description' => tra('Can report a link to the webmaster'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_site_report'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_share',
                'description' => tra('Can share a page (email, Twitter, Facebook, message, forums)'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_share'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_use_HTML',
                'description' => tra('Can use HTML in pages'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_wiki_allowhtml', 'feature_articles'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_actionlog',
                'description' => tra('Can view action log'),
                'level' => 'registered',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_actionlog'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_actionlog_owngroups',
                'description' => tra('Can view the action log for users of his or her groups'),
                'level' => 'registered',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_actionlog'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_integrator',
                'description' => tra('Can view integrated repositories'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_integrator'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_ratings_view_results',
                'description' => tra('Can view results from user ratings'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers', 'articles', 'comments', 'forums'],
            ],
            [
                'name' => 'tiki_p_view_referer_stats',
                'description' => tra('Can view referrer stats'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_referer_stats'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_stats',
                'description' => tra('Can view site stats'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_stats'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_templates',
                'description' => tra('Can view site templates'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_edit_templates'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_view_webservices',
                'description' => tra('Can view results from webservice requests'),
                'level' => 'basic',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_webservices'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_webservices',
                'description' => tra('Can administer webservices'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_webservices'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_toolbars',
                'description' => tra('Can admin toolbars'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_trust_input',
                'description' => tra('Trust all user inputs including plugins (no security checks)'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['tiki_allow_trust_input'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_plugin_viewdetail',
                'description' => tra('Can view unapproved plugin details'),
                'level' => 'registered',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_plugin_preview',
                'description' => tra('Can execute unapproved plugin registered'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_plugin_approve',
                'description' => tra('Can approve plugin execution'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_wiki'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_notifications',
                'description' => tra('Can admin mail notifications'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_admin_importer',
                'description' => tra('Can use the importer'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_modify_object_categories',
                'description' => tra('Can change the categories of an object'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_categories'],
                'scope' => 'object',
                'apply_to' => ['wiki', 'trackers'],
            ],
            [
                'name' => 'tiki_p_admin_modules',
                'description' => tra('User can administer modules'),
                'level' => 'admin',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => [],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_edit_switch_mode',
                'description' => tra('Can switch between wiki and WYSIWYG modes while editing'),
                'level' => 'editors',
                'type' => 'tiki',
                'admin' => false,
                'prefs' => ['feature_wysiwyg'],
                'scope' => 'global',
            ],
            [
                'name' => 'tiki_p_workspace_instantiate',
                'description' => tra('Can create a new workspace for a given template'),
                'level' => 'admin',
                'type' => 'workspace',
                'admin' => false,
                'prefs' => ['workspace_ui'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_goal_admin',
                'description' => tr('Can manage all aspects of a goal'),
                'level' => 'admin',
                'type' => 'goal',
                'admin' => true,
                'prefs' => ['goal_enabled'],
                'scope' => 'object',
            ],
            [
                'name' => 'tiki_p_goal_modify_eligible',
                'description' => tr('Can manage who is eligible for a goal'),
                'level' => 'admin',
                'type' => 'goal',
                'admin' => false,
                'prefs' => ['goal_enabled'],
                'scope' => 'object',
            ],
        ];

        $permissions = $this->getSortingPermissions($permissions);

        $cachelib->cacheItem('rawpermissions' . $prefs['language'], serialize($permissions));

        return $permissions;
    }

    public function get_permissions($offset = 0, $maxRecords = -1, $sort_mode = 'permName_asc', $find = '', $type = 'all', $group = '', $enabledOnly = false)
    {
        if ($enabledOnly) {
            $raw = $this->get_enabled_permissions();
        } else {
            $raw = $this->get_raw_permissions();
        }

        $ret = [];

        foreach ($raw as $permission) {
            if ($find && stripos($permission['name'], $find) === false) {
                continue;
            }

            if ($type === 'global' || $type == 'all') {
                $ret[] = $this->permission_compatibility($permission);
            } elseif ($type == $permission['type'] && $permission['scope'] == 'object') {
                $ret[] = $this->permission_compatibility($permission);
            } elseif ($type == 'category' && $permission['scope'] != 'global') {
                $ret[] = $this->permission_compatibility($permission);
            } elseif ($permission['scope'] == 'object' && isset($permission['apply_to']) && in_array($type, $permission['apply_to'])) {
                $ret[] = $this->permission_compatibility($permission);
            }
        }

        if ($group) {
            if (is_string($group)) {
                foreach ($ret as &$res) {
                    if ($this->group_has_permission($group, $res['permName'])) {
                        $res['hasPerm'] = 'y';
                    } else {
                        $res['hasPerm'] = 'n';
                    }
                }
            } elseif (is_array($group)) {
                foreach ($ret as &$res) {
                    foreach ($group as $groupName) {
                        if ($this->group_has_permission($groupName, $res['permName'])) {
                            $res[$groupName . '_hasPerm'] = 'y';
                        } else {
                            $res[$groupName . '_hasPerm'] = 'n';
                        }
                    }
                }
            }
        }

        return [
            'data' => $ret,
            'cant' => count($ret),
        ];
    }

    private function permission_compatibility($newFormat)
    {
        $newFormat['shortName'] = substr($newFormat['name'], strlen('tiki_p_'));
        $newFormat['permName'] = $newFormat['name'];
        $newFormat['permDesc'] = $newFormat['description'];
        $newFormat['feature_checks'] = implode(',', $newFormat['prefs']);

        return $newFormat;
    }

    public function get_permission_types()
    {
        $ret = [];

        foreach ($this->get_raw_permissions() as $perm) {
            if (! isset($ret[$perm['type']])) {
                $ret[$perm['type']] = true;
            }
        }

        return array_keys($ret);
    }

    public function get_group_permissions($group)
    {
        $cachelib = TikiLib::lib('cache');
        if (! $ret = $cachelib->getSerialized("groupperms_$group")) {
            $query = 'select `permName` from `users_grouppermissions` where `groupName`=?';
            $result = $this->query($query, [$group]);
            $ret = [];

            while ($res = $result->fetchRow()) {
                $ret[] = $res['permName'];
            }

            $cachelib->cacheItem("groupperms_$group", serialize($ret));
        }

        return $ret;
    }

    public function assign_permission_to_group($perm, $group)
    {
        $query = 'delete from `users_grouppermissions` where `groupName` = ? and `permName` = ?';
        $result = $this->query($query, [$group, $perm]);

        $query = 'insert into `users_grouppermissions`(`groupName`, `permName`) values(?, ?)';
        $result = $this->query($query, [$group, $perm]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('fgals_perms');
        $cachelib->invalidate("groupperms_$group");

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();

        return true;
    }

    public function get_user_permissions($user)
    {
        $groups = $this->get_user_groups($user);

        $ret = [];
        foreach ($groups as $group) {
            $perms = $this->get_group_permissions($group);

            foreach ($perms as $perm) {
                $ret[] = $perm;
            }
        }

        return $ret;
    }

    public function user_has_permission($user, $perm)
    {
        // Get user_groups ?
        $groups = $this->get_user_groups($user);

        foreach ($groups as $group) {
            if ($this->group_has_permission($group, $perm) || $this->group_has_permission($group, 'tiki_p_admin')) {
                return true;
            }
        }

        return false;
    }

    public function group_has_permission($group, $perm)
    {
        if (empty($perm) || empty($group)) {
            return 0;
        }

        $engroup = urlencode($group);
        if (! isset($this->groupperm_cache[$engroup])) {
            $this->groupperm_cache[$engroup] = [];
            $groupperms = $this->get_group_permissions($group);
            foreach ($groupperms as $gp) {
                $this->groupperm_cache[$engroup][$gp] = 1;
            }
        }

        return isset($this->groupperm_cache[$engroup][$perm]) ? 1 : 0;
    }

    public function remove_permission_from_group($perm, $group)
    {
        $query = "delete from `users_grouppermissions` where `permName` = ? and `groupName` = ?";
        $result = $this->query($query, [$perm, $group]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache("fgals_perms");
        $cachelib->invalidate("groupperms_$group");

        $menulib = TikiLib::lib('menu');
        $menulib->empty_menu_cache();

        return true;
    }

    public function get_group_info($group, $sort_mode = 'groupName_asc')
    {
        $ret = [];
        if (is_array($group)) {
            if (count($group) > 0) {
                $query = 'select * from `users_groups` where `groupName` in (' .
                    implode(',', array_fill(0, count($group), '?')) .
                    ') order by ' . $this->convertSortMode($sort_mode);
                $ret = $this->fetchAll($query, $group);
            }
        } else {
            $query = 'select * from `users_groups` where `groupName`=?';
            $result = $this->query($query, [$group]);
            $ret = $result->fetchRow();
            $perms = $this->get_group_permissions($group);
            $ret['perms'] = $perms;
        }

        return $ret;
    }

    public function get_groupId_info($groupId)
    {
        $query = 'select * from `users_groups` where `id`=?';

        $result = $this->query($query, [$groupId]);
        $res = $result->fetchRow();
        $perms = $this->get_group_permissions($res['groupName']);
        $res['perms'] = $perms;

        return $res;
    }

    /**
     * @param      $user
     * @param      $group
     * @param bool $bulk
     *
     * @throws Services_Exception
     * @return bool|TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function assign_user_to_group($user, $group, $bulk = false)
    {
        if (! $this->group_exists($group)) {
            throw new Exception(tr('Cannot add user %0 to nonexistent group %1', $user, $group));
        }
        if (! $this->user_exists($user)) {
            throw new Exception(tr('Cannot add nonexistent user %0 to group %1', $user, $group));
        }

        $groupInfo = $this->get_group_info($group);
        if ($groupInfo["isRole"] == "y") {
            throw new Exception(tr('Role groups can\'t have users.'));
        }

        global $prefs, $tiki_p_admin, $page;
        $cachelib = TikiLib::lib('cache');
        $tikilib = TikiLib::lib('tiki');
        $access = TikiLib::lib('access');

        if ($this->is_user_banned_from_group($user, $group)) {
            $msg = tr('User "%0" is banned from the group "%1".', $user, $group);
            if ($tiki_p_admin === 'y') {
                $access->check_authenticity($msg . ' ' . tra('Do you want to unban them and continue?'));
                $this->unban_user_from_group($user, $group);
            } else {
                $access->display_error($page, $msg);
            }
        }

        $cachelib->invalidate('user_details_' . $user);
        $tikilib->invalidate_usergroups_cache($user);
        $this->invalidate_usergroups_cache($user); // this is needed as cache is present in this instance too

        $result = false;
        $userid = $this->get_user_id($user);

        if ($userid > 0) {
            $query = "insert ignore into `users_usergroups`(`userId`,`groupName`, `created`) values(?,?,?)";
            $result = $this->query($query, [$userid, $group, $tikilib->now], -1, -1, false);
        }
        $this->update_group_expiries();

        if ($result && $result->numRows()) {
            $watches = $tikilib->get_event_watches('user_joins_group', $group);
            if (count($watches)) {
                require_once("lib/notifications/notificationemaillib.php");
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('mail_user', $user);
                $smarty->assign('mail_group', $group);
                sendEmailNotification($watches, null, 'user_joins_group_notification_subject.tpl', null, 'user_joins_group_notification.tpl');
            }
            TikiLib::events()->trigger('tiki.user.groupjoin', [
                'type' => 'user',
                'object' => $user,
                'group' => $group,
                'bulk_import' => $bulk,
            ]);
        }

        return $result;
    }

    public function assign_user_to_groups($user, $groups)
    {
        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('user_details_' . $user);

        $userid = $this->get_user_id($user);

        $query = 'delete from `users_usergroups` where `userId`=?';
        $this->query($query, [$userid]);

        $lastkey = end($groups);
        foreach ($groups as $k => $grp) {
            $this->assign_user_to_group($user, $grp, $k != $lastkey);
        }
    }

    public function ban_user_from_group($user, $group)
    {
        TikiLib::lib('relation')->add_relation('tiki.user.banned', 'user', $user, 'group', $group);
    }

    public function unban_user_from_group($user, $group)
    {
        $relationlib = TikiLib::lib('relation');
        $id = $relationlib->get_relation_id('tiki.user.banned', 'user', $user, 'group', $group);
        if ($id) {
            $relationlib->remove_relation($id);
        }
    }

    public function get_group_banned_users($group, $offset = 0, $max = -1, $what = 'login', $sort_mode = 'source_itemId_asc')
    {
        $res = TikiLib::lib('relation')->get_relations_to('group', $group, 'tiki.user.banned', $sort_mode);
        $temp = [];
        foreach ($res as $r) {
            $temp[] = $r['itemId'];
        }
        $max = $max > 0 ? $max : null;
        $ret['data'] = array_slice($temp, $offset, $max);
        $ret['cant'] = count($res);

        return $ret;
    }

    public function is_user_banned_from_group($user, $group)
    {
        return TikiLib::lib('relation')->get_relation_id('tiki.user.banned', 'user', $user, 'group', $group) > 0;
    }


    public function confirm_user($user)
    {
        $cachelib = TikiLib::lib('cache');

        $query = 'update `users_users` set `provpass`=?, valid=?, `email_confirm`=?, `waiting`=?, `registrationDate`=? where `login`=?';
        $result = $this->query($query, ['', null, $this->now, null, $this->now, $user]);
        $cachelib->invalidate('userslist');
        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);
    }

    public function invalidate_account($user)
    {
        $cachelib = TikiLib::lib('cache');
        $tikilib = TikiLib::lib('tiki');

        $query = 'update `users_users` set valid=?, `waiting`=? where `login`=?';
        $result = $this->query($query, [md5($tikilib->genPass()), 'u', $user]);
        $cachelib->invalidate('userslist');
        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);
    }

    public function change_user_waiting($user, $who)
    {
        $query = 'update `users_users` set `waiting`=? where `login`=?';
        $this->query($query, [$who, $user]);
        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);
    }

    /**
     * Adds a user in Tiki.
     *
     * @param user: username
     * @param pass: password (may be an empty string)
     * @param email: email
     * @param mixed $user
     * @param mixed $pass
     * @param mixed $email
     * @param mixed $provpass
     * @param mixed $pass_first_login
     * @param null|mixed $valid
     * @param null|mixed $openid_url
     * @param null|mixed $waiting
     * @param mixed $groups
     */
    public function add_user($user, $pass, $email, $provpass = '', $pass_first_login = false, $valid = null, $openid_url = null, $waiting = null, $groups = [])
    {
        global $prefs;
        $cachelib = TikiLib::lib('cache');
        $tikilib = TikiLib::lib('tiki');

        $autogenerate_uname = false;
        if ($prefs['login_autogenerate'] == 'y' && $user == '') {
            // only autogenerate if no username is provided (as many features might want to create real user name)
            // need to create as tmp uname first before replacing with user ID based number
            $user = "tmp" . md5((string) rand());
            $autogenerate_uname = true;
        }

        $user = trim($user);

        if ($this->user_exists($user)
                || empty($user)
                || (! empty($prefs['username_pattern']) && ! preg_match($prefs['username_pattern'], $user))
                || strtolower($user) == 'anonymous'
                || strtolower($user) == 'registered'
        ) {
            return false;
        }

        if ($prefs['user_unique_email'] == 'y' && $this->get_user_by_email($email)) {
            if ($autogenerate_uname) {
                // If the user to be added is to be autogenerated and the email already exists it means the user
                // is already created, for example in the 2nd pass in the registration process. To silently exit.
                return false;
            }
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('errortype', 'login');
            $smarty->assign('msg', tra('We were unable to create your account because this email is already in use.'));
            $smarty->display('error.tpl');
            die;
        }

        $userexists_cache[$user] = null;

        // Generate a unique hash; this is also done below in set_user_fields()
        $lastLogin = null;
        if (empty($openid_url)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
        } else {
            $hash = '';
            if (! isset($prefs['validateRegistration']) || $prefs['validateRegistration'] != 'y') {
                $lastLogin = $tikilib->now;
            }
        }

        if ($pass_first_login) {
            $new_pass_confirm = 0;
        } else {
            $new_pass_confirm = $this->now;
        }
        $new_email_confirm = $this->now;
        $userTable = $this->table('users_users');
        $userId = $userTable->insert(
            [
                'login' => $user,
                'email' => $email,
                'provpass' => $provpass,
                'registrationDate' => (int) $this->now,
                'hash' => $hash,
                'pass_confirm' => (int) $new_pass_confirm,
                'email_confirm' => (int) $new_email_confirm,
                'created' => (int) $this->now,
                'valid' => $valid,
                'openid_url' => $openid_url,
                'lastLogin' => $lastLogin,
                'waiting' => $waiting,
            ]
        );

        if ($autogenerate_uname) {
            // only autogenerate if no username is provided (as many features might want to create real user name)
            $user = $this->autogenerate_login($userId);
            $userTable->update(
                [
                    'login' => $user,
                ],
                [
                    'userId' => $userId,
                ]
            );
        }

        if (empty($groups)) {
            $this->assign_user_to_group($user, 'Registered');
        } else {
            if (is_array($groups)) {
                foreach ($groups as $grp) {
                    $this->assign_user_to_group($user, $grp);
                }
            } else {
                $this->assign_user_to_group($user, 'Registered');
            }
        }

        if ($prefs['eponymousGroups'] == 'y') {
            // Create a group just for this user, for permissions
            // assignment.
            $this->add_group($user, "Personal group for $user.", '', 0, 0, 0, '');

            $this->assign_user_to_group($user, $user);
        }

        $this->set_user_default_preferences($user, false); // do not force

        if (! empty($prefs['user_tracker_auto_assign_item_field'])) {
            // try to assign the user tracker item if exists
            TikiLib::lib('trk')->update_user_item($user, $email, $prefs['user_tracker_auto_assign_item_field']);
        }

        $cachelib->invalidate('userslist');

        TikiLib::events()->trigger('tiki.user.create', [
            'type' => 'user',
            'object' => $user,
            'userId' => $userId,
        ]);

        return $user;
    }

    public function autogenerate_login($userId, $digits = 6)
    {
        //create unique hash based on $userId, between 0 and 999999 (if digits = 6)
        $userHash = $userId * pow(9, $digits) % (pow(10, $digits));

        return sprintf('%0' . $digits . 'd', $userHash); //add leading 0's
    }

    public function set_user_default_preferences($user, $force = true)
    {
        global $prefs;
        foreach ($prefs as $pref => $value) {
            if (! preg_match('/^users_prefs_/', $pref)) {
                continue;
            }
            if ($pref == 'users_prefs_email_is_public') {
                $pref_name = 'email is public';
            } else {
                $pref_name = substr($pref, 12);
            }
            if ($force || is_null($this->get_user_preference($user, $pref_name))) {
                $this->set_user_preference($user, $pref_name, $value);
            }
        }

        if ($prefs['change_language'] == 'y' && $prefs['site_language'] != $prefs['language']) {
            $this->set_user_preference($user, 'language', $prefs['language']);
        }
    }

    public function change_user_email_only($user, $email)
    {
        global $prefs;
        if ($prefs['user_unique_email'] == 'y' && $this->other_user_has_email($user, $email)) {
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('errortype', 'login');
            $smarty->assign('msg', tra('Email cannot be set because this email is already in use by another user.'));
            $smarty->display('error.tpl');
            die;
        }
        $query = 'update `users_users` set `email`=? where binary `login`=?';
        $result = $this->query($query, [$email, $user]);
    }

    public function change_user_email($user, $email, $pass = null)
    {
        global $prefs;
        if ($prefs['user_unique_email'] == 'y' && $this->other_user_has_email($user, $email)) {
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('errortype', 'login');
            $smarty->assign('msg', tra('Email cannot be set because this email is already in use by another user.'));
            $smarty->display('error.tpl');
            die;
        }

        // Need to change the email-address for notifications, too
        $notificationlib = TikiLib::lib('notification');
        $oldMail = $this->get_user_email($user);
        $notificationlib->update_mail_address($user, $oldMail, $email);

        $this->change_user_email_only($user, $email);

        // that block stays here for a time (compatibility)
        // lfagundes - only if pass is provided, admin doesn't need it
        // is this still necessary?
        if (! empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $query = 'update `users_users` set `hash`=? where binary `login`=?';
            $result = $this->query($query, [$hash, $user]);
        }

        $query = 'update `tiki_user_watches` set `email`=? where binary `user`=?';
        $result = $this->query($query, [ $email, $user]);

        $query = 'update `tiki_live_support_requests` set `email`=? where binary `user`=?';
        $result = $this->query($query, [ $email, $user]);

        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);

        return true;
    }

    public function get_user_email($user)
    {
        global $prefs;

        if (($prefs['login_is_email'] == 'y' && $user != 'admin')) {
            return $this->user_exists($user) ? $user : '';
        }

        return $this->getOne('select `email` from `users_users` where binary `login`=?', [$user]);
    }

    public function get_userId_what($userIds, $what = 'email')
    {
        $query = "select `$what` from `users_users` where `userId` in (" . implode(',', array_fill(0, count($userIds), '?')) . ')';
        $result = $this->query($query, $userIds);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res[$what];
        }

        return $ret;
    }

    /**
     * Returns the contact users' email if set and permitted by Admin->Features settings
     */
    public function get_admin_email()
    {
        global $user, $prefs, $tikilib;
        if ((! isset($user) && isset($prefs['contact_anon']) && $prefs['contact_anon'] == 'y') ||
                (isset($user) && $user != '' && isset($prefs['feature_contact']) && $prefs['feature_contact'] == 'y')
        ) {
            return isset($prefs['sender_email']) ? $prefs['sender_email'] : $this->get_user_email($prefs['contact_user']);
        }
    }

    public function create_user_cookie($user, $secret = false)
    {
        global $prefs;
        if (! $secret) {
            $secret = $this->get_cookie_check();
        }
        if ($prefs['login_multiple_forbidden'] === 'y') {
            $this->delete_user_cookie($user);
        }

        $query = 'insert into `tiki_user_login_cookies`(`userId`, `secret`, `expiration`) values(?, ?, FROM_UNIXTIME(?))';
        $result = $this->query($query, [$user, $secret, $this->now + $prefs['remembertime']]);

        return $secret;
    }

    public function delete_user_cookie($user, $secret = '')
    {
        $query = 'delete from `tiki_user_login_cookies` where `userId`=?';
        $vars = [(int) $user];
        if ($secret) {
            $query .= ' and `secret`=?';
            $vars[] = $secret;
        }
        $this->query($query, $vars);
    }

    public function get_cookie_check()
    {
        // generate random string but remove fullstops as they are used as the delimiter
        return str_replace('.', chr(rand(48, 126)), TikiLib::lib('tiki')->generate_unique_sequence(32));
    }

    public function get_user_by_cookie($cookie)
    {
        list($secret, $userId) = explode('.', $cookie, 2);
        $query = 'select `userId` from `tiki_user_login_cookies` where `secret`=? and `userId`=? and `expiration` > NOW()';

        if ($userId === $this->getOne($query, [$secret, $userId])) {
            return $userId;
        }
        TikiLib::lib('logs')->add_log('login', 'get_user_by_cookie failed', $userId);

        return false;
    }

    public function get_user_by_email($email)
    {
        $query = 'select `login` from `users_users` where upper(`email`)=?';
        $pass = $this->getOne($query, [TikiLib::strtoupper($email)]);

        return $pass;
    }

    public function other_user_has_email($user, $email)
    {
        $query = 'select `login` from `users_users` where upper(`email`)=? and `login`!=?';
        $pass = $this->getOne($query, [TikiLib::strtoupper($email), $user]);

        return $pass;
    }

    public function is_due($user, $method = null)
    {
        global $prefs;
        if (empty($method)) {
            $method = $prefs['auth_method'];
        }
        // if CAS auth is enabled, don't check if password is due since CAS does not use local Tiki passwords
        if ($method == 'cas' || $method == 'ldap' || $prefs['change_password'] != 'y') {
            return false;
        }
        $confirm = $this->getOne('select `pass_confirm` from `users_users` where binary `login`=?', [$user]);
        if (! $confirm) {
            return true;
        }
        if ($prefs['pass_due'] < 0) {
            return false;
        }
        if ($confirm + (60 * 60 * 24 * $prefs['pass_due']) < $this->now) {
            return true;
        }

        return false;
    }

    public function is_email_due($user)
    {
        global $prefs;

        if ($prefs['email_due'] < 0) {
            return false;
        }

        $confirm = $this->getOne('select `email_confirm` from `users_users` where binary `login`=?', [$user]);

        if ($confirm + (60 * 60 * 24 * $prefs['email_due']) < $this->now) {
            return true;
        }

        return false;
    }

    public function unsuccessful_logins($user)
    {
        return $this->getOne('select `unsuccessful_logins` from `users_users` where binary `login`=?', [$user]);
    }

    public function renew_user_password($user)
    {
        $pass = $this->generate_provisional_password();
        // Note that tiki-generated passwords are due inmediatley
        // Note: ^ not anymore. old pw is usable until the URL in the password reminder mail is clicked
        $query = 'update `users_users` set `provpass` = ? where `login`=?';
        $result = $this->query($query, [$pass, $user]);

        return $pass;
    }

    private function generate_provisional_password()
    {
        $tikilib = TikiLib::lib('tiki');

        $site_hash = $tikilib->get_site_hash();

        $random_value = \phpseclib\Crypt\Random::string(40);

        return base64_encode(sha1($random_value . $site_hash, true));
    }

    public function activate_password($user, $actpass)
    {
        // move provpass to password and generate new hash, afterwards clean provpass
        $query = 'select `provpass` from `users_users` where `login`=?';
        $pass = $this->getOne($query, [$user]);
        if (($pass <> '') && ($actpass == md5($pass))) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $query = 'update `users_users` set `hash`=?, `pass_confirm`=? where `login`=?';
            $result = $this->query($query, [$hash, (int)$this->now, $user]);

            return $pass;
        }

        return false;
    }

    /**
     * Tests the password against policy enforcement (Admin->Login), namely
     * $min_pass_length
     * $pass_chr_num
     * $pass_ud_chr_num
     *
     * returns an empty string if password is ok, or the error string otherwise
     * @param mixed $pass
     */
    public function check_password_policy($pass)
    {
        global $prefs, $user;
        $errors = [];

        // Validate password here
        if (($prefs['auth_method'] != 'cas' || $user == 'admin') && strlen($pass) < $prefs['min_pass_length']) {
            $errors[] = tr('Password should be at least %0 characters long', $prefs['min_pass_length']);
        }

        if ($prefs['pass_chr_case'] == 'y') {
            if (! preg_match_all('/[a-z]+/', $pass) || ! preg_match_all('/[A-Z]+/', $pass)) {
                $errors[] = tra('Password must contain at least one lowercase alphabetical character like "a" and one uppercase character like "A".');
            }
        }

        if ($prefs['pass_repetition'] == 'y') {
            $chars = str_split($pass);
            $previous = '';
            foreach ($chars as $char) {
                if ($char == $previous) {
                    $errors[] = tra('Password must not contain a consecutive repetition of the same character such as "111" or "aab"');

                    break;
                }
                $previous = $char;
            }
        }

        $pass = strtolower($pass); // from here on in, we dont check upper case in the password.

        // Check this code
        if ($prefs['pass_chr_num'] == 'y') {
            if (! preg_match_all('/[0-9]+/', $pass) || ! preg_match_all('/[a-z]+/', $pass)) {
                $errors[] = tra('Password must contain both letters and numbers');
            }
        }


        if ($prefs['pass_chr_special'] == 'y') {
            if (preg_match_all('/^[0-9a-z]+$/', $pass) > 0) {
                $errors[] = tra('Password must contain at least one special character in lower case like " / $ % ? & * ( ) _ + ...');
            }
        }

        if ($prefs['pass_diff_username'] == 'y') {
            if (strtolower($user) == $pass) {
                $errors[] = tra('The password must be different from the user\'s log-in name.');
            }
        }

        if ($prefs['pass_blacklist'] === 'y') {
            $query = 'SELECT 1 FROM tiki_password_blacklist WHERE BINARY password=?;';
            $result = $this->query($query, [$pass]);
            $isCommon = $result->fetchRow();
            if ($isCommon[1] == 1) {
                $errors[] = tra('The password is blacklisted because it is too common.');
            }
        }


        return empty($errors) ? '' : implode(' ', $errors);
    }

    public function remove_2_factor_secret($user)
    {
        return $this->update_2_factor_secret($user, '');
    }

    public function generate_2_factor_secret($user)
    {
        $google2fa = new Google2FA();
        $tfaSecret = $google2fa->generateSecretKey();

        return $this->update_2_factor_secret($user, $tfaSecret);
    }

    public function update_2_factor_secret($user, $twoFASecret)
    {
        $query = 'update `users_users` set `twoFactorSecret`=? where binary `login`=?';
        $this->query($query, [$twoFASecret, $user]);

        return $twoFASecret;
    }

    public function get_2_factor_secret($user)
    {
        $query = 'select `twoFactorSecret` from `users_users` where `login`=?';

        return $this->getOne($query, [$user]);
    }

    public function validate_two_factor($twoFactorSecret, $pin)
    {
        $google2fa = new Google2FA();

        return $google2fa->verifyKey($twoFactorSecret, $pin, 2);
    }

    public function change_user_password($user, $pass, $pass_first_login = false)
    {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $new_pass_confirm = $this->now;

        if ($pass_first_login) {					// if true, set pass_confirm to force passord change upon next login
            if (! empty($pass)) {
                $query = 'update `users_users` set `hash`=? , `provpass`=?, `pass_confirm`=? where binary `login`=?';
                $this->query($query, [$hash, $pass, 0, $user]);
            } else {
                $query = 'update `users_users` set `pass_confirm`=? where binary `login`=?';
                $this->query($query, [0, $user]);
            }
        } else {
            $query = 'update `users_users` set `hash`=? ,`pass_confirm`=?, `provpass`=? where binary `login`=?';
            $this->query($query, [$hash, $new_pass_confirm, '',	$user]);
        }
        // invalidate the cache so that after a fresh install, the admin (who has no user details at the install) can log in
        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('user_details_' . $user);

        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);

        return true;
    }

    public function add_group(
        $group,
        $desc = '',
        $home = '',
        $utracker = 0,
        $gtracker = 0,
        $rufields = '',
        $userChoice = '',
        $defcat = 0,
        $theme = '',
        $ufield = 0,
        $gfield = 0,
        $isexternal = 'n',
        $expireAfter = 0,
        $emailPattern = '',
        $anniversary = '',
        $prorateInterval = '',
        $color = '',
        $isRole = '',
        $isTplGroup = '',
        $include_groups = []
    ) {
        $tikilib = TikiLib::lib('tiki');
        $group = trim($group);

        if ($this->group_exists($group)) {
            return false;
        }

        $data = [
            'groupName' => $group,
            'groupDesc' => $desc,
            'groupHome' => $home,
            'groupDefCat' => $defcat,
            'groupTheme' => $theme,
            'groupColor' => $color,
            'usersTrackerId' => (int)$utracker,
            'groupTrackerId' => (int)$gtracker,
            'registrationUsersFieldIds' => $rufields,
            'userChoice' => $userChoice,
            'usersFieldId' => (int)$ufield,
            'groupFieldId' => (int)$gfield,
            'isExternal' => $isexternal,
            'expireAfter' => $expireAfter,
            'emailPattern' => $emailPattern,
            'anniversary' => $anniversary,
            'prorateInterval' => $prorateInterval,
            'isRole' => $isRole,
            'isTplGroup' => empty($isTplGroup) ? 'n' : $isTplGroup,
        ];

        $id = $this->table('users_groups')->insert($data);

        $this->manage_group($group, $include_groups);


        TikiLib::events()->trigger('tiki.group.create', [
            'type' => 'group',
            'object' => $group,
        ]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('grouplist');
        $cachelib->invalidate('groupIdlist');

        return $id;
    }

    public function change_group(
        $olgroup,
        $group,
        $desc,
        $home,
        $utracker = 0,
        $gtracker = 0,
        $ufield = 0,
        $gfield = 0,
        $rufields = '',
        $userChoice = '',
        $defcat = 0,
        $theme = '',
        $isexternal = 'n',
        $expireAfter = 0,
        $emailPattern = '',
        $anniversary = '',
        $prorateInterval = '',
        $color = '',
        $isRole = '',
        $isTplGroup = '',
        $include_groups = []
    ) {
        $isTplGroup = empty($isTplGroup) ? 'n' : $isTplGroup;
        $users = $this->get_group_users($group);
        if (! empty($users) && $isRole == "y") {
            throw new Exception(tr('Role groups can\'t have users.'));
        }

        if ($olgroup == 'Anonymous' || $olgroup == 'Registered') {
            // Changing group name of 'Anonymous' and 'Registered' is not allowed.
            if ($group != $olgroup) {
                return false;
            }
        }

        if (! $this->group_exists($olgroup)) {
            return $this->add_group(
                $group,
                $desc,
                $home,
                $utracker,
                $gtracker,
                $rufields,
                $userChoice,
                $defcat,
                $theme,
                $isexternal,
                $expireAfter,
                $emailPattern,
                $anniversary,
                $prorateInterval,
                $color,
                $isRole,
                $isTplGroup
            );
        }

        $cachelib = TikiLib::lib('cache');

        $tx = TikiDb::get()->begin();
        
        $data = [
            'groupName' => $group,
            'groupDesc' => $desc,
            'groupHome' => $home,
            'groupDefCat' => $defcat,
            'groupTheme' => $theme,
            'groupColor' => $color,
            'usersTrackerId' => (int)$utracker,
            'groupTrackerId' => (int)$gtracker,
            'registrationUsersFieldIds' => $rufields,
            'userChoice' => $userChoice,
            'usersFieldId' => (int)$ufield,
            'groupFieldId' => (int)$gfield,
            'isExternal' => $isexternal,
            'expireAfter' => $expireAfter,
            'emailPattern' => $emailPattern,
            'anniversary' => $anniversary,
            'prorateInterval' => $prorateInterval,
            'isRole' => $isRole,
            'isTplGroup' => $isTplGroup,
        ];

        $this->table('users_groups')->update($data, ['groupName' => $olgroup]);

        if ($olgroup != $group) {
            $query = [];
            $query[] = 'update `users_usergroups` set `groupName`=? where `groupName`=?';
            $query[] = 'update `users_grouppermissions` set `groupName`=? where `groupName`=?';
            $query[] = 'update `users_objectpermissions` set `groupName`=? where `groupName`=?';
            $query[] = 'update `tiki_group_inclusion` set `groupName`=? where `groupName`=?';
            $query[] = 'update `tiki_group_inclusion` set `includeGroup`=? where `includeGroup`=?';
            $query[] = 'update `tiki_newsletter_groups` set `groupName`=? where `groupName`=?';
            $query[] = 'update `tiki_group_watches` set `group`=? where `group`=?';

            foreach ($query as $q) {
                $this->query($q, [$group, $olgroup]);
            }

            // must unserialize before replacing the groups
            $query = 'select `name`, `groups` from `tiki_modules` where `groups` like ?';
            $result = $this->query($query, ['%' . $olgroup . '%']);

            while ($res = $result->fetchRow()) {
                $aux = [];
                $aux['name'] = $res['name'];
                $aux['groups'] = unserialize($res['groups']);
                $aux['groups'] = str_replace($olgroup, $group, $aux['groups']);
                $aux['groups'] = serialize($aux['groups']);
                $query = 'update `tiki_modules` set `groups`=? where `name`=?';
                $this->query($query, [$aux['groups'], $aux['name']]);
            }

            $query = 'select * from `tiki_tracker_fields` where `visibleBy` like ?';
            $result = $this->query($query, ['%"' . $olgroup . '"%']);
            $query = 'update `tiki_tracker_fields` set `visibleBy`=? where `visibleBy`=?';
            while ($res = $result->fetchRow()) {
                $g = unserialize($res['visibleBy']);
                $g = str_replace($olgroup, $group, $g);
                $g = serialize($g);
                $this->query($query, [$g, $res['visibleBy']]);
            }

            $query = 'select * from `tiki_tracker_fields` where `editableBy` like ?';
            $result = $this->query($query, ['%"' . $olgroup . '"%']);

            $query = 'update `tiki_tracker_fields` set `editableBy`=? where `editableBy`=?';
            while ($res = $result->fetchRow()) {
                $g = unserialize($res['editableBy']);
                $g = str_replace($olgroup, $group, $g);
                $g = serialize($g);
                $this->query($query, [$g, $res['editableBy']]);
            }

            $query = 'update `tiki_tracker_item_fields` ttif' .
                                ' left join `tiki_tracker_fields` ttf on (ttf.`fieldId`=ttif.`fieldId`)' .
                                ' set ttif.`value`=? where ttif.`value`=? and ttf.`type`=?';

            $this->query($query, [$group, $olgroup, 'g']);

            $cachelib->invalidate('grouplist');
            $cachelib->invalidate('group_theme_' . $group);

            TikiLib::events()->trigger('tiki.group.delete', [
                'type' => 'group',
                'object' => $olgroup,
            ]);
        }


        $this->manage_group($group, $include_groups);

        $cachelib->invalidate('group_theme_' . $olgroup);

        TikiLib::events()->trigger('tiki.group.update', [
            'type' => 'group',
            'object' => $group,
        ]);

        $tx->commit();

        return true;
    }

    public function edit_group($id, $name, $description)
    {
        // Limited editing only, for users with the tiki_p_edit_grouplimitedinfo perm
        $groupInfo = $this->get_groupId_info($id);
        if (!$groupInfo) {
            return false;
        }
        $includeGroups = $this->get_included_groups($groupInfo["groupName"]);

        $this->change_group($groupInfo["groupName"], $name, $description, $groupInfo["groupHome"], $groupInfo["usersTrackerId"], $groupInfo["groupTrackerId"], $groupInfo["groupTrackerId"], $groupInfo["groupFieldId"], $groupInfo["registrationUsersFieldIds"], $groupInfo["userChoice"], $groupInfo["groupDefCat"], $groupInfo["groupTheme"], $groupInfo["isExternal"], $groupInfo["expireAfter"], $groupInfo["emailPattern"], $groupInfo["anniversary"], $groupInfo["prorateInterval"], $groupInfo["groupColor"], $groupInfo["isRole"], $groupInfo["isTplGroup"], $includeGroups);

        return true;
    }

    public function remove_all_inclusions($group)
    {
        if (! $this->group_exists($group)) {
            return false;
        }

        $query = 'delete from `tiki_group_inclusion` where `groupName` = ?';
        $result = $this->query($query, [$group]);
        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_type_cache('group_inclusion_' . $group);
        $this->groupinclude_cache = [];

        return true;
    }

    public function set_user_fields($u)
    {
        global $prefs;

        $q = [];
        $bindvars = [];

        if (isset($u['email'])) {
            if ($prefs['user_unique_email'] == 'y' && $this->other_user_has_email($u['login'], $u['email'])) {
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('errortype', 'login');
                $smarty->assign('msg', tra('Email cannot be set because this email is already in use by another user.'));
                $smarty->display('error.tpl');
                die;
            }
            $q[] = '`email` = ?';
            $bindvars[] = strip_tags($u['email']);
        }

        if (isset($u['openid_url'])) {
            if (isset($_SESSION['openid_url'])) {
                $q[] = '`openid_url` = ?';
                $bindvars[] = $u['openid_url'];
            }
        }

        if (count($q) > 0) {
            $query = 'update `users_users` set ' . implode(',', $q) . ' where binary `login` = ?';
            $bindvars[] = $u['login'];
            $result = $this->query($query, $bindvars);
        }

        $aUserPrefs = ['realName', 'homePage', 'country'];
        foreach ($aUserPrefs as $pref) {
            if (isset($u[$pref])) {
                $this->set_user_preference($u['login'], $pref, $u[$pref]);
            }
        }

        return $result;
    }

    public function count_users($group)
    {
        static $rv = [];

        if (! isset($rv[$group])) {
            if ($group == '') {
                $query = 'select count(login) from `users_users`';
                $result = $this->getOne($query);
            } else {
                $query = 'select count(userId) from `users_usergroups` where `groupName` = ?';
                $result = $this->getOne($query, [$group]);
            }
            $rv[$group] = $result;
        }

        return $rv[$group];
    }

    public function count_users_consolidated($groups)
    {
        $groupset = implode("','", $groups);
        $query = "select userId from `users_usergroups` where `groupName` in ('" . $groupset . "')";
        $result = $this->fetchAll($query, []);
        $resultcons = array_unique(array_column($result, 'userId'));

        return count($resultcons);
    }

    public function related_users($user, $max = 10, $type = 'wiki')
    {
        if (! isset($user) || empty($user)) {
            return [];
        }

        // This query was written using a double join for PHP. If you're trying to eke
        // additional performance and are running MySQL 4.X, you might want to try a
        // subselect and compare perf numbers.

        if ($type == 'wiki') {
            $query = 'SELECT u1.`login`, COUNT( p1.`pageName` ) AS quantity
				FROM `tiki_history` p1
				INNER JOIN `users_users` u1 ON ( u1.`login` = p1.`user` )
				INNER JOIN `tiki_history` p2 ON ( p1.`pageName` = p2.`pageName` )
				INNER JOIN `users_users` u2 ON ( u2.`login` = p2.`user` )
				WHERE u2.`login` = ? AND u1.`login` <> ?
				GROUP BY p1.`pageName`, u1.`login` 
				ORDER BY quantity DESC
				';
        } else {
            return [];
        }

        $bindvals = [$user, $user];

        return $this->fetchAll($query, $bindvals, $max, 0);
    }

    // Case-sensitivity regression only. used for patching
    public function get_object_case_permissions($objectId, $objectType)
    {
        $query = 'select `groupName`, `permName` from `users_objectpermissions` where `objectId` = ? and `objectType` = ?';

        return $this->fetchAll($query, [md5($objectType . $objectId), $objectType]);
    }

    public function object_has_one_case_permission($objectId, $objectType)
    {
        $query = 'select count(*) from `users_objectpermissions` where `objectId`=? and `objectType`=?';
        $result = $this->getOne($query, [ md5($objectType . $objectId), $objectType]);

        return $result;
    }

    public function remove_object_case_permission($groupName, $objectId, $objectType, $permName)
    {
        $query = 'delete from `users_objectpermissions`' .
                            ' where `groupName` = ? and `objectId` = ? and `objectType` = ? and `permName` = ?';
        $result = $this->query($query, [$groupName, md5($objectType . $objectId), $objectType, $permName]);

        return true;
    }

    public function send_validation_email(
        $name,
        $apass,
        $email,
        $again = '',
        $second = '',
        $chosenGroup = '',
        $mailTemplate = '',
        $pass = ''
    ) {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');

        // mail_machine kept for BC, use $validation_url
        $machine = TikiLib::tikiUrl('tiki-login_validate.php');
        $machine_assignuser = TikiLib::tikiUrl('tiki-assignuser.php');
        $machine_userprefs = TikiLib::tikiUrl('tiki-user_preferences.php');
        $smarty->assign('mail_machine', $machine);
        $smarty->assign('mail_machine_assignuser', $machine_assignuser);
        $smarty->assign('mail_machine_userprefs', $machine_userprefs);
        $smarty->assign('mail_site', $_SERVER['SERVER_NAME']);
        $smarty->assign('mail_user', $name);
        $smarty->assign('mail_apass', $apass);
        $smarty->assign('mail_email', $email);
        $smarty->assign('mail_again', $again);
        $smarty->assign(
            'validation_url',
            TikiLib::tikiUrl(
                'tiki-login_validate.php',
                [
                    'user' => $name,
                    'pass' => $apass,
                ]
            )
        );
        $smarty->assign(
            'assignuser_url',
            TikiLib::tikiUrl(
                'tiki-assignuser.php',
                ['assign_user' => $name]
            )
        );
        $smarty->assign(
            'userpref_url',
            TikiLib::tikiUrl(
                'tiki-user_preferences.php',
                ['view_user' => $name]
            )
        );

        include_once('lib/webmail/tikimaillib.php');

        if ($second == 'y') {
            $mail_data = $smarty->fetch('mail/confirm_user_email_after_approval.tpl');
            $mail = new TikiMail();
            $mail->setText($mail_data);
            $mail_data = sprintf($smarty->fetch('mail/confirm_user_email_after_approval_subject.tpl'), $_SERVER['SERVER_NAME']);
            $mail->setSubject($mail_data);
            if (! $mail->send([$email])) {
                $smarty->assign('msg', tra("The registration mail can't be sent. Contact the administrator"));

                return false;
            }
        } elseif ($prefs['validateRegistration'] == 'y' && empty($pass) && $mailTemplate != 'user_creation_validation_mail') {
            if (! empty($chosenGroup)) {
                $smarty->assign_by_ref('chosenGroup', $chosenGroup);
                if ($prefs['userTracker'] == 'y') {
                    $trklib = TikiLib::lib('trk');
                    $re = $this->get_group_info(isset($chosenGroup) ? $chosenGroup : 'Registered');
                    $fields = $trklib->list_tracker_fields(
                        $re['usersTrackerId'],
                        0,
                        -1,
                        'position_asc',
                        '',
                        true,
                        ['fieldId' => explode(':', $re['registrationUsersFieldIds'])]
                    );

                    $listfields = [];

                    foreach ($fields['data'] as $field) {
                        $listfields[$field['fieldId']] = $field;
                    }

                    $definition = Tracker_Definition::get($re['usersTrackerId']);
                    if ($definition) {
                        $items = $trklib->list_items(
                            $re['usersTrackerId'],
                            0,
                            1,
                            '',
                            $listfields,
                            $definition->getUserField(),
                            '',
                            '',
                            '',
                            $name,
                            '',
                            null,
                            true,
                            true
                        );

                        if (isset($items['data'][0])) {
                            $smarty->assign_by_ref('item', $items['data'][0]);
                        }
                    } else {
                        Feedback::error(tr('No user tracker found with id #%0', $re['usersTrackerId']));
                    }
                }
            }
            $mail_data = $smarty->fetch('mail/moderate_validation_mail.tpl');
            $mail_subject = $smarty->fetch('mail/moderate_validation_mail_subject.tpl');

            $emails = ! empty($prefs['validator_emails'])
                                ? preg_split('/,/', $prefs['validator_emails'])
                                : (! empty($prefs['sender_email']) ? [$prefs['sender_email']] : '');

            if (empty($emails)) {
                if ($prefs['feature_messages'] != 'y') {
                    $smarty->assign(
                        'msg',
                        tra("The registration mail can't be sent because there is no server email address set, and this feature is disabled") .
                        ": feature_messages"
                    );

                    return false;
                }

                TikiLib::lib('message')->post_message(
                    $prefs['contact_user'],
                    $prefs['contact_user'],
                    $prefs['contact_user'],
                    '',
                    $mail_subject,
                    $mail_data,
                    5
                );
                $smarty->assign('msg', $smarty->fetch('mail/user_validation_waiting_msg.tpl'));
            } else {
                $mail = new TikiMail();
                $mail->setText($mail_data);
                $mail->setSubject($mail_subject);
                if (! $mail->send($emails)) {
                    $smarty->assign('msg', tra("The registration mail can't be sent. Contact the administrator"));

                    return false;
                } elseif (empty($again)) {
                    $smarty->assign('msg', $smarty->fetch('mail/user_validation_waiting_msg.tpl'));
                } else {
                    $smarty->assign('msg', tra('The administrator has not yet validated your account. Please wait.'));
                }
            }
        } elseif ($prefs['validateUsers'] == 'y' || ! empty($pass) || $mailTemplate == 'user_creation_validation_mail') {
            if ($mailTemplate == '') {
                $mailTemplate = 'user_validation_mail';
            }

            $smarty->assign(
                'validation_url',
                TikiLib::tikiUrl(
                    'tiki-login_validate.php',
                    [
                        'user' => $name,
                        'pass' => $apass,
                    ]
                )
            );

            $mail_data = $smarty->fetch("mail/$mailTemplate.tpl");
            $mail = new TikiMail();
            $mail->setText($mail_data);
            $mail_data = $smarty->fetch("mail/{$mailTemplate}_subject.tpl");
            $mail->setSubject($mail_data);
            if (! $mail->send([$email])) {
                $smarty->assign('msg', tra("The registration mail can't be sent. Contact the administrator"));

                return false;
            } elseif (empty($again)) {
                $smarty->assign('msg', $smarty->fetch('mail/user_validation_msg.tpl'));
            } else {
                $smarty->assign('msg', tra('You must validate your account first. An email has been sent to you'));
            }
        }

        return true;
    }

    public function set_registrationChoice($groups, $flag)
    {
        $bindvars = [];
        $bindvars[] = $flag;
        if (is_array($groups)) {
            $mid = implode(',', array_fill(0, count($groups), '?'));
            $bindvars = array_merge($bindvars, $groups);
        } else {
            $bindvars[] = $groups;
            $mid = 'like ?';
        }
        $query = "update `users_groups` set `registrationChoice`= ? where `groupName` in ($mid)";
        $result = $this->query($query, $bindvars);
    }

    public function get_registrationChoice($group)
    {
        $query = 'select `registrationChoice` from `users_groups` where `groupName` = ?';

        return ($this->getOne($query, [$group]));
    }

    public function reset_email_due($user)
    {
        $query = 'update `users_users` set `email_confirm`=?, `waiting`=? where `login`=?';
        $result = $this->query($query, [0, 'u', $user]);
        TikiLib::events()->trigger('tiki.user.update', ['type' => 'user', 'object' => $user]);

        return $result;
    }

    public function confirm_email($user, $pass)
    {
        $tikilib = TikiLib::lib('tiki');
        $query = 'select `provpass`, `login`, `unsuccessful_logins` from `users_users` where `login`=?';
        $result = $this->query($query, [$user]);
        if (! ($res = $result->fetchRow())) {
            return false;
        }

        if (md5($res['provpass']) == $pass) {
            $this->confirm_user($user);

            $query = 'update `users_users`' .
                            ' set `provpass`=?, `email_confirm`=?, `unsuccessful_logins`=?, `registrationDate`=?' .
                            ' where `login`=? and `provpass`=?';

            $this->query($query, ['', $tikilib->now, 0, $this->now, $user, $res['provpass']]);
            if (! empty($GLOBALS['user'])) {
                $logslib = TikiLib::lib('logs');
                $logslib->add_log('login', 'confirm email ' . $user);
            }
            TikiLib::lib('user')->set_unsuccessful_logins($_REQUEST['user'], 0);

            return true;
        }

        return false;
    }

    public function set_unsuccessful_logins($user, $nb)
    {
        $query = 'update `users_users` set `unsuccessful_logins`=? where `login` = ?';
        $this->query($query, [$nb, $user]);
    }

    public function send_confirm_email($user, $tpl = 'confirm_user_email')
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');

        include_once('lib/webmail/tikimaillib.php');
        $languageEmail = $this->get_user_preference($_REQUEST['username'], 'language', $prefs['site_language']);
        $apass = $this->renew_user_password($user);
        $apass = md5($apass);
        $smarty->assign('mail_apass', $apass);
        $smarty->assign('mail_ip', $tikilib->get_ip_address());
        $smarty->assign('user', $user);
        $mail = new TikiMail();
        $mail_data = $smarty->fetchLang($languageEmail, "mail/$tpl" . '_subject.tpl');
        $mail_data = sprintf($mail_data, $_SERVER['SERVER_NAME']);
        $mail->setSubject($mail_data);
        $foo = parse_url($_SERVER['REQUEST_URI']);
        $mail_machine = TikiLib::tikiUrl('tiki-confirm_user_email.php'); // for BC
        $smarty->assign('mail_machine', $mail_machine);
        $mail_data = $smarty->fetchLang($languageEmail, "mail/$tpl.tpl");
        $mail->setText($mail_data);

        if (! ($email = $this->get_user_email($user)) || ! $mail->send([$email])) {
            $smarty->assign('msg', tra("The user email confirmation can't be sent. Contact the administrator"));

            return false;
        }
        $smarty->assign('msg', 'It is time to confirm your email. You will receive an mail with the instruction to follow');

        return true;
    }

    public function assign_openid($username, $openid)
    {
        // This won't update the database unless the openid is different
        $this->query(
            "UPDATE `users_users` SET openid_url = ? WHERE login = ? AND ( openid_url <> ? OR openid_url IS NULL )",
            [$openid, $username, $openid]
        );
    }

    public function intervalidate($remote, $user, $pass, $get_info = false)
    {
        global $prefs;
        $hashkey = $this->get_cookie_check() . '.' . ($this->now + $prefs['remembertime']);
        $remote['path'] = preg_replace('/^\/?/', '/', $remote['path']);
        $client = new XML_RPC_Client($remote['path'], $remote['host'], $remote['port']);
        $client->setDebug(0);

        $msg = new XML_RPC_Message(
            'intertiki.validate',
            [
                new XML_RPC_Value($prefs['tiki_key'], 'string'),
                new XML_RPC_Value($user, 'string'),
                new XML_RPC_Value($pass, 'string'),
                new XML_RPC_Value($get_info, 'boolean'),
                new XML_RPC_Value($hashkey, 'string')
            ]
        );
        $result = $client->send($msg);

        return $result;
    }

    /* send request + interpret email/login */
    public function interGetUserInfo($remote, $user, $email)
    {
        global $prefs;
        $remote['path'] = preg_replace('/^\/?/', '/', $remote['path']);
        $client = new XML_RPC_Client($remote['path'], $remote['host'], $remote['port']);
        $client->setDebug(0);
        $params = [];
        $params[] = new XML_RPC_Value($prefs['tiki_key'], 'string');
        $params[] = new XML_RPC_Value($user, 'string');
        $params[] = new XML_RPC_Value($email, 'string');
        $msg = new XML_RPC_Message('intertiki.getUserInfo', $params);
        $rpcauth = $client->send($msg);

        if (! $rpcauth || $rpcauth->faultCode()) {
            return false;
        }

        $response_value = $rpcauth->value();

        for (;;) {
            list($key, $value) = $response_value->structeach();
            if ($key == '') {
                break;
            } elseif ($key == 'login') {
                $u['login'] = $value->scalarval();
            } elseif ($key == 'email') {
                $u['email'] = $value->scalarval();
            }
        }

        return $u;
    }

    /* send via XML_RPC user info to the main */
    public function interSendUserInfo($remote, $user)
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $remote['path'] = preg_replace('/^\/?/', '/', $remote['path']);
        $client = new XML_RPC_Client($remote['path'], $remote['host'], $remote['port']);
        $client->setDebug(0);
        $params = [];
        $params[] = new XML_RPC_Value($prefs['tiki_key'], 'string');
        $params[] = new XML_RPC_Value($user, 'string');
        $user_details = $userlib->get_user_details($user);
        $user_info = $userlib->get_user_info($user);
        $ret['avatarData'] = new XML_RPC_Value($user_info['avatarData'], 'base64');
        $ret['user_details'] = new XML_RPC_Value(serialize($user_details), 'string');
        $params[] = new XML_RPC_Value($ret, 'struct');
        $msg = new XML_RPC_Message('intertiki.setUserInfo', $params);
        $result = $client->send($msg);

        return $result;
    }

    /* interpret the XML_RPC answer about user info */
    public function interSetUserInfo($user, $response_value)
    {
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');

        if ($response_value->kindOf() == 'struct') {
            for (;;) {
                list($key, $value) = $response_value->structeach();
                if ($key == '') {
                    break;
                } elseif ($key == 'user_details') {
                    $user_details = unserialize($value->scalarval());
                } elseif ($key == 'avatarData') {
                    $avatarData = $value->scalarval();
                }
            }
        } else {
            $user_details = unserialize($response_value->scalarval());
        }

        $userlib->set_user_fields($user_details['info']);
        $tikilib->set_user_preferences($user, $user_details['preferences']);

        if (! empty($avatarData)) {
            $userprefslib = TikiLib::lib('userprefs');
            $userprefslib->set_user_avatar(
                $user,
                'u',
                '',
                $user_details['info']['avatarName'],
                $user_details['info']['avatarSize'],
                $user_details['info']['avatarFileType'],
                $avatarData,
                false
            );
        }
    }

    public function get_remote_user_by_cookie($hash)
    {
        global $prefs;

        $remote = $prefs['interlist'][$prefs['feature_intertiki_mymaster']];
        $client = new XML_RPC_Client($remote['path'], $remote['host'], $remote['port']);
        $client->setDebug(0);

        $msg = new XML_RPC_Message(
            'intertiki.cookiecheck',
            [
                new XML_RPC_Value($prefs['tiki_key'], 'string'),
                new XML_RPC_Value($hash, 'string')
            ]
        );
        $er = error_reporting(); // suppress PHP 7.2 warnings from xmlrpc lib
        error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
        $result = $client->send($msg);
        error_reporting($er);

        return $result;
    }

    public function update_expired_groups()
    {
        $tikilib = TikiLib::lib('tiki');
        $this->update_anniversary_expiry();
        $query = 'SELECT uu.* FROM `users_usergroups` uu' .
                        ' LEFT JOIN `users_groups` ug ON (uu.`groupName`= ug.`groupName`)' .
                        ' WHERE ( ug.`expireAfter` > ? AND uu.`created` IS NOT NULL AND uu.`expire` is NULL AND uu.`created` + ug.`expireAfter`*24*60*60 < ?)' .
                        ' OR ((ug.`expireAfter` IS NOT NULL OR ug.`anniversary` > ?) AND uu.`expire` < ?)';

        $result = $this->query($query, [0, $tikilib->now, 0, $tikilib->now]);

        while ($res = $result->fetchRow()) {
            $this->remove_user_from_group($this->get_user_login($res['userId']), $res['groupName']);
        }
    }

    public function update_anniversary_expiry()
    {
        $query = 'SELECT uu.* FROM `users_usergroups` uu' .
                        ' LEFT JOIN `users_groups` ug ON (uu.`groupName`= ug.`groupName`)' .
                        ' WHERE ( ug.`anniversary` > ? AND uu.`created` IS NOT NULL AND uu.`expire` is NULL )';

        $result = $this->query($query, ['']);

        $query = 'UPDATE `users_usergroups` SET `expire` = ? WHERE `groupName`=? AND `userId`=?';

        while ($res = $result->fetchRow()) {
            $extend_until_info = $this->get_extend_until_info($res['login'], $res['groupName']);
            $this->query($query, [$extend_until_info['timestamp'], $res['groupName'], $res['userId']]);
        }
    }

    public function update_group_expiries()
    {
        $query = 'SELECT uu.* FROM `users_usergroups` uu' .
            ' LEFT JOIN `users_groups` ug ON (uu.`groupName`= ug.`groupName`)' .
            ' WHERE ( uu.`created` IS NOT NULL AND uu.`expire` is NULL )' .
            ' AND (ug.`anniversary` > ? OR ug.`expireAfter` > ?)';

        $result = $this->query($query, ['', 0]);

        $query = 'UPDATE `users_usergroups` SET `expire` = ? WHERE `groupName`=? AND `userId`=?';

        while ($res = $result->fetchRow()) {
            $uinfo = $this->get_userid_info($res['userId']);
            $extend_until_info = $this->get_extend_until_info($uinfo['login'], $res['groupName']);
            $this->query($query, [$extend_until_info['timestamp'], $res['groupName'], $res['userId']]);
        }
    }


    public function extend_membership($user, $group, $periods = 1, $date = null)
    {
        $tikilib = TikiLib::lib('tiki');
        $this->update_expired_groups();

        if (! $this->user_is_in_group($user, $group)) {
            $this->assign_user_to_group($user, $group);
            if ($periods > 1) {
                $periods--;
            } elseif (empty($date)) {
                return;
            }
        }

        $info = $this->get_group_info($group);
        $userInfo = $this->get_user_info($user);
        if (empty($date)) {
            $extend_until_info = $this->get_extend_until_info($user, $group, $periods);
        } else {
            $extend_until_info['timestamp'] = $date;
        }

        return $this->query(
            'UPDATE `users_usergroups` SET `expire` = ? WHERE `userId` = ? AND `groupName` = ?',
            [$extend_until_info['timestamp'], $userInfo['userId'], $group]
        );
    }

    public function get_extend_until_info($user, $group, $periods = 1)
    {
        //use these functions to get current expiry dates for existing members - they are calculated in some cases
        //so just grabbing the "expire" field from the users_usergroups table doesn't always work
        $userInfo = $this->get_user_info($user);
        $usergroupdates = $this->get_user_groups_date($userInfo['userId']);

        $info = $this->get_group_info($group);
        //set the start date as now for new memberships and as expiry of current membership for existing members
        if (array_key_exists($group, $usergroupdates)) {
            if (! empty($usergroupdates[$group]['expire'])) {
                $date = $usergroupdates[$group]['expire'];
            } elseif ($info['expireAfter'] > 0) {
                $date = $usergroupdates[$group]['created'];
            }
        }
        if (! isset($date) || ! $date) {
            $date = $this->now;
            //this is a new membership
            $new = true;
        } else {
            $new = false;
        }
        //convert start date to object
        $rawstartutc = new DateTimeImmutable('@' . $date);
        global $prefs;
        $tz = TikiDate::TimezoneIsValidId($prefs['server_timezone']) ? $prefs['server_timezone'] : 'UTC';
        $timezone = new DateTimeZone($tz);
        $startlocal = $rawstartutc->setTimezone($timezone);

        //anniversary memberships
        if (! empty($info['anniversary'])) {
            //set time to 1 second after midnight so that all times are set to same times for interval calculations
            $startlocal = $startlocal->setTime(0, 0, 1);
            // annual anniversaries
            if (strlen($info['anniversary']) == 4) {
                $ann_month = substr($info['anniversary'], 0, 2);
                $ann_day = substr($info['anniversary'], 2, 2);
                $startyear = $startlocal->format('Y');
                //increment the year if past the annual anniversary
                if ($startlocal->format('m') > $ann_month || ($startlocal->format('m') == $ann_month
                        && $startlocal->format('d') >= $ann_day)) {
                    $startyear++;
                }
                //first extension is always to next anniversary
                $next_ann = $startlocal->setDate($startyear, $ann_month, $ann_day);
                //extend past next anniversary if more than one period
                $extendto = $next_ann->modify('+' . $periods - 1 . ' years');
                //previous anniversary for proration
                $prev_ann = $next_ann->modify('-1 years');
            // monthly anniversaries
                //using modify('+1 month') can result in "skipping" months so fix the day of the previous/next month
            } elseif (strlen($info['anniversary']) == 2) {
                $ann_day = $info['anniversary'];
                $lastday = date('d', strtotime('last day of ' . $startlocal->format('Y') . '-'
                    . $startlocal->format('m')));
                $mod_ann_day = $ann_day > $lastday ? $lastday : $ann_day;
                if ($startlocal->format('d') < $mod_ann_day) {
                    $mod = $mod_ann_day - $startlocal->format('d');
                    $next_ann = $startlocal->modify('+' . $mod . ' days');
                    $prev_mo_lastday = $startlocal->modify('last day of last month');
                    if ($ann_day >= $prev_mo_lastday->format('d')) {
                        $prev_ann = $prev_mo_lastday;
                    } else {
                        $prev_ann = $startlocal->setDate(
                            $prev_mo_lastday->format('Y'),
                            $prev_mo_lastday->format('m'),
                            $mod_ann_day
                        );
                    }
                } else {
                    //check if last day of month
                    $next_mo_lastday = $startlocal->modify('last day of next month');
                    if ($mod_ann_day >= $next_mo_lastday->format('d')) {
                        $next_ann = $next_mo_lastday;
                    } else {
                        $next_ann = $startlocal->setDate(
                            $next_mo_lastday->format('Y'),
                            $next_mo_lastday->format('m'),
                            $mod_ann_day
                        );
                    }
                    $mod = $startlocal->format('d') - $mod_ann_day;
                    $prev_ann = $startlocal->modify('-' . $mod . ' days');
                }
                if ($periods - 1 > 0) {
                    $yrsplus = floor(($periods - 1) / 12);
                    $yr = $next_ann->format('Y') + $yrsplus;
                    $moplus = ($periods - 1) - ($yrsplus * 12);
                    if ($moplus + $next_ann->format('m') < 12) {
                        $mo = $moplus + $next_ann->format('m');
                    } else {
                        $yr++;
                        $mo = $moplus + $next_ann->format('m') - 12;
                    }
                    if ($ann_day >= date('d', strtotime('last day of ' . $yr . '-' . $mo))) {
                        $d = date('d', strtotime('last day of ' . $yr . '-' . $mo));
                    } else {
                        $d = $ann_day;
                    }
                    $extendto = $next_ann->setDate($yr, $mo, $d);
                } else {
                    $extendto = $next_ann;
                }
            }
            //calculate interval of membership term
            $interval = $startlocal->diff($extendto);
            //set prorate interval
            $prorateInterval = in_array($info['prorateInterval'], ['year', 'month', 'day']) ? $info['prorateInterval']
                : 'day';
            //prorate
            if ($prorateInterval == 'year' && strlen($info['anniversary']) == 4) {
                $ratio = $interval->y;
                $ratio += $interval->m > 0 || $interval->d > 0 ? 1 : 0;
            } elseif ($prorateInterval == 'month'
                || ($prorateInterval == 'year' && strlen($info['anniversary']) == 2)) {
                $round = $interval->d > 0 ? 1 : 0;
                $ratio = (($interval->y * 12) + $interval->m + $round);
                if (strlen($info['anniversary']) == 4) {
                    $ratio = $ratio / 12;
                }
            } elseif ($prorateInterval == 'day') {
                $ann_interval = $prev_ann->diff($next_ann);
                $stub_interval = $startlocal->diff($next_ann);
                $ratio = ($stub_interval->days / $ann_interval->days) + ($periods - 1);
            }
            $remainder = $ratio > 1 ? $ratio - floor($ratio) : $ratio;
        //memberships based on number of days
        } else {
            $remainder = 1;
            $ratio = 1;
            $extendto = $startlocal->modify('+' . $info['expireAfter'] * $periods . ' days');
            $interval = $startlocal->diff($extendto);
        }
        $timestamp = $extendto != null ? $extendto->format('U') : null;

        return [
            'timestamp' => $timestamp,
            'ratio_prorated_first_period' => $remainder,
            'ratio' => $ratio,
            'interval' => $interval,
            'new' => $new];
    }

    public function get_users_created_group($group, $user = null, $with_expire = false)
    {
        if (! empty($user)) {
            $query = 'SELECT uug.`created`,uug.`expire` FROM `users_usergroups` uug' .
                                ' LEFT JOIN `users_users` on (`users_users`.`userId`=uug.`userId`)' .
                                ' WHERE `groupName`=? AND `login`=?';

            $bindvars = [$group, $user];
        } else {
            $query = 'SELECT `login`, uug.`created`,uug.`expire` FROM `users_usergroups` uug' .
                                ' LEFT JOIN `users_users` on (`users_users`.`userId`=uug.`userId`)' .
                                ' WHERE `groupName`=?';

            $bindvars = [$group];
        }
        $result = $this->query($query, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            if ($with_expire) {
                $ret[$res['login']]['created'] = $res['created'];
                if (empty($res['expire'])) {
                    $re = $this->get_group_info($group);
                }
                if ($re['expireAfter'] > 0) {
                    $res['expire'] = $res['created'] + ($re['expireAfter'] * 24 * 60 * 60);
                }
                $ret[$res['login']]['expire'] = $res['expire'];
            } else {
                $ret[$res['login']] = $res['created'];
            }
        }

        return $ret;
    }

    public function nb_users_in_group($group = null)
    {
        if (! empty($group)) {
            $query = 'SELECT count(*) FROM `users_usergroups` WHERE `groupName`=?';

            return $this->getOne($query, [$group]);
        }
        $query = 'SELECT count(*) FROM `users_users`';

        return $this->getOne($query, []);
    }

    public function find_best_user($usrs, $group = '', $key = 'login')
    {
        $finalusers = [];
        foreach ($usrs as $u) {
            $u = trim($u);
            if (! $u) {
                continue;
            }
            if ($u == 'admin') {
                $finalusers[] = $u;
            } elseif ($key == 'userId' && preg_match('/\(([0-9]+)\)$/', $u, $matches)) {
                $finalusers[] = $this->get_user_login($matches[1]);
            } elseif ($key == 'login' && preg_match('/\((.+)\)$/', $u, $matches)) {
                $finalusers[] = $matches[1];
            } else {
                $possibleusers = $this->get_users_light(0, -1, 'login_asc', '', $group);
                $unames = array_keys($possibleusers, $u);
                if (count($unames) == 1 && $unames[0]) {
                    $finalusers[] = $unames[0];
                }
            }
        }

        return $finalusers;
    }

    public function clean_user($u, $force_check_realnames = false, $login_fallback = true)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        if ($prefs['user_show_realnames'] == 'y' || $force_check_realnames) {
            // need to trim to prevent mustMatch failure
            $realname = trim($tikilib->get_user_preference($u, 'realName', ''));
        }
        if (! empty($realname)) {
            $u = $realname;
        } elseif ($prefs['login_is_email_obscure'] == 'y' && $atsign = strpos($u, '@')) {
            $u = substr($u, 0, $atsign);
            if (! $login_fallback) {
                $u = tra('Anonymous');
            }
        }

        return $u;
    }

    private function categorize_user_tracker_item($user, $group)
    {
        $tikilib = TikiLib::lib('tiki');
        $userid = $this->get_user_id($user);
        $tracker = $this->get_usertracker($userid);
        if ($tracker && $tracker['usersTrackerId']) {
            $trklib = TikiLib::lib('trk');
            $categlib = TikiLib::lib('categ');
            $itemid = $trklib->get_item_id($tracker['usersTrackerId'], $tracker['usersFieldId'], $user);
            $cat = $categlib->get_object_categories('trackeritem', $itemid);
            $categId = $categlib->get_category_id($group);
            if (! $categId) {
                return false;
            }
            $cat[] = $categId;
            $cat = array_unique($cat);

            // using override_perms=true because if user adding himself to group may not have perms yet
            $trklib->categorized_item($tracker["usersTrackerId"], $itemid, '', $cat, [], true);
            require_once('lib/search/refresh-functions.php');
            refresh_index('trackeritem', $itemid);
        }
    }

    private function uncategorize_user_tracker_item($user, $group)
    {
        $tikilib = TikiLib::lib('tiki');
        $userid = $this->get_user_id($user);
        $tracker = $this->get_usertracker($userid);

        if ($tracker && $tracker['usersTrackerId']) {
            $trklib = TikiLib::lib('trk');
            $categlib = TikiLib::lib('categ');
            $itemid = $trklib->get_item_id($tracker['usersTrackerId'], $tracker['usersFieldId'], $user);
            $cat = $categlib->get_object_categories('trackeritem', $itemid);
            $categId = $categlib->get_category_id($group);
            if (! $categId) {
                return false;
            }
            $cat = array_diff($cat, [$categId]);
            $trklib->categorized_item($tracker["usersTrackerId"], $itemid, '', $cat, [], true);
            require_once('lib/search/refresh-functions.php');
            refresh_index('trackeritem', $itemid);
        }
    }

    /**
     * Remove the link between a Tiki user account
     * and an OpenID account
     *
     * @param int $userId
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_openid_link($userId)
    {
        $query = "UPDATE `users_users` SET `openid_url` = NULL WHERE `userId` = ?";
        $bindvars = [$userId];

        return $this->query($query, $bindvars);
    }

    public function get_lost_groups()
    {
        $query = 'SELECT ugp.`groupName` FROM `users_grouppermissions` ugp' .
                            ' LEFT JOIN `users_groups` ug ON ( ug.`groupName` = ugp.`groupName` )' .
                            ' WHERE ug.`groupName` IS NULL';

        $groups = $this->fetchAll($query);
        $ret = [];

        foreach ($groups as $res) {
            if (! in_array($res['groupName'], $ret)) {
                $ret[] = $res['groupName'];
            }
        }

        $query = 'SELECT ugp.`groupName` FROM `users_objectpermissions` ugp' .
                            ' LEFT JOIN `users_groups` ug ON ( ug.`groupName` = ugp.`groupName` )' .
                            ' WHERE ug.`groupName` IS NULL';

        $groups = $this->fetchAll($query);

        foreach ($groups as $res) {
            if (! in_array($res['groupName'], $ret)) {
                $ret[] = $res['groupName'];
            }
        }

        return $ret;
    }

    public function remove_lost_groups()
    {
        $groups = $this->get_lost_groups();
        if (empty($groups)) {
            return;
        }
        $query = 'delete FROM `users_grouppermissions` where `groupName` in (' . implode(',', array_fill(0, count($groups), '?')) . ')';
        $this->query($query, $groups);
        $query = 'delete FROM `users_objectpermissions` where `groupName` in (' . implode(',', array_fill(0, count($groups), '?')) . ')';

        $this->query($query, $groups);
    }

    public function get_user_groups_date($userId)
    {
        $query = 'select * from `users_usergroups` where `userId`=?';
        $result = $this->query($query, [$userId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $g = $res['groupName'];
            $ret[$g]['created'] = $res['created'];
            $ret[$g]['expire'] = $res['expire'];
        }

        return $ret;
    }

    /**
     * This is a function to automatically login a user programatically
     * @param string $uname The user account name to log the user in as
     * @return bool true means that successfully logged in or already logged in. false means no such user.
     */
    public function autologin_user($uname)
    {
        global $user;
        if ($user) {
            // already logged in
            return true;
        }
        if (! $this->user_exists($uname)) {
            // no such user
            return false;
        }
        // Conduct login
        global $user_cookie_site;
        $_SESSION[$user_cookie_site] = $uname;
        $this->update_expired_groups();
        $this->update_lastlogin($uname);

        return true;
    }

    /**
     * This is a function to invite users to temporarily access the site via a token
     * @param array $emails Emails to send the invite to
     * @param array $groups Groups that the temporary user should have (Registered is not included unless explicitly added)
     * @param int $timeout How long the invitation is valid for, in seconds.
     * @param string $prefix Username of the created users will be the token ID prefixed with this
     * @param string $path Users will have to autologin using this path on the site using the token
     * @throws Exception
     */
    public function invite_tempuser($emails, $groups, $timeout, $prefix = 'guest', $path = 'index.php')
    {
        global $user, $prefs;
        $smarty = TikiLib::lib('smarty');
        include_once('lib/webmail/tikimaillib.php');
        $referer = Services_Utilities::noJsPath();

        $mail = new TikiMail();
        foreach ($emails as $email) {
            if (! validate_email($email)) {
                $mes = empty($email) ? tr('Email address is required.') : tr('Invalid email address "%0"', $email);
                Feedback::error($mes);
                Services_Utilities::sendFeedback($referer);
            }
        }
        $foo = parse_url($_SERVER['REQUEST_URI']);
        $machine = $this->httpPrefix(true) . dirname($foo['path']);
        $machine = preg_replace('!/$!', '', $machine); // just in case
        $smarty->assign_by_ref('mail_machine', $machine);
        $smarty->assign('mail_sender', $user);
        $smarty->assign('expiry', $user);
        $mail->setBcc($this->get_user_email($user));
        $smarty->assign('token_expiry', $this->get_long_datetime($this->now + $timeout));
        require_once 'lib/auth/tokens.php';

        foreach ($emails as $email) {
            $tokenlib = AuthTokens::build($prefs);
            $token_url = $tokenlib->includeToken($machine . "/$path", $groups, $email, $timeout, -1, true, $prefix);
            include_once('tiki-sefurl.php');
            $token_url = filter_out_sefurl($token_url);
            $smarty->assign('token_url', $token_url);
            $mail->setUser($user);
            $mail->setSubject($smarty->fetch('mail/invite_tempuser_subject.tpl'));
            $mail->setHtml($smarty->fetch('mail/invite_tempuser.tpl'));

            if (! $mail->send($email)) {
                $errormsg = tr('Unable to send mail to invite "%0"', $email);
                if (Perms::get()->admin) {
                    $mailerrors = print_r($mail->errors, true);
                    $errormsg .= $mailerrors;
                }
                Feedback::error($errormsg);
                Services_Utilities::sendFeedback($referer);
            }
            $smarty->assign_by_ref('user', $user);
        }
    }

    /**
     * @param string $uname The username of the temporary user to remove (or disable depending on the pref)
     *
     */
    public function remove_temporary_user($uname)
    {
        global $prefs;
        if ($prefs['auth_token_preserve_tempusers'] == 'y') {
            $this->remove_user_from_all_groups($uname);
        } else {
            $this->remove_user($uname);
        }
    }
}



/* For the emacs weenies in the crowd.
Local Variables:
   c-basic-offset: 4
End:
*/
