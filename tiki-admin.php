<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


use Tiki\Package\ExtensionManager;
use Tiki\Suggestion\Rules;

$section = 'admin';

require_once('tiki-setup.php');
$adminlib = TikiLib::lib('admin');

$auto_query_args = ['page'];

$access->check_permission('tiki_p_admin');
$logslib = TikiLib::lib('logs');

/**
 * Display feedback on prefs changed
 *
 * @param string $name Name of feature
 * @param string $message Other message
 * @param int $st Type of change (0=disabled, 1=enabled, 2=changed, 3=info, 4=reset)
 * @param int $num unknown
 * @throws Exception
 * @return void
 */
function add_feedback($name, $message, $st, $num = null)
{
    TikiLib::lib('prefs')->addRecent($name);

    Feedback::add(['num' => $num,
        'mes' => $message,
        'st' => $st,
        'name' => $name,
        'tpl' => 'pref', ]);
}

/**
 * simple_set_toggle
 *
 * @param mixed $feature
 * @access public
 * @throws Exception
 * @return void
 */
function simple_set_toggle($feature)
{
    global $prefs;
    $logslib = TikiLib::lib('logs');
    $tikilib = TikiLib::lib('tiki');
    if (isset($_REQUEST[$feature]) && $_REQUEST[$feature] == 'on') {
        if ((! isset($prefs[$feature]) || $prefs[$feature] != 'y')) {
            // not yet set at all or not set to y
            if ($tikilib->set_preference($feature, 'y')) {
                add_feedback($feature, tr('%0 enabled', $feature), 1, 1);
                $logslib->add_action('feature', $feature, 'system', 'enabled');
            }
        }
    } else {
        if ((! isset($prefs[$feature]) || $prefs[$feature] != 'n')) {
            // not yet set at all or not set to n
            if ($tikilib->set_preference($feature, 'n')) {
                add_feedback($feature, tr('%0 disabled', $feature), 0, 1);
                $logslib->add_action('feature', $feature, 'system', 'disabled');
            }
        }
    }
    TikiLib::lib('cache')->invalidate('allperms');
}

/**
 * simple_set_value
 *
 * @param mixed $feature
 * @param string $pref
 * @param mixed $isMultiple
 * @access public
 * @throws Exception
 * @return void
 */
function simple_set_value($feature, $pref = '', $isMultiple = false)
{
    global $prefs;
    $logslib = TikiLib::lib('logs');
    $tikilib = TikiLib::lib('tiki');
    $old = $prefs[$feature];
    if (isset($_POST[$feature])) {
        if ($pref != '') {
            if ($tikilib->set_preference($pref, $_POST[$feature])) {
                $prefs[$feature] = $_POST[$feature];
            }
        } else {
            $tikilib->set_preference($feature, $_POST[$feature]);
        }
    } elseif ($isMultiple) {
        // Multiple selection controls do not exist if no item is selected.
        // We still want the value to be updated.
        if ($pref != '') {
            if ($tikilib->set_preference($pref, [])) {
                $prefs[$feature] = $_POST[$feature];
            }
        } else {
            $tikilib->set_preference($feature, []);
        }
    }
    if (isset($_POST[$feature]) && $old != $_POST[$feature]) {
        add_feedback($feature, ($_POST[$feature]) ? tr('%0 set', $feature) : tr('%0 unset', $feature), 2);
        $msg = '';
        if (is_array($_POST[$feature]) && is_array($old)) {
            $newCount = count($_POST[$feature]);
            $oldCount = count($old);
            if ($newCount > $oldCount) {
                $added = $newCount - $oldCount;
                $item = $added == 1 ? tr('item added') : tr('items added');
                $msg = $added . ' ' . $item;
            } elseif ($oldCount > $newCount) {
                $deleted = $oldCount - $newCount;
                $item = $deleted == 1 ? tr('item deleted') : tr('items deleted');
                $msg = $deleted . ' ' . $item;
            }
        } else {
            $msg = $old . ' => ' . $_POST[$feature];
        }
        $logslib->add_action('feature', $feature, 'system', $msg);
    }
    TikiLib::lib('cache')->invalidate('allperms');
}

