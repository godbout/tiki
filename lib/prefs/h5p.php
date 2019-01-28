<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_h5p_list($partial = false)
{
	$serviceLib = TikiLib::lib('service');
	return [
		'h5p_enabled' => [
			'name' => tra('H5P support'),
			'description' => tra('Handle H5P package files on upload. H5P enables the creation, sharing and reusing of interactive HTML5 content.'),
			'dependencies' => [
				'feature_file_galleries',
			],
			'extensions' => ['curl', 'zip'],
			'type' => 'flag',
			'default' => 'n',
			'filter' => 'alpha',
			'help' => 'H5P',
			'keywords' => 'h5p',
			'view' => $partial ? '' : $serviceLib->getUrl([
				'controller' => 'h5p',
				'action' => 'list_libraries',
			]),
		],
		'h5p_whitelist' => [
			'name' => tr('Whitelist'),
			'description' => tr('Allowed filetypes'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'text',
			'filter' => 'text',
			'default' => H5PCore::$defaultContentWhitelist,
		],
		'h5p_track_user' => [
			'name' => tra('H5P Tracker User'),
			'description' => tra('Store H5P results'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
			'view' => $partial ? '' : $serviceLib->getUrl([
				'controller' => 'h5p',
				'action' => 'list_results',
			]),
		],
		'h5p_dev_mode' => [
			'name' => tra('H5P Developer Mode'),
			'description' => tra('Use "patched" libraries?'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
		],
		'h5p_filegal_id' => [
			'name' => tr('Default Gallery'),
			'description' => tr('File gallery to create new H5P content in by default.'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'text',
			'filter' => 'int',
			'profile_reference' => 'file_gallery',
			'default' => 1,
		],
		'h5p_save_content_state' => [
			'name' => tra('Store user state'),
			'description' => tra('Allows users to resume at the point they last got to'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
		],
		'h5p_save_content_frequency' => [
			'name' => tr('Save Frequency'),
			'description' => tr('How often to update user data.'),
			'dependencies' => [
				'h5p_save_content_state',
			],
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('seconds'),
			'default' => 60,
		],
		'h5p_export' => [
			'name' => tra('Export'),
			'description' => tra('Allows users to export H5P content'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
		],
		'h5p_hub_is_enabled' => [
			'name' => tra('Hub Is Enabled'),
			'description' => tra('Updates libraries from h5p.org'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
		],
		'h5p_site_key' => [
			'name' => tr('Site Key'),
			'description' => tr('H5P Site Key.'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'text',
			'filter' => 'text',
			'default' => '',
			'warning' => tra('Experimental'),
		],
		'h5p_h5p_site_uuid' => [
			'name' => tr('H5P UUID'),
			'description' => tr('H5P Unique ID.'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'text',
			'filter' => 'text',
			'default' => '',
			'warning' => tra('Experimental'),
		],
		'h5p_content_type_cache_updated_at' => [
			'name' => tr('Content Type Updated'),
			'description' => tr('Last update.'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'text',
			'filter' => 'int',
			'units' => tr('seconds'),
			'default' => 0,
			'warning' => tra('Experimental'),
		],
		'h5p_check_h5p_requirements' => [
			'name' => tr('Check Requirements'),
			'description' => tr('Unused so far'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'y',
		],
		'h5p_send_usage_statistics' => [
			'name' => tr('Send Usage Statistics'),
			'description' => tr('Unused so far'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
			'warning' => tra('Experimental'),
		],
		'h5p_has_request_user_consent' => [
			'name' => tr('Request User Consent'),
			'description' => tr('Unused so far'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
			'warning' => tra('Experimental'),
		],
		'h5p_enable_lrs_content_types' => [
			'name' => tr('LRS Content Types'),
			'description' => tr('Reporting (?)'),
			'dependencies' => [
				'h5p_enabled',
			],
			'type' => 'flag',
			'filter' => 'alpha',
			'default' => 'n',
			'warning' => tra('Experimental'),
		],
	];
}
