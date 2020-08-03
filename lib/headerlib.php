<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}


/**
 * Add Javascript and CSS to output
 * Javascript and CSS can be added:
 * - as files (filename including relative path to tiki root)
 * - as scripts (string)
 * - as a url to load from a cdn (Note: use $tikilib->httpScheme() to build the url. It considers reverse proxies and returns correctly 'http' or 'https')
 * Note: there are 2 prefs to add additional cdns. one for http and one for https.
 *
 * To maintain the order of loading Javascript and to allow minifying, the following "ranks" are supported:
 * '10dynamic': loaded first to allow minification of the other ranks. Usually module and plugin descriptions.
 * '20cdn' : loaded after 'dynamic', no minification possible // main libs like jquery from jquery/google cdn (no user cdns)
 * '30dependency': loaded after 'cdn', minification possible  // main libs like jquery, codemirror
 * '40external': loaded after 'dependency', minification possible // custom libs that require main libs
 * '50standard': loaded after 'external', minification possible // standard js that might require main / custom libs
 * '60late': loaded after 'standard', minification possible // page specific js
 *   Note: this rank is activated in tiki-setup.php to separate page specific JS from common JS
 *   So any JS loaded after tiki-setup.php that has no rank 'external' is put into 'late'.
 *   If minification is activated for late, any new combination of late files will be created automatically if needed.
 *   When using user specific CDNs AND minification for late is enabled, any possible minified file must be available via that CDN!
 *
 * The order of files within each section will be maintained. What adds first will be processed first.
 *
 * Note: cdns (google/jquery, not user cdns), files and scripts (strings) will be handled separately.
 *
 * To add JS, the following methods are available.
 *
 *
 * Methods to add JS files:
 * If $skip_minify == true, the file will not be processed for further minification.
 * This could be used to avoid screwing up the JS file in the rare case minification on that particular file does not work.
 * It will however be concatenated to a single JS file.

 * add_jsfile_cdn($url) - add a JS File from a CDN
 * add_jsfile_dependency($filename, $skip_minify) - add a JS File to the section dependency
 * add_jsfile_external($filename, $skip_minify) - add a JS File to the section external
 * add_jsfile($filename, $skip_minify) - add a JS File to the section standard
 * add_jsfile_late($filename, $skip_minify) - add a JS File to the section late

 *
 * These functions allow to add JS as scripts/strings. No minification on them:
 * add_js($script, $rank) - add JS as string
 * add_jq_onready($script, $rank) - add JS as string and add to the onready event
 * add_js_config($script, $rank) - add JS that usually represents a config object
 *
 * @TODO CSS handling
 *
 */
class HeaderLib
{
    public $title;

    /**
     * Array of js files arrays or js urls arrays to load
     * key = rank, value = array of filenames with relative path or urls
     * Some ranks have special meanings: (note ranks are array keys in the same array)
     * @var array()
     */
    public $jsfiles;


    /**
     * Array of js files that are already minified or should not be minified
     * Filled when adding jsfiles and setting the $skip_minify param to true
     * key = filename with relative path
     * @var array
     */
    public $skip_minify;


    /**
     * Array of JS scripts arrays as strings to load
     * key = rank (load order), value = array of scripts.
     * js[$rank][] = $script;
     * @var array
     */
    public $js;


    /**
     * Array of JS Scripts arrays as string that act as config
     * Usually created dynamically
     * js_config[$rank][] = $script;
     * @var array
     */
    public $js_config;


    /**
     * Array of JS Scripts arrays as string that should be called onReady().
     * Key = rank (load order), value = array of scripts.
     * jq_onready[$rank][] = $script;
     * @var array
     */
    public $jq_onready;

    /**
     * Array of JS Scripts arrays as string that should be embedded as modules
     * Key = rank (load order), value = array of scripts.
     * jq_modules[$rank][] = $script;
     * @var array
     */
    public $js_modules;

    public $cssfiles;
    public $css;
    public $rssfeeds;
    public $metatags;
    public $linktags;

    /* If set to true, any js added through add_jsfile() that has not rank 'external' will be put to rank 'late'
     * Only set once in tiki-setup.php to separate wiki page specific js from common js.
     * @var boolean
     */
    public $forceJsRankLate;


    public $jquery_version = '3.2.1';
    public $jqueryui_version = '1.12.1';
    public $jquerymigrate_version = '3.0.0';


    public function __construct()
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->assign('headerlib', $this);

        $this->title = '';
        $this->jsfiles = [];
        $this->skip_minify = [];
        $this->js = [];
        $this->js_config = [];
        $this->jq_onready = [];
        $this->js_modules = [];
        $this->cssfiles = [];
        $this->css = [];
        $this->rssfeeds = [];
        $this->metatags = [];
        $this->rawhtml = '';

