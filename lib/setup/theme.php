<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

//Initialize variables for the actual theme and theme option to be displayed
$theme_active = $prefs['theme'];
$theme_option_active = $prefs['theme_option'];

// User theme previously set up in lib/setup/user_prefs.php

//consider Group theme
if ($prefs['useGroupTheme'] == 'y') {
    $userlib = TikiLib::lib('user');
    $users_group_groupTheme = $userlib->get_user_group_theme();
    if (! empty($users_group_groupTheme)) {
        //group theme and option is stored in one column (groupTheme) in the users_groups table, so the theme and option value needs to be separated first
        list($group_theme, $group_theme_option) = $themelib->extract_theme_and_option($users_group_groupTheme); //for more info see list_themes_and_options() function in themelib

        //set active theme
        $theme_active = $group_theme;
        $theme_option_active = $group_theme_option;

        //set group_theme smarty variable so that it can be used elsewhere
        $smarty->assign_by_ref('group_theme', $users_group_groupTheme);
    }
}

//consider Admin Theme
if (! empty($prefs['theme_admin']) && ($section === 'admin' || empty($section))) {		// use admin theme if set
    $theme_active = $prefs['theme_admin'];
    $theme_option_active = $prefs['theme_option_admin'];								// and its option
}

//consider CSS Editor (tiki-edit_css.php)
if (! empty($_SESSION['try_theme'])) {
    list($theme_active, $theme_option_active) = $themelib->extract_theme_and_option($_SESSION['try_theme']);
}

//START loading theme related items

//1) Always add default bootstrap JS and make some preference settings (adding popper.js required for bootstrap 4)
$headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/popper.js/dist/umd/popper.js');
$headerlib->add_jsfile('vendor_bundled/vendor/twbs/bootstrap/dist/js/bootstrap.js');
$headerlib->add_jsfile('lib/jquery_tiki/tiki-bootstrapmodalfix.js');

if ($prefs['feature_fixed_width'] === 'y') {
    $headerlib->add_css(
        '@media (min-width: 1200px) { .container { max-width:' .
        (! empty($prefs['layout_fixed_width']) ? $prefs['layout_fixed_width'] : '1170px') .
        '; } }'
    );
}

//2) Always add tiki_base.css. Add it first, so that it can be overriden in the custom themes
$headerlib->add_cssfile("themes/base_files/css/tiki_base.css");

//3) Always add bundled font-awesome css for the default icon fonts
$headerlib->add_cssfile('vendor_bundled/vendor/bower-asset/fontawesome/css/all.css');

//4) Add Addon custom css first, so it can be overridden by themes
foreach (\Tiki\Package\ExtensionManager::getEnabledPackageExtensions() as $package) {
    $finder = new \Symfony\Component\Finder\Finder();
    foreach ($finder->in($package['path'])->path('/^css/')->name('*.css') as $file) {
        $cssFile = $package['path'] . '/' . $file->getRelativePathname();
        $headerlib->add_cssfile($cssFile);
    }
}

//5) Now add the theme or theme option
$themelib = TikiLib::lib('theme');

if (! empty($prefs['header_custom_scss'])) {
    // TODO call compile_custom_scss() here
} elseif ($theme_active == 'custom_url' && ! empty($prefs['theme_custom_url'])) { //custom URL, use only if file exists at the custom location
    $custom_theme = $prefs['theme_custom_url'];
    if (preg_match('/^(http(s)?:)?\/\//', $custom_theme)) { // Use external link if url begins with http://, https://, or // (auto http/https)
        $headerlib->add_cssfile($custom_theme, 'external');
    } else {
        $headerlib->add_cssfile($custom_theme);
    }
} else {
    //first load the main theme css
    $theme_css = $themelib->get_theme_css($theme_active);
    if ($theme_css) {
        // exclude the main theme css if the option's css also includes it (pref is set)
        if ($prefs['theme_option_includes_main'] != 'y' || empty($theme_option_active)) {
            $headerlib->add_cssfile($theme_css);
        }
        //than load the theme option css file if needed
        if (! empty($theme_option_active)) {
            $option_css = $themelib->get_theme_css($theme_active, $theme_option_active);
            $headerlib->add_cssfile($option_css);
        }
    } else {
        trigger_error("The requested theme's CSS file could not be read. Falling back to default theme.", E_USER_WARNING);
        $theme_active = 'default';
        $theme_option_active = '';
        $theme_css = $themelib->get_theme_css($theme_active);
        $headerlib->add_cssfile($theme_css);
    }
}

