<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'mytiki';
require_once('tiki-setup.php');
require_once('lib/socialnetworkslib.php');
$access->check_feature('feature_socialnetworks');
$access->check_permission('tiki_p_socialnetworks', tra('Social networks'));

$auto_query_args = [];

if (isset($_REQUEST['request_twitter'])) {
	$access->check_user($user);
	if (! isset($_REQUEST['oauth_verifier'])) {
		// user asked to give us access to twitter
		$socialnetworkslib->getTwitterRequestToken();
	} else {
		if (isset($_SESSION['TWITTER_REQUEST_TOKEN'])) {
			// this is the callback from twitter
			// no anti-CSRF check here since token provided by Twitter in this request is verified
			$socialnetworkslib->getTwitterAccessToken($user);
		} // otherwise it is just a reload of this page
	}
}
if (isset($_REQUEST['remove_twitter'])) {
	$access->check_user($user);
	// remove user token from tiki
	$tikilib->set_user_preference($user, 'twitter_token', '');
	$smarty->assign('show_removal', true);
}
if ($user) {
	$token = $tikilib->get_user_preference($user, 'twitter_token', '');
	$smarty->assign('twitter', ($token != ''));
}
if ($user) {
	$token = $tikilib->get_user_preference($user, 'linkedin_token', '');
	$smarty->assign('linkedIn', ($token != ''));
}
if (isset($_REQUEST['request_facebook'])) {
	if ($prefs["socialnetworks_facebook_login"] != 'y') {
		$access->check_user($user);
	}
	if (! isset($_REQUEST['code'])) {
		// user asked to give us access to Facebook
		// no anti-CSRF here since this redirects to facebook site
		$socialnetworkslib->getFacebookRequestToken();
	} else {
		// this is the callback from Facebook
		// no anti-CSRF check here since token provided by Facebook in this request is verified with Facebook
		$socialnetworkslib->facebookLoginPre();
	}
}
if (isset($_REQUEST['remove_facebook'])) {
	$access->check_user($user);
	// remove user token from tiki
	$tikilib->set_user_preference($user, 'facebook_token', '');
	$tikilib->set_user_preference($user, 'facebook_id', '');
	$smarty->assign('show_removal', true);
}

if (isset($_REQUEST['accounts'])) {
	$access->check_user($user);
	$tikilib->set_user_preference($user, 'bitly_login', $_REQUEST['bitly_login']);
	$smarty->assign('bitly_login', $_REQUEST['bitly_login']);
	$tikilib->set_user_preference($user, 'bitly_key', $_REQUEST['bitly_key']);
	$smarty->assign('bitly_key', $_REQUEST['bitly_key']);
} else {
	$smarty->assign('bitly_login', $tikilib->get_user_preference($user, 'bitly_login', ''));
	$smarty->assign('bitly_key', $tikilib->get_user_preference($user, 'bitly_key', ''));
}
if ($user) {
	$token = $tikilib->get_user_preference($user, 'facebook_token', '');
	$smarty->assign('facebook', ($token != ''));
}
$smarty->assign('twitterRegistered', $socialnetworkslib->twitterRegistered());
$smarty->assign('facebookRegistered', $socialnetworkslib->facebookRegistered());
$smarty->assign('linkedInRegistered', $socialnetworkslib->linkedInRegistered());

$smarty->assign('mid', 'tiki-socialnetworks.tpl');
$smarty->display("tiki.tpl");