        $this->forceJsRankLate = false;
    }


    /**
     * user cdn and feature multi_cdn see r46854
     * @param string $file
     * @param string $rank
     * @return string $file
     */
    public function convert_cdn($file, $rank = null)
    {
        global $prefs, $tikiroot;

        // using this method, also reverse proxy / ssl offloading will continue to work
        $httpScheme = Tikilib::httpScheme();
        $https_mode = ($httpScheme == 'https') ? true : false;

        $cdn_ssl_uri = array_filter(preg_split('/\s+/', $prefs['tiki_cdn_ssl']));
        $cdn_uri = array_filter(preg_split('/\s+/', $prefs['tiki_cdn']));

        if ($https_mode && ! empty($cdn_ssl_uri)) {
            $cdn_pref = &$cdn_ssl_uri;
        } elseif (! empty($cdn_uri)) {
            $cdn_pref = &$cdn_uri;
        }

        // feature multi_cdn see r46854 - quote from commit:
        // filename hash is used to select/assign one CDN URI from the list.
        // It ensure a same file will always point/use the same CDN and ensure proper caching.
        if (! empty($cdn_pref) && 'http' != substr($file, 0, 4) && $rank !== 'dynamic') {
            $index = hexdec(hash("crc32b", $file)) % count($cdn_pref);
            $file = $cdn_pref[$index] . $tikiroot . $file;
        }

        return $file;
    }


    public function set_title($string)
    {
        $this->title = urlencode($string);
    }

    /**
     * Add a js url from this tiki instance to top priority load order.
     * These are usually dynamic created js scripts for configuration, module settings etc.
     * Urls added here will not be further processed (like minified or put into a single file)
     * @param string $url - relative url to this tiki instance
     * @return HeaderLib Current object
     */
    public function add_jsfile_dynamic($url)
    {
        $this->add_jsfile_by_rank($url, '10dynamic', true);

        return $this;
    }


    /**
     * Add a js url to top priority load order. That url must be loaded from an external source.
     * These are usually libraries like jquery that are loaded from a cdn = content delivery network.
      * Urls added here will not be further processed (like minified or put into a single file)
     *
     * N.B. skip_minify needs to be set to true here for when tiki_minify_late_js_files is active
     * and cdn files are added after page setup by plugins etc
     *
     * @param string $url - absolute url including http/https
     * @return HeaderLib Current object
     */
    public function add_jsfile_cdn($url)
    {
        $this->add_jsfile_by_rank($url, '20cdn', true);

        return $this;
    }


    /**
     * Add a js file to top priority load order, right after cdns and dynamics. That file must not be loaded from an external source.
     * Theses are usually libraries like jquery or codemirror, so files where other js file depend on.
     * Depending on prefs, it could be minified and put into a single js file.
     * @param string $file with path relative to tiki dir
     * @param bool $skip_minify true if the file must not be minified, false if it can
     * @return HeaderLib Current object
     */
    public function add_jsfile_dependency($file, $skip_minify = false)
    {
        $this->add_jsfile_by_rank($file, '30dependency', $skip_minify);

        return $this;
    }


    /**
     * Add a js file to load after dependency . That file must not be loaded from an external source.
     * Theses are usually custom libraries like raphael, gaffle etc.
     * Depending on prefs, it could be minified and put into a single js file.
     * @param string $filename with path relative to tiki dir
     * @param bool $skip_minify true if the file must not be minified, false if it can
     * @param mixed $file
     * @return HeaderLib Current object
     */
    public function add_jsfile_external($file, $skip_minify = false)
    {
        $this->add_jsfile_by_rank($file, '40external', $skip_minify);

        return $this;
    }


    /**
     * Adds a js file to load after external. That file must not be loaded from an external source.
     * Depending on prefs, it could be minified and also put into a single js file
     * @param string $file -  path relative to tiki dir
     * @param bool $skip_minify true if the file must not be minified, false if it can
     * @return HeaderLib Current object
     */
    public function add_jsfile($file, $skip_minify = false)
    {
        $this->add_jsfile_by_rank($file, '50standard', $skip_minify);

        return $this;
    }


    /**
     * Add a js file to load after standard . That file must not be loaded from an external source.
     * Use this method to add page specific js files. They will be minified separately.
     * @see $this->forceJsRankLate()
     * Depending on prefs, it could be minified and put into a single js file.
     * @param string $filename with path relative to tiki dir
     * @param bool $skip_minify true if the file must not be minified, false if it can
     * @param mixed $file
     * @return HeaderLib Current object
     */
    public function add_jsfile_late($file, $skip_minify = false)
    {
        $this->add_jsfile_by_rank($file, '60late', $skip_minify);

        return $this;
    }


    /**
     * Add a jf file by rank. Do not use this function directly!
     * Only reason that it is public, is for access from lib/core/tiki/PageCache.php
     * @param string $file
     * @param string $rank
     * @param bool $skip_minify true if the file must not be minified, false if it can
     * @return HeaderLib Current object
     */
    public function add_jsfile_by_rank($file, $rank, $skip_minify = false)
    {
        // if js is added after tiki-setup.php is run, add those js files to 'late'
        // need to check whether this is really needed
        if ($this->forceJsRankLate == true && $rank !== '40external') {
            $rank = '60late';
        }

        if (empty($this->jsfiles[$rank]) or ! in_array($file, $this->jsfiles[$rank])) {
            $this->jsfiles[$rank][] = $file;
            if ($skip_minify) {
                $this->skip_minify[$file] = $skip_minify;
            }
        }

        return $this;
    }

    public function drop_jsfile($file)
    {
        $out = [];
        foreach ($this->jsfiles as $rank => $data) {
            foreach ($data as $f) {
                if ($f != $file) {
                    $out[$rank][] = $f;
                }
            }
        }
        $this->jsfiles = $out;

        return $this;
    }


    /**
     * Add js that works as config. Usually created dynamically.
     * @param string $script
     * @param integer $rank - loadorder optional, default 0
     * @return HeaderLib Current object
     */
    public function add_js_config($script, $rank = 0)
    {
        if (empty($this->js_config[$rank]) or ! in_array($script, $this->js_config[$rank])) {
            $this->js_config[$rank][] = $script;
        }

        return $this;
    }


    /**
     * JS scripts to add as string
     * @param string $script
     * @param integer $rank loadorder optional, default = 0
     * @return HeaderLib Current object
     */
    public function add_js($script, $rank = 0)
    {
        if (empty($this->js[$rank]) or ! in_array($script, $this->js[$rank])) {
            $this->js[$rank][] = $script;
        }

        return $this;
    }

    /**
     * Adds lines or blocks of JQuery JavaScript to $(document).ready handler
     * @param string $script - Script to execute
     * @param number $rank - load order (default=0)
     * @return HeaderLib Current object
     */
    public function add_jq_onready($script, $rank = 0)
    {
        if (empty($this->jq_onready[$rank]) or ! in_array($script, $this->jq_onready[$rank])) {
            $this->jq_onready[$rank][] = $script;
        }

        return $this;
    }

    /**
     * Adds a javascript module
     *
     * @param string $script
     * @param int    $rank
     *
     * @return $this
     */
    public function add_js_module($script, $rank = 0)
    {
        if (empty($this->js_modules[$rank]) or ! in_array($script, $this->js_modules[$rank])) {
            $this->js_modules[$rank][] = $script;
        }

        return $this;
    }

    public function add_cssfile($file, $rank = 0)
    {
        if ((empty($this->cssfiles[$rank]) or ! in_array($file, $this->cssfiles[$rank])) && ! empty($file)) {
            $this->cssfiles[$rank][] = $file;
        }

        return $this;
    }

    public function replace_cssfile($old, $new, $rank)
    {
        foreach ($this->cssfiles[$rank] as $i => $css) {
            if ($css == $old) {
                $this->cssfiles[$rank][$i] = $new;

                break;
            }
        }

        return $this;
    }

    public function drop_cssfile($file)
    {
        $out = [];
        foreach ($this->cssfiles as $rank => $data) {
            foreach ($data as $f) {
                if ($f != $file) {
                    $out[$rank][] = $f;
                }
            }
        }
        $this->cssfiles = $out;

        return $this;
    }

    public function add_css($rules, $rank = 0)
    {
        if (empty($this->css[$rank]) or ! in_array($rules, $this->css[$rank])) {
            $this->css[$rank][] = $rules;
        }

        return $this;
    }

    public function add_rssfeed($href, $title, $rank = 0)
    {
        if (empty($this->rssfeeds[$rank]) or ! in_array($href, array_keys($this->rssfeeds[$rank]))) {
            $this->rssfeeds[$rank][$href] = $title;
        }

        return $this;
    }

    public function add_meta($tag, $value)
    {
        $tag = addslashes($tag);
        $this->metatags[$tag] = $value;

        return $this;
    }

    public function add_rawhtml($tags)
    {
        $this->rawhtml = $tags;

        return $this;
    }

    public function add_link($rel, $href, $sizes = '', $type = '', $color = '')
    {
        $this->linktags[$href]['href'] = $href;
        $this->linktags[$href]['rel'] = $rel;
        if ($sizes) {
            $this->linktags[$href]['sizes'] = $sizes;
        }
        if ($type) {
            $this->linktags[$href]['type'] = $type;
        }
        if ($color) {
            $this->linktags[$href]['color'] = $color;
        }

        return $this;
    }

    public function output_headers()
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_modifier_escape');

        ksort($this->cssfiles);
        ksort($this->css);
        ksort($this->rssfeeds);

        $back = "\n";
        if ($this->title) {
            $back = '<title>' . smarty_modifier_escape($this->title) . "</title>\n\n";
        }

        if ($this->rawhtml) {
            $back .= $this->rawhtml;
        }

        if (count($this->metatags)) {
            foreach ($this->metatags as $n => $m) {
                // check if the meta name starts with OpenGraph protocol prefix and use property instead of name if true
                $nameattrib = preg_match('/^og\:/', $n) ? 'property' : 'name';
                $back .= '<meta ' . $nameattrib . '="' . smarty_modifier_escape($n) . '" content="' . smarty_modifier_escape($m) . "\">\n";
            }
            $back .= "\n";
        }
        if (count($this->linktags)) {
            foreach ($this->linktags as $link) {
                $back .= '<link rel="' . $link['rel'] . '" href="' . $link['href'] . '"';
                if (isset($link['sizes'])) {
                    $back .= ' sizes="' . $link['sizes'] . '"' ;
                }
                if (isset($link['type'])) {
                    $back .= ' type="' . $link['type'] . '"' ;
                }
                if (isset($link['color'])) {
                    $back .= ' color="' . $link['color'] . '"' ;
                }
                $back .= ">\n";
            }
        }


        $back .= $this->output_css_files();

        if (count($this->css)) {
            $back .= "<style type=\"text/css\"><!--\n";
            foreach ($this->css as $x => $css) {
                $back .= "/* css $x */\n";
                foreach ($css as $c) {
                    $back .= "$c\n";
                }
            }
            $back .= "-->\n</style>\n";
        }


        if (count($this->rssfeeds)) {
            foreach ($this->rssfeeds as $x => $rssf) {
                $back .= "<!-- rss $x -->\n";
                foreach ($rssf as $rsstitle => $rssurl) {
                    $back .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"" . smarty_modifier_escape($this->convert_cdn($rsstitle)) . "\" href=\"" . smarty_modifier_escape($rssurl) . "\">\n";
                }
            }
            $back .= "\n";
        }

        return $back;
    }


    /**
     * Force JS Files being added after tiki-setup.php is done to the rank/loadorder 'late' if rank is not 'external'.
     * Used to separate page specific JS Files from the rest.
     * @return HeaderLib Current object
     */
    public function forceJsRankLate()
    {
        $this->forceJsRankLate = true;

        return $this;
    }


    /**
     * Gets included JavaScript files (for AJAX)
     * Used in also lib/wiki/wikilib.php to rebuild the cache if activated
     * @return array $jsFiles effectivly used jsfiles in scripttags considering minification / cdns if activated.
     */
    public function getJsFilesWithScriptTags()
    {
        /*
         // MISCONCEPTION: user cdns are supposed to work as entire tiki cdns - not user based additional url sources

         // check for user defined cdns: prefs: tiki_cdn_ssl, tiki_cdn
         // the current prefs ask for complete urls including the scheme-name (http / https)

         $httpScheme = Tikilib::httpScheme();
         $cdnType  = ($httpScheme == 'http') ? 'tiki_cdn' : 'tiki_cdn_ssl';
         if (isset($prefs[$cdnType])) {

         $customCdns = array_filter(preg_split('/\s+/', $prefs[$cdnType]));
         $rank = 'customCdn';
         foreach ($customCdns as $entry) {
         trim($entry);
         if (!empty($entry)) {
         $output[$rank] .= "<script type=\"text/javascript\" src=\"".smarty_modifier_escape($entry)."\"></script>\n";
         }
         }
         }
         */


        global $prefs;
        if ($prefs['javascript_enabled'] == 'n') {
            return [];
        }

        if (count($this->jsfiles) == 0) {
            return [];
        }

        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_modifier_escape');

        ksort($this->jsfiles);
        $jsfiles = $this->jsfiles;


        // array that holds a sorted list for all JS files including script tags in the correct order
        $output = [];

        // output dynamic and cdn first - they cannot be minified anyway
        $ranks = ['10dynamic', '20cdn'];
        foreach ($ranks as $rank) {
            if (isset($jsfiles[$rank])) {
                foreach ($jsfiles[$rank] as $entry) {
                    $output[] = '<script type="text/javascript" src="' . smarty_modifier_escape($entry) . '"></script>';
                }
            }
        }

        // all other ranks could be minified - minification only happens if activated and if the file was not blocked by $skip_minify

        // check whether we need to minify. minify also includes to put the minified files into one single file
        $minifyActive = isset($prefs['tiki_minify_javascript']) && $prefs['tiki_minify_javascript'] == 'y' ? true : false;

        if (! $minifyActive) {
            $ranks = ['30dependency', '40external', '50standard', '60late'];
            foreach ($ranks as $rank) {
                if (isset($jsfiles[$rank])) {
                    foreach ($jsfiles[$rank] as $entry) {
                        $entry = $this->convert_cdn($entry, $rank);
                        $output[] = '<script type="text/javascript" src="' . smarty_modifier_escape($entry) . '"></script>';
                    }
                }
            }
        } else {
            // minify (each set of ranks will be compressed into one file).

            // late stuff can vary by page. if we would include it in main, then we get multiple big js files.
            // better to accept 2 js request: a big one which rarely changes and small ones that include (page specific) late stuff.
            // at the end we could get rid of this pref though

            $ranks = ['30dependency', '40external', '50standard'];
            $entry = $this->minifyJSFiles($jsfiles, $ranks);
            $output[] .= '<script type="text/javascript" src="' . smarty_modifier_escape($entry) . '"></script>';

            $minifyLateActive = isset($prefs['tiki_minify_late_js_files']) && $prefs['tiki_minify_late_js_files'] == 'y' ? true : false;
            $rank = '60late';
            if ($minifyLateActive) {
                foreach ($jsfiles[$rank] as $index => $file) {
                    if ($this->skip_minify[$file] === true) {
                        $output[] .= '<script type="text/javascript" src="' . smarty_modifier_escape($file) . '"></script>';
                        unset($jsfiles[$rank][$index]);
                    }
                }
                // handling of user defined cdn servers is done inside minifyJSFiles()
                $entry = $this->minifyJSFiles($jsfiles, [$rank]);
                $output[] .= '<script type="text/javascript" src="' . smarty_modifier_escape($entry) . '"></script>';
            } else {
                foreach ($jsfiles[$rank] as $entry) {
                    $output[] = '<script type="text/javascript" src="' . smarty_modifier_escape($entry) . '"></script>';
                }
            }
        }

        return $output;
    }


    /**
     * Minify multiple JS files over multiple ranks into one single JS file.
     * The file is identified by a hash over the given $jsfiles array and automatically created if needed.
     * @param array $allJsfiles array of jsfiles ordered by ranks
     * @param array $ranks simple array of ranks that needs to be processed.
     * @return string $filename - name and relative path of the final js file.
     */
    private function minifyJSFiles($allJsfiles, $ranks)
    {
        global $tikidomainslash, $prefs;

        $cachelib = TikiLib::lib('cache');
        $cacheType = 'js_minify_hash';

        // build hash to identify minified file based on the _requested_ ranks, NOT on the entire jsfiles array
        // $jsfiles contains only those keys defined in $ranks
        $jsfiles = array_intersect_key($allJsfiles, array_flip($ranks));
        $cacheName = md5(serialize($jsfiles));

        // create the minified filename based on the contents of the files, and cache that hash as it's expensive to create
        // browsers will automatically load new js if it has changed after the cache has been cleared, after an upgrade for instance
        $hash = $cachelib->getCached($cacheName, $cacheType);
        if (! $hash) {
            $hash = $this->getFilesContentsHash($jsfiles);
            $cachelib->cacheItem($cacheName, $hash, $cacheType);
        }
        $tempDir = 'temp/public/' . $tikidomainslash;
        $file = $tempDir . "min_main_" . $hash . ".js";
        $cdnFile = $this->convert_cdn($file);

        // Check if we are on a user defined CDN and the file exists (if tiki_cdn_check is enabled).
        // Note: cdn will only be used if the local minified file exists, this ensures that we run the minification at
        // least once locally (covers the case where this instance is also cdn)
        if (file_exists($file) && ($file != $cdnFile)) {
            $cacheType = 'cdn_minify_check';
            if ($prefs['tiki_cdn_check'] === 'y' && ! $cachelib->isCached($cdnFile, $cacheType)) {
                $cdnHeaders = get_headers($cdnFile);
                if (strpos(current($cdnHeaders), '200') !== false) {	// check the file is really there
                    $cachelib->cacheItem($cdnFile, $cdnHeaders, $cacheType);
                }
            } else {
                return $cdnFile;
            }
        }

        if (file_exists($file)) {
            return $file;
        }

        // file does not exist - create it
        $minifiedAll = '';
        // show all relevant messages about the JS files on top - will be prepended to the output
        $topMsg = "/**** start overview of included js files *****/\n";
        foreach ($ranks as $rank) {
            // add list of minified js files to output
            $topMsg .= "\n/* list of files for rank:$rank */\n";
            $topMsg .= '/* ' . print_r($jsfiles[$rank], true) . ' */' . "\n";
            foreach ($jsfiles[$rank] as $f) {
                // important - some scripts like vendor_bundled/vendor/jquery/plugins/async/jquery.async.js do not terminate their last bits with a ';'
                // this is bad practise and that causes issues when putting them all in one file!
                $minified = ';';
                $msg = '';
                // if the name contains not  'min' and that file is not blacklisted for minification assume it is minified
                // preferable is to set $skip_minify proper
                if (! preg_match('/\bmin\./', $f) && $this->skip_minify[$f] !== true) {
                    set_time_limit(600);

                    try {
                        // to optimize processing time for changed js requirements, cache the minified version of each file
                        $hash = md5($f);
                        // filename without extension - makes it easier to identify the compressed files if needed.
                        $prefix = basename($f, '.js');
                        $minifyFile = $tempDir . "min_s_" . $prefix . "_" . $hash . ".js";
                        if (file_exists($minifyFile)) {
                            $temp = file_get_contents($minifyFile);
                        } else {
                            // if the file does not exist MatthiasMullie\Minify takes the input to be the file content
                            // which causes js errors and can break the whole site
                            if (! file_exists($f)) {
                                Feedback::error(tr('JavaScript file "%0" cannot be found so will not be minified.', $f));

                                throw new Exception('File not found');
                            }
                            $minifier = new MatthiasMullie\Minify\JS($f);
                            $temp = $minifier->minify($minifyFile);
                            chmod($minifyFile, 0644);
                        }
                        $msg .= "\n/* rank:$rank - minify:ok. $f */\n";
                        $topMsg .= $msg;
                        $minified .= $msg;
                        $minified .= $temp;
                    } catch (Exception $e) {
                        $content = file_get_contents($f);
                        $error = $e->getMessage();
                        $msg .= "\n/* rank:$rank - minify:error ($error) - adding raw file. $f */\n";
                        $topMsg .= $msg;
                        $minified .= $msg;
                        $minified .= $content;
                    }
                } else {
                    $content = file_get_contents($f);
                    $msg .= "\n/* rank:$rank - minify:disabled - adding raw file. $f */\n";
                    $topMsg .= $msg;
                    $minified .= $msg;
                    $minified .= $content;
                }

                $minifiedAll .= $minified;
            }
        }

        $topMsg .= "\n/**** end overview of included js files *****/\n";
        file_put_contents($file, $topMsg . $minifiedAll);
        chmod($file, 0644);

        return $file;
    }

    /**
     * Calculate a hash based on the contents of files recursively
     *
     * @param array $files   multidimensional array of file paths to minify/hash
     * @param string $hash
     * @return string        hash based on contents of the files
     */
    private function getFilesContentsHash(array $files, & $hash = '')
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $hash .= $this->getFilesContentsHash($file, $hash);
            } else {
                $hash .= md5_file($file);
            }
        }

        return md5($hash);
    }


    /**
     * Output script tags for all javascript files being used.
     * If minification is activated, file based JS (so not from a CDN) will be minified und put into one single file
     * @return string $jsScriptTags
     */
    public function output_js_files()
    {

        // we get one sorted array with script tags
        $js_files = $this->getJsFilesWithScriptTags();
        $output = '';

        foreach ($js_files as $entry) {
            $output .= "\n$entry";
        }

        return $output;
    }



    public function output_js_config($wrap = true)
    {
        global $prefs;

        if ($prefs['javascript_enabled'] == 'n') {
            return;
        }

        $back = null;
        if (count($this->js_config)) {
            ksort($this->js_config);
            $back = "\n<!-- js_config before loading JSfile -->\n";
            $b = "";
            foreach ($this->js_config as $x => $js) {
                $b .= "// js $x \n";
                foreach ($js as $j) {
                    $b .= "$j\n";
                }
            }
            if ($wrap === true) {
                $back .= $this->wrap_js($b);
            } else {
                $back .= $b;
            }
        }

        return $back;
    }

    public function clear_js($clear_js_files = false)
    {
        $this->js = [];
        $this->jq_onready = [];
        $this->js_modules = [];
        if ($clear_js_files) {
            $this->jsfiles = [];
        }

        return $this;
    }

    public function output_js($wrap = true)
    {
        // called in tiki.tpl - JS output at end of file now (pre 5.0)
        global $prefs;

        if ($prefs['javascript_enabled'] == 'n') {
            return;
        }

        ksort($this->js);
        ksort($this->jq_onready);
        ksort($this->js_modules);

        $back = "\n";

        if (count($this->js_modules)) {
            $b = '';
            foreach ($this->js_modules as $x => $js) {
                $b .= "// js $x \n";
                foreach ($js as $j) {
                    $b .= "$j\n";
                }
            }
            if ($wrap === true) {
                $back .= $this->wrap_js($b, true);
            } else {
                $back .= $b;
            }
        }

        if (count($this->js)) {
            $b = '';
            foreach ($this->js as $x => $js) {
                $b .= "// js $x \n";
                foreach ($js as $j) {
                    $b .= "$j\n";
                }
            }
            if ($wrap === true) {
                $back .= $this->wrap_js($b);
            } else {
                $back .= $b;
            }
        }

        if (count($this->jq_onready)) {
            $b = '$(document).ready(function(){' . "\n";
            foreach ($this->jq_onready as $x => $js) {
                $b .= "// jq_onready $x \n";
                foreach ($js as $j) {
                    $b .= "$j\n";
                }
            }
            $b .= "});\n";
            if ($wrap === true) {
                $back .= $this->wrap_js($b);
            } else {
                $back .= $b;
            }
        }

        return $back;
    }

    /**
     * Gets JavaScript and jQuery scripts as an array (for AJAX)
     * @return array[strings]
     */
    public function getJs()
    {
        ksort($this->js);
        ksort($this->jq_onready);
        $out = [];

        if (count($this->js)) {
            foreach ($this->js as $x => $js) {
                foreach ($js as $j) {
                    $out[] = "$j\n";
                }
            }
        }
        if (count($this->jq_onready)) {
            $b = '$(document).ready(function(){' . "\n";
            foreach ($this->jq_onready as $x => $js) {
                $b .= "// jq_onready $x \n";
                foreach ($js as $j) {
                    $b .= "$j\n";
                }
            }
            $b .= "}) /* end on ready */;\n";
            $out[] = $b;
        }

        return $out;
    }


    public function wrap_js($inJs, $module = false)
    {
        if ($module) {
            return "<script type=\"module\" name=\"App\">\n" . $inJs . "\n</script>\n";
        }

        return "<script type=\"text/javascript\">\n<!--//--><![CDATA[//><!--\n" . $inJs . "//--><!]]>\n</script>\n";
    }

    /**
     * Get JavaScript tags from html source - used for AJAX responses and cached pages
     *
     * @param string $html - source to search for JavaScript
     * @param bool $switch_fn_definition - if set converts 'function fName ()' to 'fName = function()' for AJAX
     * @param bool $isFiles - if set true, get external scripts. If set to false, get inline scripts. If true, the external script tags's src attributes are returned as an array.
     *
     * @return array of JavaScript strings
     */
    public function getJsFromHTML($html, $switch_fn_definition = false, $isFiles = false)
    {
        $jsarr = [];
        $js_script = [];

        preg_match_all('/(?:<script.*type=[\'"]?text\/javascript[\'"]?.*>\s*?)(.*)(?:\s*<\/script>)/Umis', $html, $jsarr);
        if ($isFiles == false) {
            if (count($jsarr) > 1 && is_array($jsarr[1]) && count($jsarr[1]) > 0) {
                $js = preg_replace('/\s*?<\!--\/\/--><\!\[CDATA\[\/\/><\!--\s*?/Umis', '', $jsarr[1]);	// strip out CDATA XML wrapper if there
                $js = preg_replace('/\s*?\/\/--><\!\]\]>\s*?/Umis', '', $js);

                if ($switch_fn_definition) {
                    $js = preg_replace('/function (.*)\(/Umis', "$1 = function(", $js);
                }

                $js_script = array_merge($js_script, $js);
            }
        } else {
            foreach ($jsarr[0] as $key => $tag) {
                if (empty($jsarr[1][$key])) { //if there was no content in the script, it is a src file
                    //we load the js as a xml element, then look to see if it has a "src" tag, if it does, we push it to array for end back
                    $js = simplexml_load_string($tag);
                    if (! empty($js['src'])) {
                        array_push($js_script, (string)$js['src']);
                    }
                }
            }
        }
        // this is very probably possible as a single regexp, maybe a preg_replace_callback
        // but it was stopping the CDATA group being returned (and life's too short ;)
        // the one below should work afaik but just doesn't! :(
        // preg_match_all('/<script.*type=[\'"]?text\/javascript[\'"]?.*>(\s*<\!--\/\/--><\!\[CDATA\[\/\/><\!--)?\s*?(.*)(\s*\/\/--><\!\]\]>\s*)?<\/script>/imsU', $html, $js);

        return array_filter($js_script);
    }

    public function removeJsFromHTML($html)
    {
        $html = preg_replace('/(?:<script.*type=[\'"]?text\/javascript[\'"]?.*>\s*?)(.*)(?:\s*<\/script>)/Umis', "", $html);

        return $html;
    }

    public function get_all_css_content()
    {
        $files = $this->collect_css_files();
        $minifier = new MatthiasMullie\Minify\CSS();

        foreach (array_merge($files['screen'], $files['default']) as $file) {
            $minifier->add($file);
        }

        $minified = $minifier->minify();

        return $minified;
    }

    private function output_css_files()
    {
        $files = $this->collect_css_files();

        $back = $this->output_css_files_list($files['default'], '');
        $back .= $this->output_css_files_list($files['screen'], 'screen');
        $back .= $this->output_css_files_list($files['print'], 'print');

        return $back;
    }

    private function output_css_files_list($files, $media = '')
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_modifier_escape');

        $back = '';

        if ($prefs['tiki_minify_css'] == 'y' && ! empty($files)) {
            if ($prefs['tiki_minify_css_single_file'] == 'y') {
                $files = $this->get_minified_css_single($files);
            } else {
                $files = $this->get_minified_css($files);
            }
        }

        foreach ($files as $file) {
            $file = $this->convert_cdn($file);
            $back .= "<link rel=\"stylesheet\" href=\"" . smarty_modifier_escape($file) . "\" type=\"text/css\"";
            if (! empty($media)) {
                $back .= " media=\"" . smarty_modifier_escape($media) . "\"";
            }
            $back .= ">\n";
        }

        return $back;
    }

    private function get_minified_css($files)
    {
        global $tikidomainslash;
        $out = [];
        $publicDirectory = 'temp/public/' . $tikidomainslash;
        foreach ($files as $originalFile) {
            /* This does not use the same cachelib-based caching strategy as get_minified_css_single() since I could not see any improvement.
            I tested on Windows 8 with an HDD and a filesystem-based CacheLib. CacheLibFileSystem::getCached() may be inefficient. The strategy may still improve performance for other setups, such as those using CacheLibMemcache. Chealer 2018-08-31 */
            $fileContentsHash = md5_file($originalFile);
            $minimalFilePath = $publicDirectory . "minified_$fileContentsHash.css";
            if (! file_exists($minimalFilePath)) {
                (new MatthiasMullie\Minify\CSS($originalFile))->minify($minimalFilePath);
                chmod($minimalFilePath, 0644);
            }

            $out[] = $minimalFilePath;
        }

        return $out;
    }

    private function get_minified_css_single($files)
    {
        global $tikidomainslash;
        $cachelib = TikiLib::lib('cache');

        $fileSetHash = md5(serialize($files));
        
        /* The minimal file's name contains a hash based on the file contents, so that browsers will automatically load changes when files are modified.
        However, since that hash is itself costly to create, it is cached server-side. Therefore, client caches will be refreshed when the server-side cache is cleared, after an upgrade for instance. */
        $fileSetContentsHash = $cachelib->getCached($fileSetHash, 'minify_css_contents_by_paths');
        if (! $fileSetContentsHash) {
            $fileSetContentsHash = $this->getFilesContentsHash($files);
            $cachelib->cacheItem($fileSetHash, $fileSetContentsHash, 'minify_css_contents_by_paths');
        }
        $minimalFilePath = 'temp/public/' . $tikidomainslash . "minified_$fileSetContentsHash.css";

        if (! file_exists($minimalFilePath)) {
            $minifier = new MatthiasMullie\Minify\CSS();

            foreach ($files as $originalFile) {
                $minifier->add($originalFile);
            }

            $minifier->minify($minimalFilePath);
            chmod($minimalFilePath, 0644);
        }

        return [ $minimalFilePath ];
    }

    public function minify_css($file)
    {
        $minifier = new MatthiasMullie\Minify\CSS($file);

        return $minifier->minify();
    }

    private function collect_css_files()
    {
        global $tikipath;

        $files = [
            'default' => [],
            'screen' => [],
            'print' => [],
        ];

        $pushFile = function ($section, $file) use (& $files) {
            global $prefs;
            $files[$section][] = $file;

            if ($prefs['feature_bidi'] == 'y') {
                $rtl = str_replace('.css', '', $file) . '-rtl.css';

                if (file_exists($rtl)) {
                    $files[$section][] = $rtl;
                }
            }
        };

        foreach ($this->cssfiles as $x => $cssf) {
            foreach ($cssf as $cf) {
                $cfprint = str_replace('.css', '', $cf) . '-print.css';
                if (! file_exists($tikipath . $cfprint)) {
                    $pushFile('default', $cf);
                } else {
                    $pushFile('screen', $cf);
                    $pushFile('print', $cfprint);
                }
            }
        }

        return $files;
    }

    public function get_css_files()
    {
        $files = $this->collect_css_files();

        return array_merge($files['default'], $files['screen']);
    }

    // TODO compile_custom_scss function here

    public function add_map()
    {
        global $prefs;

        if ($prefs['geo_enabled'] != 'y') {
            return;
        }

        $tikilib = TikiLib::lib('tiki');
        $enabled = $tikilib->get_preference('geo_tilesets', ['openstreetmap'], true);

        $google = array_intersect(['google_street', 'google_physical', 'google_satellite', 'google_hybrid'], $enabled);
        if (count($google) > 0 || $prefs['geo_google_streetview'] == 'y') {
            $args = [
                'v' => '3',
            ];

            if (! empty($prefs['gmap_key'])) {
                $args['key'] = $prefs['gmap_key'];
            }

            $url = $tikilib->httpScheme() . '://maps.googleapis.com/maps/api/js?' . http_build_query($args, '', '&');

            if (TikiLib::lib('access')->is_xml_http_request()) {
                $this->add_js('function loadScript() {
var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = "' . $url . '";
	document.body.appendChild(script);
}

window.onload = loadScript;');
            } else {
                $this->add_jsfile_external($url, true);
            }
        }

        /* Needs additional testing
        $visual = array_intersect(array('visualearth_road', 'visualearth_aerial', 'visualearth_hybrid'), $enabled);
        if (count($visual) > 0) {
            $this->add_jsfile_cdn('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1');
        }
        */

        if ($prefs['geo_openlayers_version'] === 'ol3') {
            $this->add_jsfile_external('vendor_bundled/vendor/openlayers/openlayers/ol.js', true)
                ->add_cssfile('vendor_bundled/vendor/openlayers/openlayers/ol.css')
                ->add_jsfile_external('vendor_bundled/vendor/walkermatt/ol-layerswitcher/dist/ol-layerswitcher.js')
                ->add_cssfile('vendor_bundled/vendor/walkermatt/ol-layerswitcher/src/ol-layerswitcher.css')
                ->add_js(
                    ''
                );
        } else {
            $this->add_jsfile_external('lib/openlayers/OpenLayers.js', true);
        }

        $this->add_js(
            '$(".map-container:not(.done)")
		        .addClass("done")
		        .visible(function() {
		            $(this).createMap();
		    });'
        );

        return $this;
    }


    public function __toString()
    {
        return '';
    }
}
