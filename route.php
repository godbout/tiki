<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Adding support for an other web server? Check the end of the file

/**
 * Routing method, receives the path portion of the URL relative to tiki root.
 * http://example.com/tiki/hello-world?foo-bar
 * $path is expectedto be hello-world
 * @param mixed $path
 */
function tiki_route($path)
{
    /*
    // If you are converting to Tiki and want to preserve some URLs, map the urls and remove the comment block
    $urlMapping = array(
        'wiki/old-page-name' => 'PageName',
        'corporate/Privacy+Policy.pdf' => 'dl123',
    );

    if (isset($urlMapping[$path])) {
        $path = $urlMapping[$path];
    }
    */


    $simple = [
        'articles' => 'tiki-view_articles.php',
        'blogs' => 'tiki-list_blogs.php',
        'calendar' => 'tiki-calendar.php',
        'categories' => 'tiki-browse_categories.php',
        'chat' => 'tiki-chat.php',
        'contact' => 'tiki-contact.php',
        'directories' => 'tiki-directory_browse.php',
        'faqs' => 'tiki-list_faqs.php',
        'filelist' => 'tiki-list_file_gallery.php',
        'forums' => 'tiki-forums.php',
        'galleries' => 'tiki-galleries.php',
        'login' => 'tiki-login_scr.php',
        'logout' => 'tiki-logout.php',
        'me' => 'tiki-user_information.php',
        'my' => 'tiki-my_tiki.php',
        'newsletters' => 'tiki-newsletters.php',
        'quizzes' => 'tiki-list_quizzes.php',
        'register' => 'tiki-register.php',
        'sheets' => 'tiki-sheets.php',
        'statistics' => 'tiki-stats.php',
        'surveys' => 'tiki-list_surveys.php',
        'trackers' => 'tiki-list_trackers.php',
        'users' => 'tiki-list_users.php',
        'tiki-check' => 'tiki-check.php',
    ];

    foreach ($simple as $key => $file) {
        tiki_route_attempt("|^$key$|", $file);
    }

    /*
        Valid:

        art123
        article123
        art123-XYZ
        article123-XYZ
    */
    tiki_route_attempt('/^(art|article)(\d+)(\-.*)?$/', 'tiki-read_article.php', tiki_route_single(2, 'articleId'));

    tiki_route_attempt('|^blog(\d+)(\-.*)?$|', 'tiki-view_blog.php', tiki_route_single(1, 'blogId'));
    tiki_route_attempt('|^blogpost(\d+)(\-.*)?$|', 'tiki-view_blog_post.php', tiki_route_single(1, 'postId'));
    tiki_route_attempt('|^cat(\d+)(\-.*)?$|', 'tiki-browse_categories.php', tiki_route_single(1, 'parentId'));
    tiki_route_attempt_prefix('browseimage', 'tiki-browse_image.php', 'imageId');
    tiki_route_attempt('/^event(\d+)(\-.*)?$/', 'tiki-calendar_edit_item.php', tiki_route_single(1, 'viewcalitemId'));

    tiki_route_attempt(
        '|^cal(\d[\d,]*)$|',
        'tiki-calendar.php',
        function ($parts) {
            $ids = explode(',', $parts[1]);
            $ids = array_filter($ids);

            return ['calIds' => $ids];
        }
    );

    tiki_route_attempt_prefix('directory', 'tiki-directory_browse.php', 'parent');
    tiki_route_attempt_prefix('dirlink', 'tiki-directory_redirect.php', 'siteId');

    tiki_route_attempt_prefix('faq', 'tiki-view_faq.php', 'faqId');
    tiki_route_attempt_prefix('file', 'tiki-list_file_gallery.php', 'galleryId');
    tiki_route_attempt_prefix('forum', 'tiki-view_forum.php', 'forumId');
    tiki_route_attempt('|^forumthread(\d+)(\-.*)?$|', 'tiki-view_forum_thread.php', tiki_route_single(1, 'comments_parentId'));
    tiki_route_attempt_prefix('calevent', 'tiki-calendar_edit_item.php', 'viewcalitemId');
    tiki_route_attempt_prefix('gallery', 'tiki-browse_gallery.php', 'galleryId');
    tiki_route_attempt_prefix('img', 'show_image.php', 'id');
    tiki_route_attempt_prefix('image', 'show_image.php', 'id');
    tiki_route_attempt(
        '|^imagescale(\d+)/(\d+)$|',
        'show_image.php',
        function ($parts) {
            return [
                'id' => $parts[1],
                'scalesize' => $parts[2],
            ];
        }
    );
    tiki_route_attempt('|^item(\d+)(\-.*)?$|', 'tiki-view_tracker_item.php', tiki_route_single(1, 'itemId'));
    tiki_route_attempt_prefix('int', 'tiki-integrator.php', 'repID');
    tiki_route_attempt_prefix('newsletter', 'tiki-newsletters.php', 'nlId', ['info' => '1']);
    tiki_route_attempt_prefix('nl', 'tiki-newsletters.php', 'nlId', ['info' => '1']);
    tiki_route_attempt_prefix('poll', 'tiki-poll_form.php', 'pollId');
    tiki_route_attempt_prefix('quiz', 'tiki-take_quiz.php', 'quizId');
    tiki_route_attempt_prefix('survey', 'tiki-take_survey.php', 'surveyId');
    tiki_route_attempt_prefix('tracker', 'tiki-view_tracker.php', 'trackerId');
    tiki_route_attempt_prefix('sheet', 'tiki-view_sheets.php', 'sheetId');
    tiki_route_attempt_prefix('user', 'tiki-user_information.php', 'userId');
    tiki_route_attempt('|^userinfo$|', 'tiki-view_tracker_item.php', function () {
        return ['view' => ' user'];
    });

    tiki_route_attempt_prefix('dl', 'tiki-download_file.php', 'fileId');
    tiki_route_attempt_prefix('thumbnail', 'tiki-download_file.php', 'fileId', ['thumbnail' => '']);
    tiki_route_attempt_prefix('display', 'tiki-download_file.php', 'fileId', ['display' => '']);
    tiki_route_attempt_prefix('preview', 'tiki-download_file.php', 'fileId', ['preview' => '']);

    tiki_route_attempt(
        '/^(wiki|page)\-(.+)$/',
        'tiki-index.php',
        function ($parts) {
            return ['page' => $parts[2]];
        }
    );
    tiki_route_attempt(
        '/^show:(.+)$/',
        'tiki-slideshow.php',
        function ($parts) {
            return ['page' => urldecode($parts[1])];
        }
    );

    tiki_route_attempt(
        '/([^\/]+).xml$/',
        'tiki-sitemap.php',
        function ($parts) {
            return ['file' => $parts[0]];
        }
    );

    tiki_route_attempt(
        '|^tiki\-(\w+)\-(\w+)$|',
        'tiki-ajax_services.php',
        function ($parts) {
            if ($parts[2] == 'x') {
                return [
                    'controller' => $parts[1],
                ];
            }

            return [
                    'controller' => $parts[1],
                    'action' => $parts[2],
                ];
        }
    );

    if (false !== $dot = strrpos($path, '.')) {
        // Prevent things that look like filenames from being considered for wiki page names
        $extension = substr($path, $dot + 1);
        if (in_array($extension, ['css', 'gif', 'jpg', 'png', 'php', 'html', 'js', 'htm', 'shtml', 'cgi', 'sql', 'phtml', 'txt', 'ihtml'])) {
            return;
        }
    }

    tiki_route_attempt_custom_route_redirect();

    tiki_route_attempt(
        '|.*|',
        'tiki-index.php',
        function ($parts) {
            return ['page' => urldecode($parts[0])];
        }
    );
}

