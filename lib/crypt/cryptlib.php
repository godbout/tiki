<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/*
 * CryptLib (aims to) safely store encrypted data, e.g. passwords for external systems, in Tiki.
 * The encrypted data can only be decrypted by the owner/user.
 *
 * CryptLib will use openssl if the PHP extension is available.
 * Otherwise it reverts to (near-plaintext) Base64 encoding.
 *
 * In order to use openssl
 * 1. The openssl PHP extension must be available
 * 2. Call the init method before using cryptlib
 *
 * Before Tiki 18, CryptLib used the mcrypt library, which is being deprecated in PHP 7.1 and removed from the standard installation starting with PHP 7.2.
 * See http://php.net/manual/en/migration71.deprecated.php for details.
 * In order to convert existing, encrypted data, the mcrypt must be used.
 * Thus CryptLib still may attempt to use mcrypt if such data is found.
 *
 * The method setUserData encrypts the value and stores a user preference
 * getUserData reads it back into cleartext
 *
 * The secret key phrase is the MD5 sum of the username + Tiki password.
 * The secret key is thus 1) personal 2) not stored anywhere in Tiki.
 *
 * Each encryption uses its own initialization vector (seed).
 * Rehashing the same value should thus yield a different result every time.
 *
 * When a user logs in, Tiki calls onUserLogin, which registers the current secret key in a session variable.
 * This session variable is used to decrypt the stored user passwords when needed.
 *
 * When a user changes the password, Tiki will call onChangeUserPassword. There the value must be rehashed.
 * Changing a user's Tiki password directly in the database will not fire onChangeUserPassword,
 * making the stored passwords unreadable.
 *
 * The system needs to have the username + both the old and new passwords in cleartext,
 * in order order to be able to rehash the encrypted data. This may not always be possible.
 * When an admin "hard" sets a user password, without having to know the previous password,
 * the old password is unknown. The encrypted data can then no longer be decrypted when the user logs in,
 * since the "secret key" has changed. The user will have to re-enter the lost data.
 * A recovery is possible. The recovery mechanism should call onChangeUserPassword.
 */
class CryptLib extends TikiLib
{
    // MCrypt attributes (Old, phased out encryption) . Kept for conversion of existing data
    private $mcrypt_key;	// mcrypt key
    private $mcrypt;		// mcrypt object
    private $mcrypt_prefprefix = 'dp';		// prefix for user pref keys: 'test' => 'dp.test'

    // OpenSSL attributes
    private $hasOpenSSL = false;
    private $cryptMethod = 'aes-256-ctr';
    private $key;					// crypt key
    private $prefprefix = 'ds';		// prefix for user pref keys: 'test' => 'ds.test'

    // Sodium attributes
    private $hasSodium = false;

