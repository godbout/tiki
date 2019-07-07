<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_geo_list()
{
	return [
		'geo_enabled' => [
			'name' => tr('Maps & Location Enabled'),
			'type' => 'flag',
			'description' => tr('Provide controls to load map and location libraries.'),
			'default' => 'n',
		],
		'geo_locate_wiki' => [
			'name' => tra('Geolocate wiki pages'),
			'description' => tra('Provide controls to indicate a geographic location of wiki pages in the edit form.'),
			'dependencies' => ['geo_enabled'],
			'type' => 'flag',
			'default' => 'n',
		],
		'geo_locate_article' => [
			'name' => tra('Geolocate articles'),
			'description' => tra('Provide controls to indicate a geographic location in the article edit form.'),
			'dependencies' => ['geo_enabled'],
			'type' => 'flag',
			'default' => 'n',
		],
		'geo_locate_blogpost' => [
			'name' => tra('Geolocate blog posts'),
			'description' => tra('Provide controls to indicate a geographic location in the blog post edit form.'),
			'dependencies' => ['geo_enabled'],
			'type' => 'flag',
			'default' => 'n',
		],
		'geo_tilesets' => [
			'name' => tra('Available tile layers on maps'),
			'description' => tra('Enables replacement of the default OpenStreetMap tiles with tiles from other mapping services, such as Google or Bing.'),
			'dependencies' => ['geo_enabled'],
			'hint' => tr(
				'Valid options for OpenLayers 2 are: %0 and for OpenLayers 3+ are: %1',
				implode(
					', ',
					[
						'openstreetmap',
						'mapquest_street',
						'mapquest_aerial',
						'google_street',
						'google_satellite',
						'google_physical',
						'google_hybrid',
						'blank',
					]
				),
				// for ol3+
				implode(
					', ',
					[
						'openstreetmap',
						'bing_road',
						'bing_road_on_demand',
						'bing_aerial',
						'bing_aerial_with_labels',
						'bing_collins_bart',
						'bing_ordnance_survey',
					]
				)
			),
			'type' => 'text',
			'filter' => 'word',
			'separator' => ',',
			'default' => ['openstreetmap'],
			'tags' => ['advanced'],
		],
		'geo_google_streetview' => [
			'name' => tr('Google Street View'),
			'description' => tr('Open Google Street View in a new window to see the visible coordinates.'),
			'dependencies' => ['gmap_key', 'geo_enabled'],
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['basic', 'experimental'],
		],
		'geo_google_streetview_overlay' => [
			'name' => tr('Google Street View overlay'),
			'description' => tr('Open Google Street View in a new window to see the visible coordinates.'),
			'dependencies' => ['geo_google_streetview'],
			'warning' => tr('This is not guaranteed to work.'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['basic', 'experimental'],
		],
		'geo_always_load_openlayers' => [
			'name' => tr('Always load OpenLayers'),
			'description' => tr('Load the OpenLayers library even if no map is explicitly included in the page'),
			'dependencies' => ['geo_enabled'],
			'type' => 'flag',
			'default' => 'n',
		],
		'geo_zoomlevel_to_found_location' => [
			'name' => tr('Zoom level for the found location'),
			'description' => tr('Zoom level when a searched-for location is found'),
			'dependencies' => ['geo_enabled'],
			'type' => 'list',
			'options' => [
					'street' => tra('Street'),
					'town' => tra('Town'),
					'region' => tra('Region'),
					'country' => tra('Country'),
					'continent' => tra('Continent'),
					'world' => tra('World'),
				],
			'default' => 'street',
		],
		'geo_openlayers_version' => [
			'name' => tr('OpenLayers version'),
			'type' => 'list',
			'dependencies' => ['geo_enabled'],
			'options' => [
					'ol2' => tra('OpenLayers 2.x (for use up to at least 15.x)'),
					'ol3' => tra('OpenLayers 3+ (experimental)'),
				],
			'default' => 'ol2',
		],
		'geo_bingmaps_key' => [
			'name' => tra('Bing Maps API Key'),
			'description' => tra('Needed for Bing Map Layers'),
			'type' => 'text',
			'help' => 'http://www.bingmapsportal.com/',
			'filter' => 'striptags',
			'default' => '',
		],
		'geo_nextzen_key' => [
			'name' => tra('Nextzen Maps API Key'),
			'description' => tra('Needed for Nextzen Map Layers'),
			'type' => 'text',
			'help' => 'https://developers.nextzen.org/',
			'filter' => 'striptags',
			'default' => '',
		],

	];
}
