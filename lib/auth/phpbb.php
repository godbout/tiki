<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Tikiwiki authentication backend for phpBB3 with adodb
// By Jacob 'jacmoe2' Moen 10 Dec 2009
// Based on:
// Mediawiki authentication plugin for phpBB3 with mysql4
// By Steve Streeting 26 Dec 2008

require_once('lib/auth/PasswordHash.php');

// some definitions for helping with authentication
// Er, what about definition clashes ?
define("PHPBB_INVALID_CREDENTIALS", -21);
define("PHPBB_INVALID_SYNTAX", -23);
define("PHPBB_NO_SUCH_USER", -25);
define("PHPBB_SUCCESS", -29);
define("SERVER_ERROR", -1);


//TODO: support other database types

class TikiPhpBBLib
{
    public $db;

    public function check($user, $pass)
    {

    // no need to progress further if the user doesn't even exist
        if (! $this->userExists($user)) {
            return PHPBB_NO_SUCH_USER;
        }

        // if the user does exist, authenticate
        if ($this->authenticate($user, $pass)) {
            return PHPBB_SUCCESS;
        }

        return PHPBB_INVALID_CREDENTIALS;
    }

    public function connectdb()
    {
        global $prefs;
        $dbhost = $prefs['auth_phpbb_dbhost'];
        $dbuser = $prefs['auth_phpbb_dbuser'];
        $dbpasswd = $prefs['auth_phpbb_dbpasswd'];
        $dbname = $prefs['auth_phpbb_dbname'];
        $dbtype = 'mysql';//$prefs['auth_phpbb_dbtype'];

        // Force autoloading
        if (! class_exists('ADOConnection')) {
            return false;
        }


        $dbconnection = NewADOConnection($dbtype);
        $dbconnection->Connect($dbhost, $dbuser, $dbpasswd, $dbname);

        if ($dbconnection) {
            return $dbconnection;
        }

        return false;
    }

    /**
    * Check whether there exists a user account with the given name.
    *
    * @param string $username
    * @return bool
    * @access public
    */
    public function userExists($username)
    {
        global $prefs;

        $dbconnection = $this->connectdb();
        $username = $dbconnection->Quote($username);

        // MySQL queries are case insensitive anyway
        $query = "select username from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = lcase('" . $username . "')";
        /** @var ADORecordSet $result */
        $result = $dbconnection->Execute($query);
        if ($result === false) {
            die('AuthPhpBB : Query failed: ' . $dbconnection->ErrorMsg());
        }

        return $result->RecordCount() > 0;
    }

    /**
    * Check if a username+password pair is a valid login.
    *
    * @param string $username
    * @param string $password
    * @return bool
    * @access public
    */
    public function authenticate($username, $password)
    {
        global $prefs;

        $dbconnection = $this->connectdb();
        $username = $dbconnection->Quote($username);

        $query = "select user_password from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = lcase('" . $username . "')";
        $result = $dbconnection->Execute($query);
        if ($result === false) {
            die('AuthPhpBB : Query failed: ' . $dbconnection->ErrorMsg());
        }

        if ($result->RecordCount() == 0) {
            return false;
        }
        // TODO: check for phpBB version here, and select a different hasher, if needed.
        // This one is hardcoded for phpbb3
        $PasswordHasher = new PasswordHash(8, true);

        if ($PasswordHasher->CheckPassword($password, $result->fields[0])) {
            return true;
        }

        return false;
    }

    /**
    * Returns a users email from the phpbb3 user table.
    * @param Username $username
    * @access public
    * @return email or 0
    */
    public function grabEmail(&$username)
    {
        global $prefs;
        $dbconnection = $this->connectdb();
        $username = $dbconnection->Quote($username);

        // Just add email
        $query = "select user_email from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = lcase('" . $username . "')";
        $result = $dbconnection->Execute($query);
        if ($result === false) {
            die('AuthPhpBB : Query failed: ' . $dbconnection->ErrorMsg());
        }

        if ($result->RecordCount() > 0) {
            return $result->field[0];
        }

        return 0;
    }
}
