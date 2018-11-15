<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_theme_list($partial = false)
{
	global $prefs;
	$themelib = TikiLib::lib('theme');

	//get list of themes and make the first character of array values uppercase
	$themes = array_map('ucfirst', $themelib->list_themes());
	//get list of theme options
	$theme_options = [
		'' => tr('None'),
	];
	if (! $partial) {
		$theme_options = $theme_options + $themelib->get_options();
	}

	//admin themes -> add empty option which means that site theme should be used. Also remove Custom URL.
	$admin_themes = [
		'' => tr('Site theme'),
	];
	$admin_themes = $admin_themes + $themes;
	unset($admin_themes['custom_url']); //remove custom URL from the list

	//get list of icon sets shipped by Tiki
	$iconsets = $themelib->list_base_iconsets();
	$iconsets['theme_specific_iconset'] = tr('Icons of the displayed theme'); //add a specific option to allow theme specific icon set to be used

	return [
		'theme' => [
			'name' => tr('Site theme'),
			'description' => tr('The default theme for the site. Themes are bootstrap.css variants, including original Tiki themes as well as implementations of themes from Bootswatch.com. For more information about Bootstrap, see getbootstrap.com.'),
			'type' => 'list',
			'default' => 'default',
			'options' => $themes,
			'help' => 'Themes',
			'tags' => ['basic'],
		],
		'theme_option' => [
			'name' => tra('Site theme option'),
			'type' => 'list',
			'help' => 'Themes',
			'description' => tra('Supplemental style sheet for the selected theme'),
			'options' => $theme_options,
			'default' => '',
			'tags' => ['basic'],
			'keywords' => tra('theme option, theme-option, style option, options, css'),
		],
		'theme_custom_url' => [
			'name' => tr('Custom theme URL'),
			'description' => tr('Local or external URL of the custom Bootstrap-compatible CSS file to use.'),
			'type' => 'text',
			'filter' => 'url',
			'default' => '',
			'tags' => ['basic'],
		],
		'theme_admin' => [
			'name' => tra('Admin theme'),
			'type' => 'list',
			'help' => 'Themes',
			'description' => tra('Theme for the settings panels and other administration pages'),
			'options' => $admin_themes,
			'default' => '',
			'tags' => ['basic'],
		],
		'theme_option_admin' => [
			'name' => tra('Admin theme option'),
			'type' => 'list',
			'help' => 'Themes',
			'description' => tra('Supplemental style sheet for the selected theme'),
			'options' => $theme_options,
			'default' => '',
			'tags' => ['basic'],
		],
		'theme_option_includes_main' => [
			'name' => tra('Option theme includes main theme CSS'),
			'type' => 'flag',
			'help' => 'Themes',
			'description' => tra('Don\'t include the main theme stylesheet because its contents are included in the option stylesheet.'),
			'default' => 'n',
		],
        'theme_navbar_color_variant' => [
            'name' => tra('Navbar background color'),
            'type' => 'radio',
            'options' => [
                'dark' => tra('Dark'),
                'light' => tra('Light'),
            ],
            'help' => 'Themes',
            'description' => tra('Select a dark or light navbar (containing horizontal menu, etc.), as styled by the theme.'),
            'default' => 'light',
        ],
		'theme_navbar_fixed_topbar_offset' => [
			'name' => tra('Fixed topbar offset'),
			'type' => 'text',
			'size' => '4',
			'filter' => 'digits',
			'units' => 'px',
			'help' => 'Themes',
			'description' => tra('The offset value of the top padding should be the same height as the navbar in the fixed top position to prevent the navbar from obscuring the page content.'),
			'default' => '',
			'keywords' => tra('topbar offset, top offset, fixed topbar, top padding, offset'),
			'tags' => ['basic'],
		],
		'theme_iconset' => [
			'name' => tr('Icons'),
			'description' => tr('Icon set used by the site.'),
			'type' => 'list',
			'options' => $iconsets,
			'default' => 'default',
			'help' => 'Icons',
			'tags' => ['basic'],
		],
		'theme_customizer' => [
			'name' => tra('Theme Customizer tool'),
			'description' => tra('Activate the theme customizer tool to enable easy theme customization.'),
			'type' => 'flag',
			'help' => 'Themes',
			'default' => 'n',
			'tags' => ['experimental'],
			'view' => TikiLib::lib('service')->getUrl(['controller' => 'styleguide', 'action' => 'show']),
		],
	];
}
