{* $Id: $ *}
<!-- *** header start *** -->
{if $base_url and $dir_level gt 0}<base href="{$base_url}"/>{/if}
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
{if $prefs.metatag_keywords ne ''}<meta name="keywords" content="{$prefs.metatag_keywords}" />
{/if}
{if $prefs.metatag_author ne ''}<meta name="author" content="{$prefs.metatag_author}" />
{/if}
{if $prefs.metatag_description ne ''}<meta name="description" content="{$prefs.metatag_description}" />
{/if}
{if $prefs.metatag_geoposition ne ''}<meta name="geo.position" content="{$prefs.metatag_geoposition}" />
{/if}
{if $prefs.metatag_georegion ne ''}<meta name="geo.region" content="{$prefs.metatag_georegion}" />
{/if}
{if $prefs.metatag_geoplacename ne ''}<meta name="geo.placename" content="{$prefs.metatag_geoplacename}" />
{/if}
{if $prefs.metatag_robots ne ''}<meta name="robots" content="{$prefs.metatag_robots}" />
{/if}
{if $prefs.metatag_revisitafter ne ''}<meta name="revisit-after" content="{$prefs.metatag_revisitafter}" />
{/if}

{* --- tikiwiki block --- *}
<script type="text/javascript" src="lib/tiki-js.js"></script>
{include file="bidi.tpl"}
<title>
{if isset($trail)}{breadcrumbs type="fulltrail" loc="head" crumbs=$trail}
{else}
{$prefs.siteTitle}
{if !empty($headtitle)} : {$headtitle}
{elseif !empty($page)} : {if $beingStaged eq 'y' and $prefs.wikiapproval_hideprefix == 'y'}{$approvedPageName|escape}{else}{$page|escape}{/if} {* add $description|escape if you want to put the description + update breadcrumb_build replace return $crumbs->title; with return empty($crumbs->description)? $crumbs->title: $crumbs->description; *}
{elseif !empty($arttitle)} : {$arttitle}
{elseif !empty($title)} : {$title}
{elseif !empty($thread_info.title)} : {$thread_info.title}
{elseif !empty($post_info.title)} : {$post_info.title}
{elseif !empty($forum_info.name)} : {$forum_info.name}
{elseif !empty($categ_info.name)} : {$categ_info.name}
{elseif !empty($userinfo.login)} : {$userinfo.login}
{elseif !empty($tracker_item_main_value)} : {$tracker_item_main_value}
{elseif !empty($tracker_info.name)} : {$tracker_info.name}
{/if}
{/if}
</title>

{if $prefs.site_favicon}<link rel="icon" href="{$prefs.site_favicon}" />{/if}
<!--[if lt IE 7]> <link rel="StyleSheet" href="css/ie6.css" type="text/css" /> <![endif]-->

{* --- phplayers block --- *}
{if $prefs.feature_phplayers eq 'y'}
<link rel="StyleSheet" href="lib/phplayers/layerstreemenu.css" type="text/css"></link>
<link rel="StyleSheet" href="lib/phplayers/layerstreemenu-hidden.css" type="text/css" />
<script type="text/javascript"><!--
var numl;var toBeHidden;
{php} include_once ("lib/phplayers/libjs/layersmenu-browser_detection.js"); global $LayersMenu, $TreeMenu, $PHPTreeMenu, $PlainMenu;{/php}
// --></script>
<script type="text/javascript" src="lib/phplayers/libjs/layersmenu-library.js"></script>
{* lets try this *}
<script type="text/javascript" src="lib/phplayers/libjs/layersmenu.js"></script>
{* will it work now ? (luci) *}
<script type="text/javascript" src="lib/phplayers/libjs/layerstreemenu-cookies.js"></script>
{/if}

{* --- Firefox RSS icons --- *}
{if $prefs.feature_wiki eq 'y' and $prefs.rss_wiki eq 'y' and $tiki_p_view eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Wiki{/tr}" href="tiki-wiki_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_blogs eq 'y' and $prefs.rss_blogs eq 'y' and $tiki_p_read_blog eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Blogs{/tr}" href="tiki-blogs_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_articles eq 'y' and $prefs.rss_articles eq 'y' and $tiki_p_read_article eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Articles{/tr}" href="tiki-articles_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_galleries eq 'y' and $prefs.rss_image_galleries eq 'y' and $tiki_p_view_image_gallery eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Image Galleries{/tr}" href="tiki-image_galleries_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_file_galleries eq 'y' and $prefs.rss_file_galleries eq 'y' and $tiki_p_view_file_gallery eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS File Galleries{/tr}" href="tiki-file_galleries_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_forums eq 'y' and $prefs.rss_forums eq 'y' and $tiki_p_forum_read eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Forums{/tr}" href="tiki-forums_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_maps eq 'y' and $prefs.rss_mapfiles eq 'y' and $tiki_p_map_view eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Maps{/tr}" href="tiki-map_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}
{if $prefs.feature_directory eq 'y' and $prefs.rss_directories eq 'y' and $tiki_p_view_directory eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Directories{/tr}" href="tiki-directories_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}

