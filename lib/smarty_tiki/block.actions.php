<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once ('function.popup.php');
require_once ('function.icon.php');

function smarty_block_actions($params, $content, $smarty, $repeat = false)
{
	global $prefs;

	if ($repeat) {
		return ('');
	}

	$return = '';

	$num_actions = substr_count($content, '<action>');

	if ($num_actions == 0) {
		return ('');
	} elseif ($num_actions == 1) {
		$content = str_ireplace('<action>', '', $content);
		$content = str_ireplace('</action>', '', $content);
		return ($content);
	}

	if ($prefs['javascript_enabled'] !== 'y') {
		$js = 'n';
		$libeg = '<li>';
		$liend = '</li>';
	} else {
		$js = 'y';
		$libeg = '';
		$liend = '';
	}

	if ($js === 'n') {
		$return .= '<ul class="cssmenu_horiz"><li>';
	}

	$return .= '<a
			class="tips"
			title="'.tra('Actions').'"
			href="#"';

	if ($js === 'y') {
		$return .= smarty_function_popup(['fullhtml' => '1', 'center' => 'true', 'text' => $content]);
	}

	$return .= 'style="padding:0; margin:0; border:0">';
	$return .= smarty_function_icon(['name' => 'wrench']);
	$return .= '</a>';

	if ($js === 'n') {
		$return .= '<ul class="dropdown-menu" role="menu">'.$content.'</ul></li></ul>';
	}

	$return = str_ireplace('<action>', $libeg, $return);
	$return = str_ireplace('</action>', $liend, $return);

	return ($return);
}
