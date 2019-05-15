<?php

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
		header("location: index.php");
		exit;
}

function smarty_modifier_packageitemid($token)
{

	$api = new \Tiki\Package\Extension\Api();
		return $api->getItemIdFromToken($token);
}