{if $prefs.feature_calendar eq 'y' and $prefs.rss_calendar eq 'y' and $tiki_p_view_calendar eq 'y'}
<link rel="alternate" type="application/rss+xml" title="{tr}RSS Calendars{/tr}" href="tiki-calendars_rss.php?ver={$prefs.rssfeed_default_version}" />
{/if}

{if $prefs.feature_mootools eq "y"}
<script type="text/javascript" src="lib/mootools/mootools.js"></script>
{if $mootools_windoo eq "y"}
<script type="text/javascript" src="lib/mootools/extensions/windoo/windoo.js"></script>
{/if}
{if $mootab eq "y"}
<script src="lib/mootools/extensions/tabs/SimpleTabs.js" type="text/javascript" ></script> 
{/if}
{/if}

{if $prefs.feature_swffix eq "y"}
<script type="text/javascript" src="lib/swffix/swffix.js"></script>
{/if}

{if $headerlib}{$headerlib->output_headers()}{/if}
{if ($mid eq 'tiki-editpage.tpl')}
<script type="text/javascript">
{literal}
  var needToConfirm = true;
  
  window.onbeforeunload = confirmExit;
  function confirmExit()
  {
    if (needToConfirm)
		{/literal}return "{tr interactive='n'}You are about to leave this page. If you have made any changes without Saving, your changes will be lost.  Are you sure you want to exit this page?{/tr}";{literal}
  }
{/literal}
</script>
{/if}

{if $prefs.feature_shadowbox eq 'y'}
<!-- Includes for Shadowbox script -->
	<link rel="stylesheet" type="text/css" href="lib/shadowbox/build/css/shadowbox.css" />

{if $prefs.feature_mootools eq "y"}
	<script type="text/javascript" src="lib/shadowbox/build/js/adapter/shadowbox-mootools.js" charset="utf-8"></script>
{else}
	<script type="text/javascript" src="lib/shadowbox/build/js/adapter/shadowbox-jquery.js" charset="utf-8"></script>
{/if}

	<script type="text/javascript" src="lib/shadowbox/build/js/shadowbox.js" charset="utf-8"></script>

	<script type="text/javascript">

{if $prefs.feature_mootools eq "y"}
	{literal}
		window.addEvent('domready', function() {
	{/literal}
{else}
	{literal}
		$(document).ready(function() {
	{/literal}
{/if}
{literal}
			var options = {
				ext: {
					img:        ['png', 'jpg', 'jpeg', 'gif', 'bmp'],
					qt:         ['dv', 'mov', 'moov', 'movie', 'mp4'],
					wmp:        ['asf', 'wm', 'wmv'],
					qtwmp:      ['avi', 'mpg', 'mpeg'],
					iframe: ['asp', 'aspx', 'cgi', 'cfm', 'doc', 'htm', 'html', 'pdf', 'pl', 'php', 'php3', 'php4', 'php5', 'phtml', 'rb', 'rhtml', 'shtml', 'txt', 'vbs', 'xls']
				},
				handleUnsupported: 'remove',
				loadingImage: 'lib/shadowbox/images/loading.gif',
				overlayBgImage: 'lib/shadowbox/images/overlay-85.png',
				resizeLgImages: true,
				text: {
{/literal}
					cancel:   '{tr}Cancel{/tr}',
					loading:  '{tr}Loading{/tr}',
					close:    '{tr}<span class="shortcut">C</span>lose{/tr}',
					next:     '{tr}<span class="shortcut">N</span>ext{/tr}',
					prev:     '{tr}<span class="shortcut">P</span>revious{/tr}'
{literal}
				},
				keysClose:          ['c', 27], // c OR esc
				keysNext:           ['n', 39], // n OR arrow right
				keysPrev:           ['p', 37]  // p OR arrow left
			};

			Shadowbox.init(options);
		});
	</script>
{/literal}
{/if}
<!-- *** header end *** -->