function tiki_route_attempt($pattern, $file, $callback = null, $extra = [])
{
    global $path, $inclusion, $base, $full;

    if ($inclusion) {
        return;
    }

    if (preg_match($pattern, $path, $parts)) {
        $inclusion = $file;

        $full = $base . $file;

        if ($callback && is_callable($callback)) {
            $_GET = array_merge($_GET, $callback($parts), $extra);
        }
    }
}

function tiki_route_attempt_prefix($prefix, $file, $key, $extra = [])
{
    tiki_route_attempt("|^$prefix(\d+)$|", $file, tiki_route_single(1, $key), $extra);
}

function tiki_route_single($index, $name)
{
    return function ($parts) use ($index, $name) {
        return [$name => $parts[$index]];
    };
}

/**
 * Attempts to route based on custom routes, defined by the admin.
 * If a suitable rule is found an HTTP redirect will be issued and the user sent to the right page/URL.
 * Custom routes rules are only processed if none of the built in rules were successful
 * This function also loads the minimal amount of framework to be able to query the db.
 */
function tiki_route_attempt_custom_route_redirect()
{
    global $path, $inclusion, $prefs, $tikiroot, $tikipath, $base, $full;

    if ($inclusion || empty($path)) {
        return;
    }

    // bootstrap the essentials to be able to use tiki db and libraries
    // in a sane state that allows tiki to be fallback to the default entrypoints
    // if a custom route is not match
    require_once __DIR__ . '/tiki-filter-base.php'; // sets $tikiroot, $tikipath
    $GLOBALS['tikiroot'] = $tikiroot;
    $GLOBALS['tikipath'] = $tikipath;

    require_once __DIR__ . '/db/tiki-db.php';

    if (! TikiDb::get()) {
        exit;
    }

    require_once __DIR__ . '/lib/tikilib.php';

    $tikilib = new TikiLib;
    $GLOBALS['tikilib'] = $tikilib;

    $prefereces = [
        'feature_sefurl' => 'n',
        'feature_sefurl_routes' => 'n',
    ];

    $tikilib->get_preferences($prefereces, true, true);
    // ~ bootstrap

    if ($prefs['feature_sefurl_routes'] === 'y') {
        $route = \Tiki\CustomRoute\CustomRoute::matchRoute($path);
        if ($route) {
            $routeParameters = \Tiki\CustomRoute\CustomRoute::getInPlaceRoutingParameters($route, $path);
            if ($routeParameters !== false) {
                $inclusion = $routeParameters['file'];
                $full = $base . $inclusion;
                $_GET = array_merge($_GET, $routeParameters['get_param']);
            } else {
                require_once __DIR__ . '/tiki-setup.php';
                // Reload necessary preferences for SEF url
                $tikilib->get_preferences($prefereces, true, true);
                \Tiki\CustomRoute\CustomRoute::executeRoute($route, $path);
            }
        }
    }
}