$crumbs[] = new Breadcrumb(tra('Control Panels'), tra('Sections'), 'tiki-admin.php', 'Admin+Home', tra('Help on Configuration Sections', '', true));
// Default values for AdminHome
$admintitle = tra('Control Panels');
$helpUrl = 'Admin+Home';
$helpDescription = $description = '';
$url = 'tiki-admin.php';
$adminPage = '';

/*
 * Tiki System Suggestions
 */
$tikiShowSuggestionsPopup = false;
if ($prefs['feature_system_suggestions'] == 'y' && empty($_POST)) {
    $adminLogin = ! empty($_SESSION['u_info']['id']) ? $_SESSION['u_info']['id'] : '';
    $suggestionMessages = ! empty($_SESSION['suggestions_user_id_' . $adminLogin]) ? $_SESSION['suggestions_user_id_' . $adminLogin] : [];

    if (isset($_REQUEST['tikiSuggestionPopup'])) {
        $_SESSION['suggestions_popup_off_user_id_' . $adminLogin] = true;

        return;
    }

    if (! empty($adminLogin)
        && ! isset($_SESSION['suggestions_off_user_id_' . $adminLogin])
        && TikiLib::lib('user')->user_is_in_group($user, 'Admins')
    ) {
        if (isset($_REQUEST['tikiSuggestion'])) {
            $_SESSION['suggestions_off_user_id_' . $adminLogin] = true;

            return;
        }

        if (empty($suggestionMessages)) {
            $suggestionRules = new Rules();
            $suggestionMessages = $suggestionRules->getAllMessages();
        }

        if (! empty($suggestionMessages)) {
            $feedback['title'] = tra('Tiki Suggestions');
            $feedback['mes'] = $suggestionMessages;
            Feedback::note($feedback);
            $_SESSION['suggestions_user_id_' . $adminLogin] = $suggestionMessages;
        }
    }

    if (! empty($suggestionMessages) && ! isset($_SESSION['suggestions_popup_off_user_id_' . $adminLogin])) {
        $tikiShowSuggestionsPopup = true;
    }
}
$smarty->assign('tikiShowSuggestionsPopup', $tikiShowSuggestionsPopup);

$prefslib = TikiLib::lib('prefs');

if (isset($_REQUEST['pref_filters']) && $access->checkCsrf()) {
    $prefslib->setFilters($_REQUEST['pref_filters']);
    Feedback::success(tra('Default preference filters set'));
}

/*
 * If blacklist preferences have been updated and its also not being disabled
 * Then update the database with the selection.
 */

$blackL = TikiLib::lib('blacklist');

if (isset($_POST['pass_blacklist'])) {    // if preferences were updated and blacklist feature is enabled (or is being enabled)
    $pass_blacklist_file = $jitPost->pass_blacklist_file->striptags();
    $userfile = explode('-', $pass_blacklist_file);
    $userfile = $userfile[3];
    if ($userfile) {                       // if the blacklist is a user generated file
        $passDir = 'storage/pass_blacklists/';
    } else {
        $passDir = 'lib/pass_blacklists/';
    }
    if ($pass_blacklist_file === 'auto') {
        if ($_POST['min_pass_length'] != $GLOBALS['prefs']['min_pass_length'] ||
            $_POST['pass_chr_num'] != $GLOBALS['prefs']['pass_chr_num'] ||
            $_POST['pass_chr_special'] != $GLOBALS['prefs']['pass_chr_special']) {       // if blacklist is auto and an option is changed that could effect the selection
            $prefname = implode('-', $blackL->selectBestBlacklist($_POST['pass_chr_num'], $_POST['pass_chr_special'], $_POST['min_pass_length']));
            $filename = $passDir . $prefname . '.txt';
            $tikilib->set_preference('pass_auto_blacklist', $prefname);
            $blackL->loadBlacklist(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename);
        }
    } elseif ($pass_blacklist_file != $GLOBALS['prefs']['pass_blacklist_file']) {        // if manual selection mode has been changed
        $filename = $passDir . $pass_blacklist_file . '.txt';
        $blackL->loadBlacklist(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename);
    }
}

