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

function smarty_function_tracker_item_status_icon($params, $smarty)
{
	global $prefs;

	if (empty($params['item'])) {
		return '';
	}

	$item = $params['item'];

	if (! is_object($item)) {
		$item = Tracker_Item::fromId($item);
	}
		
	if (! empty($prefs['tracker_status_in_objectlink'])) {
		$show_status = $prefs['tracker_status_in_objectlink'];
	} else {
		$show_status = 'y';
	}

	if (($show_status == 'y') && $item && $status = $item->getDisplayedStatus()) {
		$smarty->loadPlugin('smarty_function_icon');
		return smarty_function_icon([
			'name' => 'status-' . $status,
			'iclass' => 'tips',
			'ititle' => ':' . tr($status),
		], $smarty);
	}

	return '';
}
