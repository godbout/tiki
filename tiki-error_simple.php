<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once 'tiki-filter-base.php';
$filter = \TikiFilter::get('xss');

if (! empty($_REQUEST['error'])) {
	$error = $filter->filter(substr($_REQUEST["error"], 0, 256));
} else {
	$error = '';
}
if (! empty($_REQUEST['title'])) {
	$title = $filter->filter($_REQUEST['title']);
} else {
	$title = '';
}

if (! empty($_REQUEST['login_error'])) {
	$login_error = $filter->filter($_REQUEST["login_error"]);
} else {
	$login_error = '';
}
$style_alert = ''.$login_error !='' ? 'alert alert-danger' : '';
$style_alert_btn = ''.$login_error !='' ? '' : 'display:none';
session_start();
$ticket = strtr(str_replace('=', '', base64_encode(\phpseclib\Crypt\Random::string(32))), '+/', '-_');
$_SESSION['tickets'][$ticket] = time();

$login = '<form class="form-detail" id="myform" action="tiki-login.php?page=tikiIndex" method="post">
			<div class="form-row">
				<label>User</label>
				<input type="text" name="user" id="your_email" class="input-text" required>
			</div>
			<div class="form-row form-row-1">
				<label for="password">Password</label>
				<input type="password" name="pass" id="password" class="input-text" required>
			</div>
			<div class="'.$style_alert.'" role="alert">'.$login_error.'
				<button type="button" class="close" style="'.$style_alert_btn.'" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="form-row-last">
				<input type="hidden" class="ticket" name="ticket" value="' . $ticket . '" />
				<input type="hidden" name="confirmForm" value="y" />
				<input type="submit" name="login" value="Log in" class="register">
			</div>
</form>';

if (file_exists('themes/base_files/other/site_closed_local.html')) {
	$html = file_get_contents('themes/base_files/other/site_closed_local.html');
} else {
	$html = file_get_contents('themes/base_files/other/site_closed.html');
}

$html = str_replace('{error}', $error, $html);
$html = str_replace('{title}', $title, $html);
$html = str_replace('{login}', $login, $html);
header("HTTP/1.0 503 Service Unavailable");

echo $html;