$temp_filters = isset($_REQUEST['filters']) ? explode(' ', $_REQUEST['filters']) : null;
$smarty->assign('pref_filters', $prefslib->getFilters($temp_filters));

if (isset($_POST['lm_preference']) && $access->checkCsrf()) {
    $changes = $prefslib->applyChanges((array) $_POST['lm_preference'], $_POST);
    foreach ($changes as $pref => $val) {
        if ($val['type'] == 'reset') {
            add_feedback($pref, tr('%0 reset', $pref), 4);
            $logslib->add_action('feature', $pref, 'system', 'reset');
        } else {
            $value = $val['new'];
            if ($value == 'y') {
                add_feedback($pref, tr('%0 enabled', $pref), 1, 1);
                $logslib->add_action('feature', $pref, 'system', 'enabled');
            } elseif ($value == 'n') {
                add_feedback($pref, tr('%0 disabled', $pref), 0, 1);
                $logslib->add_action('feature', $pref, 'system', 'disabled');
            } else {
                add_feedback($pref, tr('%0 set', $pref), 1, 1);
                $logslib->add_action('feature', $pref, 'system', (is_array($val['old']) ? implode($val['old'], ',') : $val['old']) . '=>' . (is_array($value) ? implode($value, ',') : $value));
            }
            /*
                Enable/disable addreference/showreference plugins alognwith references feature.
            */
            if ($pref == 'feature_references') {
                $tikilib->set_preference('wikiplugin_addreference', $value);
                $tikilib->set_preference('wikiplugin_showreference', $value);

                /* Add/Remove the plugin toolbars from the editor */
                $toolbars = ['wikiplugin_addreference', 'wikiplugin_showreference'];
                $t_action = ($value == 'y') ? 'add' : 'remove';
                $tikilib->saveEditorToolbars($toolbars, 'global', $t_action);
            }
        }
    }
}

if (isset($_REQUEST['lm_criteria'])) {
    set_time_limit(0);

    try {
        $smarty->assign('lm_criteria', $_REQUEST['lm_criteria']);
        $results = $prefslib->getMatchingPreferences($_REQUEST['lm_criteria']);
        $results = array_slice($results, 0, 50);
        $results = $prefslib->unsetHiddenPreferences($results);
        $smarty->assign('lm_searchresults', $results);
    } catch (ZendSearch\Lucene\Exception\ExceptionInterface $e) {
        Feedback::warning(['mes' => $e->getMessage(), 'title' => tr('Search error')]);
        $smarty->assign('lm_criteria', '');
        $smarty->assign('lm_searchresults', '');
    }
} else {
    $smarty->assign('lm_criteria', '');
    $smarty->assign('lm_searchresults', '');
}

$smarty->assign('indexNeedsRebuilding', $prefslib->indexNeedsRebuilding());

if (isset($_REQUEST['prefrebuild'])) {
    $prefslib->rebuildIndex();
    header('Location: ' . $base_url . 'tiki-admin.php');
}

