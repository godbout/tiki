<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Converts multiple links into a popup. Often used for the action popup in a list of items.
 * Syntax is as follows:
 *	{actions}
 * 		<action>
 * 			<a href="tiki-index.php">
 * 				{icon name="go"}
 * 			</a>
 * 		</action>
 * 		{* one or more additional links within action tags as above *}
 * 	{/actions}
 *
 * @param $params		array
 * 		title:	string title of the dropdown
 * 		icon:	string icon name for the icon that is clicked or hovered over to display the popup
 * @param $content		string	HTML within the {actions} block tags. Usually within {strip} tags
 * @param $smarty		Smarty_Tiki
 * @param bool $repeat
 * @return mixed|string
 * @throws SmartyException
 */
function smarty_block_actions($params, $content, $smarty, $repeat = false)
{
	global $prefs;
	$smarty->loadPlugin('smarty_function_popup');
	$smarty->loadPlugin('smarty_function_icon');

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
		$js = 0;
		$libeg = '<li>';
		$liend = '</li>';
	} else {
		$js = 1;
		$libeg = '';
		$liend = '';
	}

	if (! $js) {
		$return .= '<ul class="cssmenu_horiz float-right"><li>';
	}

	$title = ! empty($params['title']) ? htmlspecialchars($params['title']) : tra('Actions');
	$icon = ! empty($params['icon']) ? $params['icon'] : 'settings';

	$return .= '<a
			class="float-right p-0 m-0 border border-0"
			title="' . $title . '"
			href="#"';

	if ($js) {
		$return .= ' ' . smarty_function_popup(['fullhtml' => '1', 'center' => 'true', 'text' => $content, 'trigger' => 'click'], $smarty);
	}

	$return .= '>';
	$return .= smarty_function_icon(['name' => $icon, 'iclass' => 'float-right'], $smarty);
	$return .= '</a>';

	if (! $js) {
		$return .= '<ul class="dropdown-menu" role="menu">';
		$return .= '<li class="dropdown-title"><li class="dropdown-title">' . $title;
		$return .= '</li><li class="dropdown-divider"></li>';
		$return .= $content . '</ul></li></ul>';
	}

	$return = str_ireplace(['&lt;action&gt;', '<action>'], $libeg, $return);
	$return = str_ireplace(['&lt;&#x2F;action&gt;', '</action>'], $liend, $return);

	return ($return);
}
