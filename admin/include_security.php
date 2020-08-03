<?php

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

TikiLib::lib('smarty')->assign('sodium_available', extension_loaded('sodium'));
TikiLib::lib('smarty')->assign('openssl_available', extension_loaded('openssl'));

if ($prefs['feature_user_encryption'] == 'y') {
    $cryptlib = TikiLib::lib('crypt');
    $smarty->assign('show_user_encyption_stats', 'y');
    $smarty->assign('user_encryption_stat_mcrypt', $cryptlib->getUserCryptDataStats('mcrypt'));
    $smarty->assign('user_encryption_stat_openssl', $cryptlib->getUserCryptDataStats('openssl'));
    $smarty->assign('user_encryption_stat_sodium', $cryptlib->getUserCryptDataStats('sodium'));
}