$admin_icons = [
    "general" => [
        'title' => tr('General'),
        'description' => tr('Global site configuration, date formats, etc.'),
        'help' => 'General Admin',
    ],
    "features" => [
        'title' => tr('Features'),
        'description' => tr('Switches for major features'),
        'help' => 'Features Admin',
    ],
    "login" => [
        'title' => tr('Log in'),
        'description' => tr('User registration, remember me cookie settings and authentication methods'),
        'help' => 'Login Config',
    ],
    "user" => [
        'title' => tr('User Settings'),
        'description' => tr('User related preferences like info and picture, features, messages and notification, files, etc'),
        'help' => 'User Settings',
    ],
    "profiles" => [
        'title' => tr('Profiles'),
        'description' => tr('Repository configuration, browse and apply profiles'),
        'help' => 'Profiles',
    ],
    "look" => [
        'title' => tr('Look & Feel'),
        'description' => tr('Theme selection, layout settings and UI effect controls'),
        'help' => 'Look and Feel',
    ],
    "textarea" => [
        'title' => tr('Editing and Plugins'),
        'description' => tr('Text editing settings applicable to many areas. Plugin activation and plugin alias management'),
        'help' => 'Text area',
    ],
    "module" => [
        'title' => tr('Modules'),
        'description' => tr('Module appearance settings'),
        'help' => 'Module',
    ],
    "i18n" => [
        'title' => tr('i18n'),
        'description' => tr('Internationalization and localization - multilingual features'),
        'help' => 'i18n',
    ],
    "metatags" => [
        'title' => tr('Meta Tags'),
        'description' => tr('Information to include in the header of each page'),
        'help' => 'Meta Tags',
    ],
    "maps" => [
        'title' => tr('Maps'),
        'description' => tr('Settings and features for maps'),
        'help' => 'Maps',
        'disabled' => false,
    ],
    "performance" => [
        'title' => tr('Performance'),
        'description' => tr('Server performance settings'),
        'help' => 'Performance',
    ],
    "security" => [
        'title' => tr('Security'),
        'description' => tr('Site security settings'),
        'help' => 'Security',
    ],
    "comments" => [
        'title' => tr('Comments'),
        'description' => tr('Comments settings'),
        'help' => 'Comments',
    ],
    "rss" => [
        'title' => tr('Feeds'),
        'help' => 'Feeds User',
        'description' => tr('Outgoing RSS feed setup'),
    ],
    "connect" => [
        'title' => tr('Connect'),
        'help' => 'Connect',
        'description' => tr('Tiki Connect - join in!'),
    ],
    "rating" => [
        'title' => tr('Rating'),
        'help' => 'Rating',
        'description' => tr('Rating settings'),
        'disabled' => $prefs['wiki_simple_ratings'] !== 'y' &&
                        $prefs['wiki_comments_simple_ratings'] !== 'y' &&
                        $prefs['comments_vote'] !== 'y' &&
                        $prefs['rating_advanced'] !== 'y' &&
                        $prefs['trackerfield_rating'] !== 'y' &&
                        $prefs['article_user_rating'] !== 'y' &&
                        $prefs['rating_results_detailed'] !== 'y' &&
                        $prefs['rating_smileys'] !== 'y',
    ],
    "search" => [
        'title' => tr('Search'),
        'description' => tr('Search configuration'),
        'help' => 'Search',
        'disabled' => $prefs['feature_search'] !== 'y' &&
                        $prefs['feature_search_fulltext'] !== 'y',
    ],
    "wiki" => [
        'title' => tr('Wiki'),
        'disabled' => $prefs['feature_wiki'] != 'y',
        'description' => tr('Wiki page settings and features'),
        'help' => 'Wiki Config',
    ],
    "fgal" => [
        'title' => tr('File Galleries'),
        'disabled' => $prefs['feature_file_galleries'] != 'y',
        'description' => tr('Defaults and configuration for file galleries'),
        'help' => 'File Gallery',
    ],
    "blogs" => [
        'title' => tr('Blogs'),
        'disabled' => $prefs['feature_blogs'] != 'y',
        'description' => tr('Settings for blogs'),
        'help' => 'Blog',
    ],
    "gal" => [
        'title' => tr('Image Galleries'),
        'disabled' => $prefs['feature_galleries'] != 'y',
        'description' => tr('Defaults and configuration for image galleries (will be phased out in favour of file galleries)'),
        'help' => 'Image Gallery',
    ],
    "articles" => [
        'title' => tr('Articles'),
        'disabled' => $prefs['feature_articles'] != 'y',
        'description' => tr('Settings and features for articles'),
        'help' => 'Articles',
    ],
    "forums" => [
        'title' => tr('Forums'),
        'disabled' => $prefs['feature_forums'] != 'y',
        'description' => tr('Settings and features for forums'),
        'help' => 'Forums-Admin',
    ],
    "trackers" => [
        'title' => tr('Trackers'),
        'disabled' => $prefs['feature_trackers'] != 'y',
        'description' => tr('Settings and features for trackers'),
        'help' => 'Trackers-Admin',
    ],
    "polls" => [
        'title' => tr('Polls'),
        'disabled' => $prefs['feature_polls'] != 'y',
        'description' => tr('Settings and features for polls'),
        'help' => 'Polls',
    ],
    "calendar" => [
        'title' => tr('Calendar'),
        'disabled' => $prefs['feature_calendar'] != 'y',
        'description' => tr('Settings and features for calendars'),
        'help' => 'Calendar',
    ],
    "category" => [
        'title' => tr('Categories'),
        'disabled' => $prefs['feature_categories'] != 'y',
        'description' => tr('Settings and features for categories'),
        'help' => 'Categories-Admin',
    ],
    "workspace" => [
        'title' => tr('Workspaces'),
        'disabled' => $prefs['workspace_ui'] != 'y' && $prefs['feature_areas'] != 'y',
        'description' => tr('Configure workspace feature'),
        'help' => 'Workspace',
    ],
    "score" => [
        'title' => tr('Score'),
        'disabled' => $prefs['feature_score'] != 'y',
        'description' => tr('Values of actions for users rank score'),
        'help' => 'Score',
    ],
    "freetags" => [
        'title' => tr('Tags'),
        'disabled' => $prefs['feature_freetags'] != 'y',
        'description' => tr('Settings and features for tags'),
        'help' => 'Tags',
    ],
    "faqs" => [
        'title' => tr('FAQs'),
        'disabled' => $prefs['feature_faqs'] != 'y',
        'description' => tr('Settings and features for FAQs'),
        'help' => 'FAQ',
    ],
    "directory" => [
        'title' => tr('Directory'),
        'disabled' => $prefs['feature_directory'] != 'y',
        'description' => tr('Settings and features for directory of links'),
        'help' => 'Directory',
    ],
    "copyright" => [
        'title' => tr('Copyright'),
        'disabled' => $prefs['feature_copyright'] != 'y',
        'description' => tr('Site-wide copyright information'),
        'help' => 'Copyright',
    ],
    "messages" => [
        'title' => tr('Messages'),
        'disabled' => $prefs['feature_messages'] != 'y',
        'description' => tr('Message settings'),
        'help' => 'Inter-User Messages',
    ],
    "webmail" => [
        'title' => tr('Webmail'),
        'disabled' => $prefs['feature_webmail'] != 'y',
        'description' => tr('Webmail settings'),
        'help' => 'Webmail',
        'url' => 'tiki-webmail.php?page=settings'
    ],
    "wysiwyg" => [
        'title' => tr('Wysiwyg'),
        'disabled' => $prefs['feature_wysiwyg'] != 'y',
        'description' => tr('Options for WYSIWYG editor'),
        'help' => 'Wysiwyg',
    ],
    "ads" => [
        'title' => tr('Banners'),
        'disabled' => $prefs['feature_banners'] != 'y',
        'description' => tr('Site advertisements and notices'),
        'help' => 'Banner-Admin',
    ],
    "intertiki" => [
        'title' => tr('InterTiki'),
        'disabled' => $prefs['feature_intertiki'] != 'y',
        'description' => tr('Set up links between Tiki servers'),
        'help' => 'InterTiki',
    ],
    "semantic" => [
        'title' => tr('Semantic Links'),
        'disabled' => $prefs['feature_semantic'] != 'y',
        'description' => tr('Manage semantic wiki links'),
        'help' => 'Semantic Admin',
    ],
    "webservices" => [
        'title' => tr('Webservices'),
        'disabled' => $prefs['feature_webservices'] != 'y',
        'description' => tr('Register and manage web services'),
        'help' => 'WebServices',
    ],
    "sefurl" => [
        'title' => tr('SEF URL'),
        'disabled' => $prefs['feature_sefurl'] != 'y' && $prefs['feature_canonical_url'] != 'y',
        'description' => tr('Search Engine Friendly URLs'),
        'help' => 'Search-Engine-Friendly-URL',
    ],
    "video" => [
        'title' => tr('Video'),
        'disabled' => $prefs['feature_kaltura'] != 'y',
        'description' => tr('Video integration configuration'),
        'help' => 'Video-Admin',
    ],
    "payment" => [
        'title' => tr('Payment'),
        'disabled' => $prefs['payment_feature'] != 'y',
        'description' => tr('Payment settings'),
        'help' => 'Payment',
    ],
    "socialnetworks" => [
        'title' => tr('Social networks'),
        'disabled' => $prefs['feature_socialnetworks'] != 'y',
        'description' => tr('Configure social networks integration'),
        'help' => 'Social Networks',
    ],
    "community" => [
        'title' => tr('Community'),
        'description' => tr('User specific features and settings'),
        'help' => 'Community',
    ],
    "share" => [
        'title' => tr('Share'),
        'disabled' => $prefs['feature_share'] != 'y',
        'description' => tr('Configure share feature'),
        'help' => 'Share',
    ],
    "stats" => [
        'title' => tr('Statistics'),
//		'disabled' => $prefs['feature_stats'] != 'y',
        'description' => tr('Configure statistics reporting for your site usage'),
        'help' => 'Statistics-Admin',
    ],
    "print" => [
        'title' => tr('Print Settings'),
        'description' => tr('Settings and features for print versions and pdf generation'),
        'help' => 'Print Setting-Admin',
    ],
    "packages" => [
        'title' => tr('Packages'),
        'description' => tr('External packages installation and management'),
        'help' => 'Packages',
    ],
    "rtc" => [
        'title' => tr('RTC'),
        'description' => tr('Real-time collaboration tools'),
        'help' => 'RTC',
    ],
];

