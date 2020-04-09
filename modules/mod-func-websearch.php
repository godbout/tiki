<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

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
		'name' => tra('websearch'),
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
function module_websearch($mod_reference, $module_params)
{
	$url_page_info_engines = "https://doc.tiki.org/web_search_engines";
	$engines = [
		"Google" => "https://www.google.com/search?q=",
		"Google Images" => "https://www.google.com/images?q=",
		"Bing" => "https://www.bing.com/search?q=",
		"Bing Images" => "https://www.bing.com/images?q=",
		"Yahoo" => "https://search.yahoo.com/search?p=",
		"Yahoo Images" => "https://images.search.yahoo.com/search/images?p=",
		"Searx" => "https://searx.me/?q=",
				"Searx Images" => "https://searx.me/?&category_images=on&q=",
		"Qwant" => "https://www.qwant.com/?q=",
				"Qwant Images" => "https://www.qwant.com/?t=images&q=",
		"Startpage" => "https://www.startpage.com/do/asearch?&query=",
				"Startpage Images" => "https://www.startpage.com/do/asearch?cat=pics&query=",
		"Ask" => "https://www.ask.com/web?q=",
				"Ask Videos" => "https://www.ask.com/youtube?q=",
		"Duckduckgo" => "https://duckduckgo.com/?q=",
				"Duckduckgo Images" => "https://duckduckgo.com/?iax=images&ia=images&q=",
		"Pickanews" => "https://www.pickanews.com/find?q=",
				"SearchEncrypt" => "https://www.searchencrypt.com/search?eq=",
				"Swisscows" => "https://swisscows.ch/?query=",
				"Gigablast" => "http://www.gigablast.com/search?q=",
	];
		ksort($engines);
		$smarty = TikiLib::lib('smarty');
		$smarty->assign('url_page_info_engines', $url_page_info_engines);
	$smarty->assign('engines', $engines);
}
