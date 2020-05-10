<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function smarty_function_preference($params, $smarty)
{
	global $prefs, $user_overrider_prefs;
	$prefslib = TikiLib::lib('prefs');
	if (! isset($params['name'])) {
		return 'Preference name not specified.';
	}

	$source = null;
	if (isset($params['source'])) {
		$source = $params['source'];
	}
	$get_pages = isset($params['get_pages']) && $params['get_pages'] != 'n' ? true : false;

	if ($info = $prefslib->getPreference($params['name'], true, $source, $get_pages)) {
		if (isset($info['hide']) && $info['hide'] === true) {
			return '';
		}

		if (isset($params['label'])) {
			$info['name'] = $params['label'];
		}
		if ($source === null && in_array($params['name'], $user_overrider_prefs) && isset($prefs[$params['name']])) {
			$info['value'] = $prefs['site_' . $params['name']];
		}

		if (isset($info['autocomplete'])) {
			// For passwords, autocomplete=off should ne replaced with autocomplete=new-password
			$autocomplete = ($info['autocomplete'] === 'off' && $info['type'] === 'password') ? 'new-password' : $info['autocomplete'];
			$info['params'] .= ' autocomplete="' . $autocomplete . '" ';
		} elseif ($info['type'] === 'password' && ! isset($info['parameters'], $info['parameters']['autocomplete'])) {
			$info['params'] .= ' autocomplete="new-password" '; // by default preferences of type password should not be autocomplete
		}

		if (isset($params['visible']) && $params['visible'] == 'always') {
			// Modified preferences are never hidden, so pretend it's modified when forcing display
			$info['tags'][] = 'modified';
			$info['tagstring'] .= ' modified';
		}

		$pages_string = '';
		if ($get_pages) {
			if (count($info['pages']) > 0) {
				foreach ($info['pages'] as $pg) {
					$ct_string = $pg[1] > 1 ? '&amp;cookietab=' . $pg[1] : '';
					$pages_string .= ($pages_string ? ', ' : '');
					$pages_string .= '<a class="lm_result label label-default" href="tiki-admin.php?page=' . $pg[0] . $ct_string . '&amp;highlight=' . $info['preference'] . '">' . $pg[0] . '</a>';
				}
			} else {
				$pages_string = tra('(not found in an admin panel)');
			}
		}
		$info['pages'] = $pages_string;

		if (! isset($info['separator'])) {
			$info['separator'] = [];
		}
		if (isset($params['size'])) {
			$info['size'] = $params['size'];
		}
		if (isset($params['fgal_picker'])) {
			$info['fgal_picker'] = true;
		}
		if (isset($params['show_tags']) && ! $params['show_tags']) {
			$info['tags'] = [];
		}

		$smarty->assign('p', $info);

		/* Allows having preference Lisa show only if its parent preference Homer is *un*checked (rather than checked), by setting mode=invert on Homer.
		TODO: Replace this with something on children rather than on the parent, otherwise all children must display/hide at the same time. */
		if (isset($params['mode']) && in_array($params['mode'], ['invert', 'notempty'])) {
			$smarty->assign('mode', $params['mode']);
		} else {
			$smarty->assign('mode', 'normal');
		}

		//we reset the codemirror/syntax vars so that they are blank because they are reused for other params
		$smarty->assign('codemirror');
		$smarty->assign('syntax');

		if (! empty($params['syntax'])) {
			$smarty->assign('codemirror', 'true');
			$smarty->assign('syntax', $params['syntax']);
		}

		if (file_exists('templates/prefs/' . $info['type'] . '.tpl')) {
			return $smarty->fetch('prefs/' . $info['type'] . '.tpl', $params['name']);
		} else {
			return $smarty->fetch('prefs/text.tpl');
		}
	} else {
		$info = [
			'value' => tra('Error'),
			'default_val' => tra('Error'),
			'name' => tr('Preference %0 is not defined', $params['name']),
			'tags' => ['modified', 'basic', 'all'],
			'tagstring' => 'modified basic all',
			'separator' => null,
		];
		if (strpos($_SERVER["SCRIPT_NAME"], 'tiki-edit_perspective.php') !== false) {
			$info['hint'] = tra('Drag this out of the perspective and resave the perspective.');
		}

		$smarty->assign('p', $info);
		return $smarty->fetch('prefs/text.tpl');
	}
}