if (isset($_REQUEST['page'])) {
    $adminPage = $_REQUEST['page'];
    // Check if the associated incude_*.php file exists. If not, check to see if it might exist in the Addons.
    // If it exists, include the associated file
    $utilities = new \Tiki\Package\Extension\Utilities();
    if (file_exists("admin/include_$adminPage.php")) {
        include_once("admin/include_$adminPage.php");
    } elseif ($filepath = $utilities->getExtensionFilePath("admin/include_$adminPage.php")) {
        include_once($filepath);
    }
    $url = 'tiki-admin.php' . '?page=' . $adminPage;

    if (isset($admin_icons[$adminPage])) {
        $admin_icon = $admin_icons[$adminPage];

        $admintitle = $admin_icon['title'];
        $description = isset($admin_icon['description']) ? $admin_icon['description'] : '';
        $helpUrl = isset($admin_icon['help']) ? $admin_icon['help'] : '';
    }
    $helpDescription = tr("Help on %0 Config", $admintitle);

    $smarty->assign('include', $adminPage);
    $smarty->assign('template_not_found', 'n');
    if (substr($adminPage, 0, 3) == 'tp_' && ! file_exists("admin/include_$adminPage.tpl")) {
        $packageAdminTplFile = $utilities->getExtensionFilePath("templates/admin/include_$adminPage.tpl");
        if (! file_exists($packageAdminTplFile)) {
            $smarty->assign('include', 'extension_package_missing_page');
        }
        if (! ExtensionManager::isExtensionEnabled(str_replace("_", "/", substr($adminPage, 3)))) {
            $smarty->assign('include', 'extension_package_inactive');
        }
    } elseif (! file_exists("templates/admin/include_$adminPage.tpl")) {
        // Graceful error management when URL is wrong for admin panel
        $smarty->assign('template_not_found', 'y');
    } else {
        $smarty->assign('template_not_found', 'n');
    }

    //for most admin include page forms, need to redirect as changes to one pref can affect display of others
    //however other forms that perform actions other than changing preferences should not redirect to avoid infinite loops
    //for these add a hidden input named redirect with a value of 0
    if ($access->csrfResult() && (! isset($_POST['redirect']) || $_POST['redirect'] === 1)
        && ! isset($_POST['saveblacklist']) && ! isset($_POST['viewblacklist'])) {
        $access->redirect($_SERVER['REQUEST_URI'], '', 200);
    }
} else {
    $smarty->assign('include', 'list_sections');
    $smarty->assign('admintitle', 'Control Panels');
    $smarty->assign('description', 'Home Page for Administrators');
    $smarty->assign('headtitle', breadcrumb_buildHeadTitle($crumbs));
    $smarty->assign('description', $crumbs[0]->description);
}
$headerlib->add_cssfile('themes/base_files/feature_css/admin.css');
if (isset($admintitle) && isset($description)) {
    $crumbs[] = new Breadcrumb($admintitle, $description, $url, $helpUrl, $helpDescription);
    $smarty->assign_by_ref('admintitle', $admintitle);
    $headtitle = breadcrumb_buildHeadTitle($crumbs);
    $smarty->assign_by_ref('headtitle', $headtitle);
    $smarty->assign_by_ref('helpUrl', $helpUrl);
    $smarty->assign_by_ref('description', $description);
}

