<?php
// (c) Copyright 2002-2015 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_module_list()
{
	return array(
		'module_zones_top' => array(
			'name' => tra('Top module zone'),
			'description' => tra('Activate zone for modules such as site logo, log-in form, etc. (page header)'),
			'type' => 'list',
			'keywords' => tra('sidebar'),
			'help' => 'Users+Flip+Columns',
			'options' => array(
				'y' => tra('Only if one or more modules are assigned'),
				'fixed' => tra('Always'),
//				'user' => tra('User decides'),
				'n' => tra('Never'),
			),
			'default' => 'y',
		),
		'module_zones_topbar' => array(
			'name' => tra('Topbar module zone'),
			'description' => tra('Activate zone for modules such as horizontal menu (navbar), search form, page-wide content, etc.'),
			'type' => 'list',
			'keywords' => tra('topbar'),
			'help' => 'Users+Flip+Columns',
			'options' => array(
				'y' => tra('Only if one or more modules are assigned'),
				'fixed' => tra('Always'),
//				'user' => tra('User decides'),
				'n' => tra('Never'),
			),
			'default' => 'y',
		),
		'module_zones_pagetop' => array(
			'name' => tra('Page top module zone'),
			'description' => tra('Activate zone for modules such as breadcrumbs, banners, share icons, etc'),
			'type' => 'list',
			'keywords' => tra('sidebar'),
			'help' => 'Users+Flip+Columns',
			'options' => array(
				'y' => tra('Only if one or more modules are assigned'),
				'fixed' => tra('Always'),
//				'user' => tra('User decides'),
				'n' => tra('Never'),
			),
			'default' => 'y',
		),
		'module_zones_bottom' => array(
			'name' => tra('Bottom module zone'),
			'description' => tra('Activate zone for modules such as "powered by" and "rss list" (page footer)'),
			'type' => 'list',
			'keywords' => tra('sidebar'),
			'help' => 'Users+Flip+Columns',
			'options' => array(
				'y' => tra('Only if one or more modules are assigned'),
				'fixed' => tra('Always'),
//				'user' => tra('User decides'),
				'n' => tra('Never'),
			),
			'default' => 'y',
		),
		'module_zones_pagebottom' => array(
			'name' => tra('Page bottom module zone'),
			'description' => tra('Activate zone for modules at the bottom of the main column of each page'),
			'type' => 'list',
			'keywords' => tra('sidebar'),
			'help' => 'Users+Flip+Columns',
			'options' => array(
				'y' => tra('Only if one or more modules are assigned'),
				'fixed' => tra('Always'),
//				'user' => tra('User decides'),
				'n' => tra('Never'),
			),
			'default' => 'y',
		),
		'module_file' => array(
			'name' => tr('Module file'),
			'description' => tr('Use a static module definition file instead of relying on the dynamic values generated by Tiki. Useful for custom themes. The file must be in YAML format, following the format used in profiles.'),
			'type' => 'text',
			'default' => '',
		),
		'module_zone_available_extra' => array(
			'name' => tr('Extra module zones available'),
			'description' => tr('Extra module zones to be managed through the module administration interface. Useful if your custom theme requires a special zone other than the predefined ones.'),
			'hint' => tr('Comma-separated list, maximum of 20 characters per entry.'),
			'type' => 'text',
			'separator' => ',',
			'filter' => 'alpha',
			'default' => array(),
		),
	);
}
