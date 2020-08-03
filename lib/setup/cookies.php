<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

$headerlib = TikiLib::lib('header');
$smarty = TikiLib::lib('smarty');

if (isset($_SESSION['tiki_cookie_jar'])) {
    $cookielist = [];

    if (is_array($_SESSION['tiki_cookie_jar'])) {
        $smarty->loadPlugin('smarty_modifier_escape');
        foreach ($_SESSION['tiki_cookie_jar'] as $nn => $vv) {
            $cookielist[] = "'" . smarty_modifier_escape($nn, 'javascript') . "': '" . smarty_modifier_escape($vv, 'javascript') . "'";
        }
    }

    if (count($cookielist)) {
        $headerlib->add_js('tiki_cookie_jar={' . implode(',', $cookielist) . '};');
    }
    $_COOKIE = array_merge($_SESSION['tiki_cookie_jar'], $_COOKIE);
} else {
    $headerlib->add_js('tiki_cookie_jar=new Object();');
}

$smarty->assign_by_ref('cookie', $_COOKIE);

function getCookie($name, $section = null, $default = null)
{
    global $feature_no_cookie, $jitCookie;

    if (isset($_COOKIE[$name])) {
        $cookie = $_COOKIE[$name];
    } elseif (isset($jitCookie[$name])) {
        $cookie = $jitCookie[$name];
    }

    if ($feature_no_cookie || (empty($section) && ! isset($cookie) && isset($_SESSION['tiki_cookie_jar'][$name]))) {
        if (isset($_SESSION['tiki_cookie_jar'][$name])) {
            return $_SESSION['tiki_cookie_jar'][$name];
        }

        return $default;
    } elseif ($section) {
        if (isset($_COOKIE[$section])) {
            if (preg_match("/@" . preg_quote($name, '/') . "\:([^@;]*)/", $_COOKIE[$section], $matches)) {
                return $matches[1];
            }

            return $default;
        }

        return $default;
    }
    if (isset($cookie)) {
        return $cookie;
    }

    return $default;
}

function setCookieSection($name, $value, $section = '', $expire = null, $path = '', $domain = '', $secure = '')
{
    global $feature_no_cookie;

    if ($section) {
        $valSection = getCookie($section);
        $name2 = '@' . $name . ':';
        if ($valSection) {
            if (preg_match('/' . preg_quote($name2) . '/', $valSection)) {
                $valSection = preg_replace('/' . preg_quote($name2) . '[^@;]*/', $name2 . $value, $valSection);
            } else {
                $valSection = $valSection . $name2 . $value;
            }
            setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
        } else {
            $valSection = $name2 . $value;
            setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
        }
    } else {
        if ($feature_no_cookie) {
            $_SESSION['tiki_cookie_jar'][$name] = $value;
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure);
        }
    }
}