// VERSION TRACKING
$forcecheck = ! empty($_GET['forcecheck']);

// Versioning feature has been enabled, so if the time is right, do a live
// check, otherwise display the stored data.
if ($prefs['feature_version_checks'] == 'y' || $forcecheck) {
    $versionUtils = new Tiki_Version_Utils();
    $upgrades = $versionUtils->checkUpdatesForVersion($TWV->version);

    $smarty->assign('upgrade_messages', $upgrades);
}

foreach ($admin_icons as &$admin_icon) {
    $admin_icon = array_merge([ 'disabled' => false, 'description' => ''], $admin_icon);
}

// SSL setup
$haveMySQLSSL = $tikilib->haveMySQLSSL();
$smarty->assign('haveMySQLSSL', $haveMySQLSSL);
if ($haveMySQLSSL) {
    $isSSL = $tikilib->isMySQLConnSSL();
} else {
    $isSSL = false;
}
$smarty->assign('mysqlSSL', $isSSL);

$smarty->assign('admin_icons', $admin_icons);

$show_warning = $adminlib->checkSystemConfigurationFile();
$smarty->assign('show_system_configuration_warning', $show_warning);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('adminpage', $adminPage);
$smarty->assign('mid', 'tiki-admin.tpl');
$smarty->assign('trail', $crumbs);
$smarty->assign('crumb', count($crumbs) - 1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    if (file_exists(__DIR__ . '/vendor/do_not_clean.txt')
        || ! ( // check the existence of critical files denoting a legacy vendor folder
            (file_exists(__DIR__ . '/vendor/zendframework/zend-config/src/Config.php') //ZF2
                || file_exists(__DIR__ . '/vendor/bombayworks/zendframework1/library/Zend/Config.php')) //ZF1
            && (file_exists(__DIR__ . '/vendor/smarty/smarty/libs/Smarty.class.php') //Smarty
                || file_exists(__DIR__ . '/vendor/smarty/smarty/distribution/libs/Smarty.class.php')) //Smarty
            && file_exists(__DIR__ . '/vendor/adodb/adodb/adodb.inc.php') //Adodb
        )) {
        $vendorAutoloadIgnored = false;
    } else {
        $vendorAutoloadIgnored = true;
    }
} else {
    $vendorAutoloadIgnored = false;
}

if (file_exists(__DIR__ . '/vendor/autoload-disabled.php')) {
    $vendorAutoloadDisabled = true;
} else {
    $vendorAutoloadDisabled = false;
}

$smarty->assign('fgal_web_accessible', false);
if ($prefs['fgal_use_dir'] && $prefs['fgal_use_db'] === 'n') {
    $smarty->assign('fgal_web_accessible', $access->isFileWebAccessible($prefs['fgal_use_dir'] . 'index.php'));
}
$smarty->assign('vendor_autoload_ignored', $vendorAutoloadIgnored);
$smarty->assign('vendor_autoload_disabled', $vendorAutoloadDisabled);

include_once('installer/installlib.php');
$installer = Installer::getInstance();
$smarty->assign('db_requires_update', $installer->requiresUpdate());
$smarty->assign('installer_not_locked', $installer->checkInstallerLocked());
$smarty->assign('search_index_outdated', \TikiLib::lib('unifiedsearch')->isOutdated());

$smarty->display('tiki.tpl');