//6) Allow to have a IE specific CSS files for the theme's specific hacks (IE 8 and 9 support dropped in Bootstrap 4)
// $style_ie8_css = $themelib->get_theme_path($theme_active, $theme_option_active, 'ie8.css');
// $style_ie9_css = $themelib->get_theme_path($theme_active, $theme_option_active, 'ie9.css');

//7) include optional custom.css if there. In case of theme option, first include main theme's custom.css, than the option's custom.css
if (! empty($theme_option_active)) {
    $main_theme_path = $themelib->get_theme_path($theme_active);
    $main_theme_custom_css = "{$main_theme_path}css/custom.css";
    if (is_readable($main_theme_custom_css)) {
        $headerlib->add_cssfile($main_theme_custom_css, 53);
    }
}

$custom_css = $themelib->get_theme_path($theme_active, $theme_option_active, 'custom.css');
if (empty($custom_css)) {
    $custom_css = $themelib->get_theme_path('', '', 'custom.css');
}
if (is_readable($custom_css)) {
    $headerlib->add_cssfile($custom_css, 53);
}
if (! isset($prefs['site_favicon_enable']) || $prefs['site_favicon_enable'] === 'y') {    // if favicons are disabled in preferences, skip the lot of it.
    $favicon_path = $themelib->get_theme_path($prefs['theme'], $prefs['theme_option'], 'favicon-16x16.png', 'favicons/');
    if ($favicon_path) {  // if there is a 16x16 png favicon in the theme folder, then find and display others if they exist
        $headerlib->add_link('icon', $favicon_path, '16x16', 'image/png');
        $favicon_path = (dirname($favicon_path)); // get_theme_path makes a lot of system calls, so just remember what dir to look in.
        if (is_file($favicon_path . '/apple-touch-icon.png')) {
            $headerlib->add_link('apple-touch-icon', $favicon_path . '/apple-touch-icon.png', '180x180');
        }
        if (is_file($favicon_path . '/favicon-32x32.png')) {
            $headerlib->add_link('icon', $favicon_path . '/favicon-32x32.png', '32x32', 'image/png');
        }
        if (is_file($favicon_path . '/site.webmanifest')) {
            $headerlib->add_link('manifest', $favicon_path . '/site.webmanifest');
        // The file name changed, so check for the old file if the new does not exist
        } elseif (is_file($favicon_path . '/manifest.json')) {
            $headerlib->add_link('manifest', $favicon_path . '/manifest.json');
        }
        if (is_file($favicon_path . '/favicon.ico')) {
            $headerlib->add_link('shortcut icon', $favicon_path . '/favicon.ico');
        }
        if (is_file($favicon_path . '/safari-pinned-tab.svg')) {
            $headerlib->add_link('mask-icon', $favicon_path . '/safari-pinned-tab.svg', '', '', '#5bbad5');
        }
        if (is_file($favicon_path . '/browserconfig.xml')) {
            $headerlib->add_meta('msapplication-config', $favicon_path . '/browserconfig.xml');
        }
    } else {    // if no 16x16 png favicon exists, display Tiki icons
        $headerlib->add_link('icon', 'themes/base_files/favicons/favicon-16x16.png', '16x16', 'image/png');
        $headerlib->add_link('apple-touch-icon', 'themes/base_files/favicons/apple-touch-icon.png', '180x180');
        $headerlib->add_link('icon', 'themes/base_files/favicons/favicon-32x32.png', '32x32', 'image/png');
        $headerlib->add_link('manifest', 'themes/base_files/favicons/site.webmanifest');
        $headerlib->add_link('shortcut icon', 'themes/base_files/favicons/favicon.ico');
        $headerlib->add_link('mask-icon', 'themes/base_files/favicons/safari-pinned-tab.svg', '', '', '#5bbad5');
        $headerlib->add_meta('msapplication-config', 'themes/base_files/favicons/browserconfig.xml');
    }
    unset($favicon_path);  // no longer needed, so bye bye
}

//8) produce $iconset to be used for generating icons
$iconset = TikiLib::lib('iconset')->getIconsetForTheme($theme_active, $theme_option_active);
// and add js support file
$headerlib->add_js('jqueryTiki.iconset = ' . json_encode($iconset->getJS()));
$headerlib->add_jsfile('lib/jquery_tiki/iconsets.js');

//9) set global variable and prefs so that they can be accessed elsewhere
$prefs['theme'] = $theme_active;
$prefs['theme_option'] = $theme_option_active;

//10) load additional language overrides that might be located in theme folder
/** @var Language $langLib */
$langLib = TikiLib::lib('language');
$langLib->loadThemeOverrides($prefs['language'], $theme_active);

//Note: if Theme Control is active, than tiki-tc.php can modify the active theme

//finish
$smarty->initializePaths();
