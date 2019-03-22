<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once 'lib/wiki/pluginslib.php';

function wikiplugin_list_info()
{
	return [
		'name' => tra('List'),
		'documentation' => 'PluginList',
		'description' => tra('Search for, list, and filter all types of items and display custom-formatted results.'),
		'prefs' => ['wikiplugin_list', 'feature_search'],
		'body' => tra('List configuration information'),
		'filter' => 'wikicontent',
		'profile_reference' => 'search_plugin_content',
		'iconname' => 'list',
		'introduced' => 7,
		'tags' => [ 'basic' ],
		'params' => [
			'searchable_only' => [
				'required' => false,
				'name' => tra('Searchable Only Results'),
				'description' => tra('Only include results marked as searchable in the index.'),
				'filter' => 'digits',
				'default' => '1',
				'options' => [
					['text' => tra(''), 'value' => ''],
					['text' => tra('Yes'), 'value' => '1'],
					['text' => tra('No'), 'value' => '0'],
				],
			],
			'gui' => [
				'required' => false,
				'name' => tra('Use List GUI'),
				'description' => tra('Use the graphical user interface for editing this list plugin.'),
				'filter' => 'digits',
				'default' => '1',
				'options' => [
					['text' => tra(''), 'value' => ''],
					['text' => tra('Yes'), 'value' => '1'],
					['text' => tra('No'), 'value' => '0'],
				],
			],
			'cache' => [
				'required' => false,
				'name' => tra('Cache Output'),
				'description' => tra('Cache output of this list plugin.'),
				'filter' => 'word',
				'since' => '20.0',
				'options' => [
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n'],
				]
			],
			'cacheexpiry' => [
				'required' => false,
				'name' => tra('Cache Expiry Time'),
				'description' => tra('Time before cache is expired in minutes.'),
				'filter' => 'word',
				'since' => '20.0',
			],
			'cachepurgerules' => [
				'required' => false,
				'name' => tra('Cache Purge Rules'),
				'description' => tra('Purge the cache when the type:id objects are updated. Set id=0 for any of that type. Examples: trackeritem:20, trackeritem:trackerId:3, file:galleryId:5, forum post:forum_id:7, forum post:parent_id:8. Note that rule changes affect future caching, not past caches.'),
				'separator' => ',',
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
		],
	];
}

function wikiplugin_list($data, $params)
{
	global $prefs;

	static $i;
	$i++;

	if (! isset($params['gui'])) {
		$params['gui'] = 1;
	}

	if ($prefs['wikiplugin_list_gui'] === 'y' && $params['gui']) {
		TikiLib::lib('header')
			->add_jsfile('lib/jquery_tiki/pluginedit_list.js')
			->add_jsfile('vendor_bundled/vendor/jquery/plugins/nestedsortable/jquery.ui.nestedSortable.js');
	}

	$now = TikiLib::lib('tiki')->now;
	$cachelib = TikiLib::lib('cache');
	$cacheType = 'listplugin';
	$cacheName = md5($data);
	if (isset($params['cacheexpiry'])) {
		$cacheExpiry = $params['cacheexpiry'];
	} else {
		$cacheExpiry = $prefs['unified_list_cache_default_expiry'];
	}

	if ($params['cache'] == 'y' || $prefs['unified_list_cache_default_on'] == 'y' && $params['cache'] != 'n') {
		// Clean rules setting
		$rules = array();
		foreach ($params['cachepurgerules'] as $r) {
			$parts = explode(':', $r, 2);
			$cleanrule['type'] = trim($parts[0]);
			$cleanrule['object'] = trim($parts[1]);
			$rules[] = $cleanrule;
		}
		// Need to check if existing rules have been changed and therefore have to be deleted first
		$oldrules = $cachelib->get_purge_rules_for_cache($cacheType, $cacheName);
		if ($oldrules != $rules) {
			$cachelib->clear_purge_rules_for_cache($cacheType, $cacheName);
		}
		// Now set rules
		foreach ($rules as $rule) {
			$cachelib->set_cache_purge_rule($rule['type'], $rule['object'], $cacheType, $cacheName);
		}
		// Now retrieve cache if any
		if ($cachelib->isCached($cacheName, $cacheType)) {
			list($date, $out) = $cachelib->getSerialized($cacheName, $cacheType);
			if ($date > $now - $cacheExpiry * 60) {
				return $out;
			}
		}
	}

	$unifiedsearchlib = TikiLib::lib('unifiedsearch');

	$query = new Search_Query;
	if (! isset($params['searchable_only']) || $params['searchable_only'] == 1) {
		$query->filterIdentifier('y', 'searchable');
	}
	$unifiedsearchlib->initQuery($query);

	$matches = WikiParser_PluginMatcher::match($data);

	$builder = new Search_Query_WikiBuilder($query);
	$builder->enableAggregate();
	$builder->apply($matches);
	$tsret = $builder->applyTablesorter($matches);
	if (! empty($tsret['max']) || ! empty($_GET['numrows'])) {
		$max = ! empty($_GET['numrows']) ? $_GET['numrows'] : $tsret['max'];
		$builder->wpquery_pagination_max($query, $max);
	}
	$paginationArguments = $builder->getPaginationArguments();

	if (! empty($_REQUEST[$paginationArguments['sort_arg']])) {
		$query->setOrder($_REQUEST[$paginationArguments['sort_arg']]);
	}

	if (! $index = $unifiedsearchlib->getIndex()) {
		return '';
	}

	PluginsLibUtil::handleDownload($query, $index, $matches);

	$result = $query->search($index);

	$result->setId('wplist-' . $i);

	$resultBuilder = new Search_ResultSet_WikiBuilder($result);
	$resultBuilder->setPaginationArguments($paginationArguments);
	$resultBuilder->apply($matches);

	$builder = new Search_Formatter_Builder;
	$builder->setPaginationArguments($paginationArguments);
	$builder->setId('wplist-' . $i);
	$builder->setCount($result->count());
	$builder->setTsOn($tsret['tsOn']);
	$builder->apply($matches);

	$result->setTsSettings($builder->getTsSettings());

	$formatter = $builder->getFormatter();

	$result->setTsOn($tsret['tsOn']);
	$out = $formatter->format($result);

	if ($params['cache'] == 'y' || $prefs['unified_list_cache_default_on'] == 'y' && $params['cache'] != 'n') {
		$cachelib->cacheItem($cacheName, serialize([$now, $out]), $cacheType);
	}

	return $out;
}