$base = null;
$path = null;
$inclusion = null;

// This portion may need to vary depending on the webserver/configuration

switch (PHP_SAPI) {
    case 'apache2handler':
    default:
        // Fix $_SERVER['REQUEST_URI', which is ASCII encoded on IIS
        //	Convert the SERVER variable itself, to fix $_SERVER['REQUEST_URI'] access everywhere
        //	route.php comes first in the processing.  Avoid dependencies.
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
            if (mb_detect_encoding($_SERVER['REQUEST_URI'], 'UTF-8', true) == false) {
                $_SERVER['REQUEST_URI'] = utf8_encode($_SERVER['REQUEST_URI']);
            }
        }

        if (isset($_SERVER['SCRIPT_URL'])) {
            $full = $_SERVER['SCRIPT_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $full = $_SERVER['REQUEST_URI'];
            if (strpos($full, '?') !== false) {
                $full = substr($full, 0, strpos($full, '?'));
            }
        } elseif (isset($_SERVER['REDIRECT_URL'])) {
            $full = $_SERVER['REDIRECT_URL'];
        } elseif (isset($_SERVER['UNENCODED_URL'])) {	// For IIS
            $full = $_SERVER['UNENCODED_URL'];
        } else {
            break;
        }

        $file = basename(__FILE__);
        $base = substr($_SERVER['PHP_SELF'], 0, -strlen($file));
        $path = substr($full, strlen($base));

        break;
}

// Global check

if (is_null($base) || is_null($path)) {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/plain; charset=utf-8');

    echo "Request could not be understood. Verify routing file.";
    exit;
}

tiki_route($path);

if ($inclusion) {
    $_SERVER['PHP_SELF'] = $base . $inclusion;
    $_SERVER['SCRIPT_NAME'] = $base . basename($inclusion);
    include __DIR__ . '/' . $inclusion;
} else {
    error_log("No route found - full:$full query:{$_SERVER['QUERY_STRING']}");

    // Route to the "no-route" URL, if found
    require_once('lib/init/initlib.php');
    $local_php = TikiInit::getCredentialsFile();
    if (file_exists($local_php)) {
        include($local_php);
    }
    if (empty($noroute_url)) {
        // Fail
        header('HTTP/1.0 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');

        echo "No route found. Please see http://dev.tiki.org/URL+Rewriting+Revamp";
    } else {
        header('Location: ' . $noroute_url);
    }
    exit;
}
