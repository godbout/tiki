<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// To include a link in your tpl do
//<a href="tiki-short_url.php?page={$page}">{tr}Get a short URL{/tr}</a>

use Tiki\CustomRoute\Item;
use Tiki\CustomRoute\CustomRoute;

require_once('tiki-setup.php');

// Check if feature is enabled
$access->check_feature('sefurl_short_url');
//@todo check if user has access to feature?


if (! empty($_REQUEST['page'])) {
	$objectType = 'wiki page';
	$objectId = TikiLib::lib('tiki')->get_page_id_from_name($_REQUEST['page']);
} else {
	$objectType = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
	$objectId = empty($_REQUEST['objectId']) ? '' : $_REQUEST['objectId'];
}

if (empty($objectType) || empty($objectId)) {
	Feedback::error(tr('Unable to generate a short url for the requested resource.'), 'session');
}

// Check if there is already a short url generated for the same page/object
$hash = CustomRoute::getShortUrl($objectType, $objectId);

if (empty($hash)) {
	//Possible url hash chars
	$hash = CustomRoute::generateShortUrlHash();

	$objLib = TikiLib::lib('object');
	$objectInfoId = $objectType == 'wiki page' ? $_REQUEST['page'] : $objectId;
	$objectInfo = $objLib->get_info($objectType, $objectInfoId);
	$objectTitle = empty($objectInfo['title']) ? '' : $objectInfo['title'];

	$description = sprintf("%s: '%s' short url", ucfirst($objectType), substr($objectTitle, 0, 25));

	$route = new Item(Item::TYPE_OBJECT, $hash, ['type' => $objectType, 'object' => $objectId], $description, 1, 1);
	$route->save();
}

global $prefs, $base_url;
//$url = $url_scheme . '://' . $url_host . (($url_port != '') ? ":$url_port" : '') : $base_url . $url;

$shortUrl = ! empty($prefs['sefurl_short_url_base_url']) ? $prefs['sefurl_short_url_base_url'] : $base_url;
$shortUrl = rtrim($shortUrl, '/') . '/' . $hash;

// Generate a custom path (from)
Feedback::success(tr('Short URL:') . " <a href='{$shortUrl}'>{$shortUrl}</a>", 'session');

//Redirect back to the page
require_once('tiki-sefurl.php');
$smarty = TikiLib::lib('smarty');
$smarty->loadPlugin('smarty_modifier_sefurl');

$objectId = ($objectType == 'wiki page') ? $_REQUEST['page'] : $objectId;

$url = smarty_modifier_sefurl($objectId, $objectType);

$access = TikiLib::lib('access');
$access->redirect($url);
