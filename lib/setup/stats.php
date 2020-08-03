<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

if ($prefs['feature_referer_stats'] == 'y') {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $pref = parse_url($_SERVER['HTTP_REFERER']);
        if (isset($pref['host']) && ! strstr($_SERVER['SERVER_NAME'], $pref['host'])) {
            $tikilib->register_referer($pref['host'], $_SERVER['HTTP_REFERER']);
        }
    }
}

if (StatsLib::is_stats_hit()) {
    if (! isset($section) or ($section != 'chat' and $section != 'livesupport')) {
        $statslib = TikiLib::lib('stats');
        $statslib->add_pageview();
    }
}
