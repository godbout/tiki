<?php

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_modifier_packagegroupname($token)
{
	$api = new \Tiki\Package\Extension\Api\Group();
	if ($ret = $api->getOrganicGroupName($token)) {
		return $ret;
	} else {
		return $token;
	}
}