    //
    // Init and release
    ////////////////////////////////

    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        $this->release();
    }

    public function init()
    {
        if (! isset($_SESSION['cryptphrase'])) {
            throw new Exception(tra('Unable to locate cryptphrase'));
        }
        $phraseMD5 = $_SESSION['cryptphrase'];
        $this->initSeed($phraseMD5);
    }

    public function initSeed($phraseMD5)
    {
        if (extension_loaded('sodium')) {
            $this->hasSodium = true;
            $this->prefprefix = 'du';
        }

        if (extension_loaded('openssl')) {
            $this->hasOpenSSL = true;
            $this->key = $phraseMD5;
        }

        if (extension_loaded('mcrypt') && $this->mcrypt == null) {
            $this->mcrypt_key = $phraseMD5;

            // Using Rijndael 256 in CBC mode.
            $this->mcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'cbc', '');
        }
    }
    public function makeCryptPhrase($username, $cleartextPwd)
    {
        return md5($username . $cleartextPwd);
    }

    public function release()
    {
        if ($this->hasOpenSSL) {
            $this->key = null;
        }
        if ($this->mcrypt != null) {
            mcrypt_module_close($this->mcrypt);
            $this->mcrypt_key = null;
            $this->mcrypt = null;
        }
    }


    //
    // Test/Check utilities
    ////////////////////////////////

    /**
     * Check if Sodium encryption is used
     *
     * @return bool
     */
    public function hasSodiumCrypt()
    {
        return $this->hasSodium;
    }

    // Check if encryption is used (and not Base64)
    public function hasCrypt()
    {
        return $this->hasOpenSSL;
    }

    // Check if MCrypt module is available in case a conversion is needed
    public function hasMCrypt()
    {
        return $this->mcrypt != null;
    }

    // Check if any data exists the user preference.
    // Return true if data exit (not necessarily readable). false, if no stored data is found
    public function hasUserData($userprefKey, $paramName = '')
    {
        global $user;

        if (! empty($paramName)) {
            $paramName = '.' . $paramName;
        }
        $storedPwd64 = $this->get_user_preference($user, $this->prefprefix . '.' . $userprefKey . $paramName);
        if (empty($storedPwd64)) {
            // Check if old, mcrypt encrypted data exist
            // Decryption is done when getting data
            $storedPwdMCrypt = $this->get_user_preference($user, $this->mcrypt_prefprefix . '.' . $userprefKey . $paramName);
            if (empty($storedPwdMCrypt)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the number of rows associated with the specified cryptographic method
     * @param string $method "mcrypt" for MCrypt, or "openssl" for OpenSSL, or "sodium" for Sodium
     * @return int Number of rows
     */
    public function getUserCryptDataStats($method)
    {
        if ($method == 'mcrypt') {
            $pattern = 'dp.%';
        } elseif ($method == 'openssl') {
            $pattern = 'ds.%';
        } elseif ($method == 'sodium') {
            $pattern = 'du.%';
        } else {
            throw new DomainException('Invalid method');
        }

        return $this->getOne('SELECT count(*) as Nr FROM `tiki_user_preferences` WHERE `prefName` like \'' . $pattern . "'");
    }

    //
    // User data utilities
    ////////////////////////////////


    /*
     * Encrypt and save the data in the user preferences.
     * The class specified prefix will be applied to the pref key.
     * So, is the paramName, if specified. Given...
     * $prefprefix = 'pwddom';
     * $userprefKey = 'test'
     * $paramName = ''
     * => pwddom.test
     * if $paramName = 'user', then
     * * => pwddom.test.user
     */
    //
    // Return false on failure otherwise the generated crypt text
    public function setUserData($userprefKey, $cleartext, $paramName = '')
    {
        global $user;
        if (empty($cleartext)) {
            return false;
        }
        $storedPwd64 = $this->encryptData($cleartext);
        if (! empty($paramName)) {
            $paramName = '.' . $paramName;
        }
        $this->set_user_preference($user, $this->prefprefix . '.' . $userprefKey . $paramName, $storedPwd64);

        return $storedPwd64;
    }

    /*
     * Encrypt and save the data in the user preferences for the specified user.
     * The class specified prefix will be applied to the pref key.
     * So, is the paramName, if specified. Given...
     * $username = 'myuser'
     * $prefprefix = 'pwddom';
     * $userprefKey = 'test'
     * $paramName = ''
     * => pwddom.test
     * if $paramName = 'user', then
     * * => pwddom.test.user
     */
    //
    // Return false on failure otherwise the generated crypt text
    public function putUserData($username, $userprefKey, $cleartext, $paramName = '')
    {
        if (empty($cleartext)) {
            return false;
        }
        $storedPwd64 = $this->encryptData($cleartext);
        if (! empty($paramName)) {
            $paramName = '.' . $paramName;
        }
        $this->set_user_preference($username, $this->prefprefix . '.' . $userprefKey . $paramName, $storedPwd64);

        return $storedPwd64;
    }

    // Get the data from the user preferences.
    // Decrypt and return cleartext
    // Return false, if no stored data is found
    public function getUserData($userprefKey, $paramName = '')
    {
        global $user;

        if (! empty($paramName)) {
            $paramName = '.' . $paramName;
        }
        $storedPwd64 = $this->get_user_preference($user, $this->prefprefix . '.' . $userprefKey . $paramName);
        if (empty($storedPwd64)) {
            return false;
        }
        $cleartext = $this->decryptData($storedPwd64);
        

        // Check if the cleartext contain any illigal password character.
        // 	If found, it indicates that the decryption has failed.
        if (! ctype_print($cleartext)) {
            return false;
        }

        return $cleartext;
    }

    // Recover the stored cleartext data from the user preferences.
    // Return stored data in cleartext or false on error
    /*
     * WARNING: Not converted to OpenSSL
    function recoverUserData($username, $cleartextPwd, $userprefKey, $paramName = '')
    {
        if (empty($cleartextPwd)) {
            return false;
        }
        // Initialize using the input params
        $cryptlib = new CryptLib();
        $phraseMD5 = md5($username.$cleartextPwd);
        $cryptlib->initSeed($phraseMD5);

        // Build the pref key
        if (!empty($paramName)) {
            $paramName = '.'.$paramName;
        }
        $prefKey = $cryptlib->prefprefix.'.'.$userprefKey.$paramName;

        // Get the stored data
        $storedPwd64 = $cryptlib->get_user_preference($username, $prefKey);
        if (empty($storedPwd64)) {
            return false;
        }

        // Decrypt
        $cleartext = $cryptlib->decryptData($storedPwd64);
        // Check if the cleartext contain any illigal password character.
        // 	If found, it indicates that the decryption has failed.
        if (!ctype_print ($cleartext)) {
            return false;
        }

        return $cleartext;
    }
*/
    public function getPasswordDomains($use_prefix = false)
    {
        global $prefs;

        // Load the domain ddefinitions
        $domainsText = $prefs['feature_password_domains'];
        $domains = explode(',', $domainsText);

        // Trim whitespace from names
        foreach ($domains as &$dom) {
            $dom = trim($dom);
        }

        // Add prefix
        if ($use_prefix) {
            foreach ($domains as &$dom) {
                $dom = $this->prefprefix . '.' . $dom;
            }
        }

        return $domains;
    }

    //
    // Data encryption
    ////////////////////////////////

    // Encrypt data
    // Return encrypted data, or false on error
    private function encryptData($cleartextData)
    {
        if (empty($cleartextData)) {
            return false;
        }

        // Due to appending spaces to short input data, short cleartext data cannot end with space
        $pwdLen = mb_strlen($cleartextData);
        if ($pwdLen < 20 && $cleartextData[$pwdLen] == ' ') {
            throw new Exception('Data to encrypt cannot end with a space');
        }
        // Make sure the data is at least 20 characters long
        // The spaces are trimmed when decrypting
        while (mb_strlen($cleartextData) < 20) {
            $cleartextData .= ' ';
        }

        // Encrypt the data
        $cryptData = $this->encrypt($cleartextData);
        if (empty($cryptData)) {
            return false;
        }

        // Save iv in the stored data
        $cryptData64 = base64_encode($cryptData);

        return $cryptData64;
    }

    // Decrypt data
    // Return cleartext data, or false on error
    private function decryptData($cryptData64)
    {
        if (empty($cryptData64)) {
            return false;
        }

        // Extract the iv and crypttext
        $cryptData = base64_decode($cryptData64);

        // Decrypt
        $cleartext = $this->decrypt($cryptData);

        return rtrim($cleartext);
    }

    //
    // Tiki events
    ////////////////////////////////

    // User has logged in
    public function onUserLogin($cleartextPwd)
    {
        global $user;

        // Encode the phrase
        $phraseMD5 = $this->makeCryptPhrase($user, $cleartextPwd);

        // Store the pass phrase in a session variable
        $_SESSION['cryptphrase'] = $phraseMD5;

        $this->convertMCryptDataToOpenSSL($user);
        $this->convertOpenSSLDataToSodium($user);
    }

    // User has changed the password
    // Change/Rehash the password, given the old and the new key phrases
    public function onChangeUserPassword($oldCleartextPwd, $newCleartextPwd)
    {
        global $user;

        // Lookup pref key that are encrypted data
        $domains = $this->getPasswordDomains();

        // Rehash encrypted preferences
        foreach ($domains as $userprefKey) {
            $rc = $this->changeUserPassword($userprefKey, md5($user . $oldCleartextPwd), md5($user . $newCleartextPwd));

            // Also update the username, if defined
            if ($rc && $this->hasUserData($userprefKey, 'usr')) {
                $this->changeUserPassword($userprefKey . '.usr', md5($user . $oldCleartextPwd), md5($user . $newCleartextPwd));
            }
        }

        // Save the new cryptphrase, so the new hash is readable without logging out
        $this->onUserLogin($newCleartextPwd);
    }

    // Change/Rehash the password, given the old and the new key phrases
    // Return true on success; otherwise false, e.g. if no stored password is found, or a decryption failure
    public function changeUserPassword($userprefKey, $oldPhraseMD5, $newPhraseMD5)
    {
        global $user;
        // Retrieve the old password
        $cryptOld = new CryptLib();
        $cryptOld->initSeed($oldPhraseMD5);
        if (! $cryptOld->hasCrypt()) {
            // Crypt is not available.
            // Only Base64 encoding. No conversion needed
            return false;
        }
        $cleartextPwd = $cryptOld->getUserData($userprefKey);
        $cryptOld->release();
        if ($cleartextPwd == false) {
            return false;
        }

        // Check if the cleartext contain any illigal password character.
        // 	If found, it indicates that the decryption has failed. The $oldPhraseMD5 may be incorrect?
        //  Then, do not proceed to rehash the password
        if (! ctype_print($cleartextPwd)) {
            return false;
        }

        // Rehash and save
        $cryptNew = new CryptLib();
        $cryptNew->initSeed($newPhraseMD5);
        $cryptPwd = $cryptNew->setUserData($userprefKey, $cleartextPwd);
        $cryptNew->release();
        if ($cryptPwd == false) {
            return false;
        }

        // Rehashed OK
        return true;
    }


    //
    // Crypt
    ////////////////////////////////

    // Use OpenSSL if available. Otherwise Base64 encode only
    // Return base64 encoded string, containing either the crypttext with the iv prepended, or the cleartext if on base64 encoding is used
    private function encrypt($cleartext)
    {
        if ($this->hasSodiumCrypt()) {
            $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $crypttext = $key . sodium_crypto_secretbox($cleartext, $nonce, $key) . $nonce;
        } elseif ($this->hasCrypt()) {
            $ivSize = openssl_cipher_iv_length($this->cryptMethod);
            $iv = openssl_random_pseudo_bytes($ivSize);

            $crypttext = openssl_encrypt(
                $cleartext,
                $this->cryptMethod,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            // Prepend the iv
            $crypttext = $iv . $crypttext;
        } else {
            $crypttext = base64_encode($cleartext);
        }

        return $crypttext;
    }

    // Use OpenSSL if available. Otherwise Base64 decode
    // Return cleartext
    private function decrypt($crypttext)
    {
        if ($this->hasSodiumCrypt()) {
            $key = trim(substr($crypttext, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
            $nonce = trim(substr($crypttext, -SODIUM_CRYPTO_SECRETBOX_NONCEBYTES));
            $ciphertextLength = strlen($crypttext) - (SODIUM_CRYPTO_SECRETBOX_KEYBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $crypttext = trim(substr($crypttext, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $ciphertextLength));
            $rawcleartext = sodium_crypto_secretbox_open($crypttext, $nonce, $key);
        } elseif ($this->hasCrypt()) {
            $ivSize = openssl_cipher_iv_length($this->cryptMethod);
            $iv = substr($crypttext, 0, $ivSize);
            $ciphertext = substr($crypttext, $ivSize);

            $rawcleartext = openssl_decrypt(
                $ciphertext,
                $this->cryptMethod,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );
        } else {
            // Use Base64 encoding
            $rawcleartext = base64_decode($crypttext);
        }

        // Clear trailing null-characters
        $cleartext = rtrim($rawcleartext);

        return $cleartext;
    }


    //
    // Old MCrypt coded data conversion
    ////////////////////////////////

    /**
     * Decrypt OpenSSL data
     *
     * @param $crypttext
     * @return Mixed
     */
    private function decryptOpenSll($crypttext)
    {
        $cleartext = null;

        if ($this->hasCrypt()) {
            $crypttext = base64_decode($crypttext);
            $ivSize = openssl_cipher_iv_length($this->cryptMethod);
            $iv = substr($crypttext, 0, $ivSize);
            $ciphertext = substr($crypttext, $ivSize);

            $cleartext = openssl_decrypt(
                $ciphertext,
                $this->cryptMethod,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            // Clear trailing null-characters
            $cleartext = rtrim($cleartext);
        }

        return $cleartext;
    }

    // Use MCrypt if available. Otherwise Base64 decode
    // Return cleartext
    private function decryptMcrypt($cryptData64)
    {
        if ($this->hasMCrypt()) {
            $cryptData = base64_decode($cryptData64);

            $ivSize = mcrypt_enc_get_iv_size($this->mcrypt);
            $iv = substr($cryptData, 0, $ivSize);
            $crypttext = substr($cryptData, $ivSize);

            $rawcleartext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->mcrypt_key, $crypttext, MCRYPT_MODE_CBC, $iv);

            // Clear trailing null-characters
            $cleartext = rtrim($rawcleartext);
        } else {
            // Use Base64 encoding
            $cleartext = base64_decode($cryptData64);
        }

        return $cleartext;
    }

    private function convertMCryptDataToOpenSSL($login)
    {
        $this->init();

        // Convert encrypted data, if OpenSSL is installed
        if ($this->hasCrypt()) {
            $query = 'SELECT `prefName` , `value` FROM `tiki_user_preferences` WHERE `prefName` like \'dp.%\' and  `user` = ?';
            $result = $this->query($query, [$login]);

            while ($row = $result->fetchRow()) {
                $orgPrefName = $row['prefName'];
                $storedPwdMCrypt64 = $row['value'];

                if ($this->hasMCrypt()) {
                    $cleartext = $this->decryptMcrypt($storedPwdMCrypt64);

                    // Strip dp. from prefName
                    $prefName = str_replace('dp.', '', $orgPrefName);

                    // Add new OpenSSL coded user data
                    $this->setUserData($prefName, $cleartext);
                }

                // Delete old Mcrypt coded user data
                $userPreferences = $this->table('tiki_user_preferences', false);
                $userPreferences->delete(['user' => $login, 'prefName' => $orgPrefName]);
            }
        }
    }

    /**
     * Convert OpenSSL encrypted data to Sodium
     *
     * @param $login
     * @return null
     */
    private function convertOpenSSLDataToSodium($login)
    {
        $this->init();

        // Convert encrypted OpenSSL data, if Sodium is installed
        if ($this->hasSodiumCrypt()) {
            $query = 'SELECT `prefName` , `value` FROM `tiki_user_preferences` WHERE `prefName` like \'ds.%\' and  `user` = ?';
            $result = $this->query($query, [$login]);

            while ($row = $result->fetchRow()) {
                $orgPrefName = $row['prefName'];
                $storedPwdMCrypt64 = $row['value'];

                if ($this->hasCrypt()) {
                    $cleartext = $this->decryptOpenSll($storedPwdMCrypt64, $orgPrefName);

                    if (empty($cleartext)) {
                        continue;
                    }

                    // Strip ds. from prefName
                    $prefName = str_replace('ds.', '', $orgPrefName);

                    // Add new Sodium coded user data
                    $this->setUserData($prefName, $cleartext);
                }

                // Delete old OpenSSL coded user data
                $userPreferences = $this->table('tiki_user_preferences', false);
                $userPreferences->delete(['user' => $login, 'prefName' => $orgPrefName]);
            }
        }
    }
}
