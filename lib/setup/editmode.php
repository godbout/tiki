<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) != false) {
    header('location: index.php');
    exit;
}
global $tiki_p_admin, $tiki_p_use_HTML, $prefs, $info, $jitRequest, $is_html;

if (! isset($is_html)) {
    if (isset($info['is_html'])) {
        $is_html = $info['is_html'];
    } else {
        $is_html = false;
    }
}
if ($prefs['feature_wysiwyg'] == 'y' && $prefs['javascript_enabled'] == 'y') {
    if ($prefs['wysiwyg_optional'] == 'n') {
        $_SESSION['wysiwyg'] = 'y';
    } else {
        if (isset($_REQUEST['mode_wysiwyg']) && $_REQUEST['mode_wysiwyg'] == 'y') {
            $_SESSION['wysiwyg'] = 'y';
        } elseif (isset($_REQUEST['mode_normal']) && $_REQUEST['mode_normal'] == 'y') {
            $_SESSION['wysiwyg'] = 'n';
        } elseif (isset($_REQUEST['wysiwyg'])) {
            if (! in_array($_REQUEST['wysiwyg'], ['y', 'n'])) {
                throw new Exception('Invalid wysiwyg parameter');
            }
            $_SESSION['wysiwyg'] = $_REQUEST['wysiwyg'];
        } elseif ($prefs['wysiwyg_memo'] == 'y' and ! empty($info['wysiwyg'])) {
            $_SESSION['wysiwyg'] = $info['wysiwyg'];
        } else {
            $_SESSION['wysiwyg'] = $prefs['wysiwyg_default'];
        }
    }
} else {
    $_SESSION['wysiwyg'] = 'n';
}


if ($_SESSION['wysiwyg'] == 'y') {
    if ($prefs['wysiwyg_htmltowiki'] !== 'y' && ! isset($info['is_html'])) { // new pages in wysiwyg mode
        $is_html = true;
    }
    if ($is_html) {
        $prefs['feature_wiki_allowhtml'] = 'y';		// FIXME: hack. If page is HTML, temporarily allow html even if pref disables it.
    }
} elseif ($prefs['feature_wiki_allowhtml'] == 'y' and ($tiki_p_admin == 'y' or $tiki_p_use_HTML == 'y')) {
    if (isset($_REQUEST['preview']) || isset($jitRequest['edit'])) {
        if (isset($_REQUEST["allowhtml"]) && $_REQUEST["allowhtml"] == "on") {
            $is_html = true;
        } elseif ($_SESSION['wysiwyg'] === 'n' || $prefs['wysiwyg_htmltowiki'] === 'y') {	// unchecked
            $is_html = false;
        }
    } else {
        if (isset($info['is_html']) and $info['is_html']) {
            $is_html = true;
        }
    }
}
if (isset($jitRequest['edit'])) {
    // Restore the property for the rest of the script
    if ($is_html) {
        $data = $jitRequest->edit->none();
        $parserlib = TikiLib::lib('parser');
        $noparsed = [];
        $parserlib->plugins_remove($data, $noparsed);

        $data = TikiFilter::get('xss')->filter($data);

        $parserlib->plugins_replace($data, $noparsed, true);
        $_REQUEST['edit'] = $data;
    } else {
        $_REQUEST['edit'] = $jitRequest->edit->wikicontent();
    }

    //html is stored encoded in wysiwyg
    if (isset($jitRequest['wysiwyg']) && $jitRequest['wysiwyg'] == 'y') {
        $_REQUEST['edit'] = html_entity_decode($_REQUEST['edit'], ENT_QUOTES, 'UTF-8');
    }
}
