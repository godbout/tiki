<?php

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_modifier_packagenavbar($token, $from = '')
{
	$api = new \Tiki\Package\Extension\Api\NavBar();
	if ($ret = $api->getNavBar($token, $from)) {
		return $ret;
	} else {
		return '';
	}
}
