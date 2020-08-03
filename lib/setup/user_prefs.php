<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Handle the current user prefs in session
if (! isset($_SESSION['u_info']) || $_SESSION['u_info']['login'] != $user) {
    $_SESSION['u_info'] = [];
    $_SESSION['u_info']['login'] = $user;
    $_SESSION['u_info']['group'] = ($user) ? $userlib->get_user_default_group($user) : '';
    if (empty($user)) {
        $_SESSION['preferences'] = []; // For anonymous, store some preferences like the theme in the session.
    }
}

// Define the globals $u_info array for use in php / smarty
$u_info = & $_SESSION['u_info'];
$smarty->assign_by_ref('u_info', $u_info);

$smarty->assign_by_ref('user', $user);
$user_preferences = []; // Used for cache

if ($user) {
    $default_group = $group = $_SESSION['u_info']['group'];
    $smarty->assign('group', $group); // do not use by_ref as $group can be changed in the .php
    $smarty->assign('default_group', $group);

    // Initialize user preferences

    // Defaults that are not in global prefs
    $prefs['user_article_watch_editor'] = 'n';
    $prefs['user_blog_watch_editor'] = 'n';
    $prefs['user_calendar_watch_editor'] = 'n';
    $prefs['user_wiki_watch_editor'] = 'n';
    $prefs['user_tracker_watch_editor'] = 'n';
    $prefs['user_comment_watch_editor'] = 'n';
    $prefs['user_category_watch_editor'] = 'n';
    $prefs['user_plugin_approval_watch_editor'] = 'n';

    // Get all user prefs in one query
    $tikilib->get_user_preferences($user);

    // Check pref for user theme
    if ($prefs['change_theme'] !== 'y') {
        unset($user_preferences[$user]['theme']);
        unset($user_preferences[$user]['theme_option']);
    } else {
        if (! empty($user_preferences[$user]['theme']) && empty($user_preferences[$user]['theme_option'])) {
            $prefs['theme_option'] = '';
        }
    }

    // Prefs overriding
    $prefs = array_merge($prefs, $user_preferences[$user]);

    // Set the userPage name for this user since other scripts use this value.
    $userPage = $prefs['feature_wiki_userpage_prefix'] . $user;
    $exist = $tikilib->page_exists($userPage);
    $smarty->assign('userPage', $userPage);
    $smarty->assign('userPage_exists', $exist);
} else {
    if (isset($_SESSION['preferences'])) {
        $prefs = array_merge($prefs, $_SESSION['preferences']);
    }
    $allowMsgs = 'n';
}

$smarty->assign('IP', $tikilib->get_ip_address());

$tikilib->set_display_timezone($user);

if (! empty($section) && $section == 'admin' && ! empty($prefs['language_admin'])) {
    $prefs['language'] = $prefs['language_admin'];
}

$smarty->refreshLanguage();

if ($prefs['language'] != $prefs['site_language']) {
    $prefslib = TikiLib::lib('prefs');
    $translatablePreferences = $prefslib->getTranslatablePreferences();

    foreach ($translatablePreferences as $preference) {
        if (! empty($prefs[$preference . '_' . $prefs['language']])) {
            $prefs[$preference . '_translated'] = $prefs[$preference . '_' . $prefs['language']];
        }
    }
}
