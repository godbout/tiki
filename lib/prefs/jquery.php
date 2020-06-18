<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_jquery_list($partial = false)
{

	global $prefs;

	$jquery_effect_options = [
		''      => tra('Default'),
		'none'  => tra('None'),
		'slide' => tra('Slide'),
		'fade'  => tra('Fade'),
	];

	if (! $partial && $prefs['feature_jquery_ui'] == 'y') {
		$jquery_effect_options['blind_ui'] = tra('Blind (UI)');
		$jquery_effect_options['clip_ui'] = tra('Clip (UI)');
		$jquery_effect_options['drop_ui'] = tra('Drop (UI)');
		$jquery_effect_options['explode_ui'] = tra('Explode (UI)');
		$jquery_effect_options['fold_ui'] = tra('Fold (UI)');
		$jquery_effect_options['puff_ui'] = tra('Puff (UI)');
		$jquery_effect_options['slide_ui'] = tra('Slide (UI)');
	}

	return [
		'jquery_effect' => [
			'name' => tra('Effect for modules'),
			'type' => 'list',
			'options' => $jquery_effect_options,
			'help' => 'JQuery#Effects',
			'default' => '',				// Default effect for general show/hide: ['' | 'slide' | 'fade' | and
											// see http://docs.jquery.com/UI/Effects: 'blind' | 'clip' | 'explode' etc]
		],
		'jquery_effect_tabs' => [
			'name' => tra('Effect for tabs'),
			'type' => 'list',
			'options' => $jquery_effect_options,
			'help' => 'JQuery#Effects',
			'default' => 'slide',	// Different effect for tabs (['none' | 'normal' (for jq) | 'slide' etc]
		],
		'jquery_effect_speed' => [
			'name' => tra('Speed'),
			'type' => 'list',
			'options' => [
				'fast' => tra('Fast'),
				'normal' => tra('Normal'),
				'slow' => tra('Slow'),
			],
			'default' => 'normal', 	// ['slow' | 'normal' | 'fast' | milliseconds (int) ]
		],
		'jquery_effect_direction' => [
			'name' => tra('Direction'),
			'type' => 'list',
			'options' => [
				'vertical' => tra('Vertical'),
				'horizontal' => tra('Horizontal'),
				'left' => tra('Left'),
				'right' => tra('Right'),
				'up' => tra('Up'),
				'down' => tra('Down'),
			],
			'default' => 'vertical', 	// ['horizontal' | 'vertical' | 'left' | 'right' | 'up' | 'down' ]
		],
		'jquery_effect_tabs_speed' => [
			'name' => tra('Speed'),
			'type' => 'list',
			'options' => [
				'fast' => tra('Fast'),
				'normal' => tra('Normal'),
				'slow' => tra('Slow'),
			],
			'default' => 'fast',
		],
		'jquery_effect_tabs_direction' => [
			'name' => tra('Direction'),
			'type' => 'list',
			'options' => [
				'vertical' => tra('Vertical'),
				'horizontal' => tra('Horizontal'),
				'left' => tra('Left'),
				'right' => tra('Right'),
				'up' => tra('Up'),
				'down' => tra('Down'),
			],
			'default' => 'vertical',
		],
		'jquery_ui_chosen' => [
			'name' => tra('jQuery-UI Chosen Select Boxes'),
			'description' => tra('Styled replacement for dropdown select lists and multiple-select inputs.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_jquery_ui',
			],
		],
		'jquery_ui_chosen_sortable' => [
			'name' => tra('Sortable Chosen Multi-selects'),
			'description' => tra('Enable drag and drop re-ordering of Chosen multi-select options.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'jquery_ui_chosen',
			],
		],
		'jquery_colorbox_theme' => [
			'name' => tra('Visual style of Colorbox (a.k.a. "Shadowbox")'),
			'type' => 'list',
			'perspective' => false,
			'options' => [
				'example1' => tra('One'),
				'example2' => tra('Two'),
				'example3' => tra('Three'),
				'example4' => tra('Four'),
				'example5' => tra('Five'),
			],
			'default' => 'example1',
			'dependencies' => [
				'feature_shadowbox',
			],
		],
		'jquery_fitvidjs' => [
			'name' => tra('FitVids.js'),
			'description' => tra('jQuery plugin for fluid-width (responsive) embedded videos.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'jquery_timeago' => [
			'name' => tra('jQuery Timeago'),
			'description' => tra('jQuery plugin for fuzzy timestamps.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'jquery_smartmenus_enable' => [
			'name' => tra('SmartMenus'),
			'description' => tra('Add "SmartMenus" to Bootstrap menus. See smartmenus.org for more.'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['advanced'],
			'keywords' => 'smart menu',
		],
		'jquery_smartmenus_mode' => [
			'name' => tra('SmartMenus Mode'),
			'type' => 'list',
			'options' => [
				'' => tra('None'),
				'blue' => tra('Blue'),
				'clean' => tra('Clean'),
				'mint' => tra('Mint'),
				'simple' => tra('Simple'),
			],
			'default' => '',
			'dependencies' => [
				'jquery_smartmenus_enable',
			],
		],
		'jquery_ui_modals_draggable' => [
			'name' => tra('Draggable Modals'),
			'description' => tra('Modal popups can be moved around.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_jquery_ui',
			],
		],
		'jquery_ui_modals_resizable' => [
			'name' => tra('Resizable Modals'),
			'description' => tra('Modal popups can be resized.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_jquery_ui',
			],
		],
		'jquery_jqdoublescroll' => [
			'name' => tra('jQuery Double Scroll'),
			'description' => tra('jQuery plugin which adds an extra horizontal scroll bar at the top.'),
			'type' => 'flag',
			'default' => 'n',
		],
	];
}
