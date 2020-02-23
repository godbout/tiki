<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\CustomRoute\CustomRoute;

require_once('tiki-setup.php');

// Check if feature is enabled
$access->check_feature(['feature_sefurl_routes', 'sefurl_short_url']);

if (empty($_REQUEST['exturl']) && (empty($_REQUEST['url']) || $tikilib->getMatchBaseUrlSchema($_REQUEST['url']) === null)) {
	if ($_REQUEST['module'] == 'y') {
		echo json_encode(['error' => true, 'message' => tr('URL provided is empty or unsupported')]);
		return;
	}

	Feedback::error(tr('Unable to generate a short url for the requested resource.'));
	// Redirect to homepage
	$access->redirect();
	return;
}

$extUrl = $_REQUEST['exturl'];
$url = $_REQUEST['url'];
$description = tr("'%0' short url", substr($_REQUEST['title'], 0, 75));
if (! empty($extUrl)) {
	$route = CustomRoute::getShortUrlRoute($extUrl, $description);
} else {
	$route = CustomRoute::getShortUrlRoute($url, $description);
}
$shortUrl = $route->getShortUrlLink();

if ($_REQUEST['module'] == 'y') {
	echo  json_encode(['url' => $shortUrl]);
	return;
}

Feedback::success(tr('Short URL for this page:') . " <a class='alert-link' href='{$shortUrl}'>{$shortUrl}</a>");
$access->redirect($url);
