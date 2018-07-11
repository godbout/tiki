<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: mod-func-websearch.php 66101 2018-07-11 18:03:14Z manasse $

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * @return array
 */
function module_websearch_info()
{
	return [
		'name' => tra('Search on the web'),
		'description' => tra('Displays a simple form to perform a web search with choice on multiple search engines.'),
		'prefs' => [],
		'params' => [
					'title' => [
						'name' => tra('title'),
						'description' => tra('Direction for menu: horiz or vert (default vert)'),
		                'default' => 'false',
						'filter' => 'text',
		                'options' => [
							['text' => '', 'value' => ''],
							['text' => tra('True'), 'value' => 'true'],
							['text' => tra('False'), 'value' => 'false']
						]
					],]
	];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_websearch($mod_reference, $module_params){
  	$url_page_info_engines = "https://doc.tiki.org/web_search_engines";
    $engines = [
        "Google" => "https://www.google.com/search?q=",
        "Google_Images" => "https://www.google.com/images?q=",
        "Bing" => "https://www.bing.com/search?q=",
        "Bing_Images" => "https://www.bing.com/images?q=",
        "Yahoo" => "https://ca.search.yahoo.com/web?q=",
        "Yahoo_Images" => "https://ca.images.search.yahoo.com/images?q=",
        "Searx" => "https://searx.me/?q=",
        "Qwant" => "https://www.qwant.com/?q=",
        "Startpage" => "https://www.startpage.com/",
        "Ask" => "https://www.ask.com/web?q=",
        "Duckduckgo" => "https://duckduckgo.com/?q=",
        "Pickanews" => "https://www.pickanews.com/find?q=",
    ];
		$smarty = TikiLib::lib('smarty');
		$smarty->assign('url_page_info_engines', $url_page_info_engines);
  	$smarty->assign('engines', $engines);
}
