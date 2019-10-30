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
				'description' => tra('Purge the cache when the type:id objects are updated. Set id=0 for any of that type. Or set type:withparam:x. Examples: trackeritem:20, trackeritem:trackerId:3, file:galleryId:5, forum post:forum_id:7, forum post:parent_id:8. Note that rule changes affect future caching, not past caches.'),
				'separator' => ',',
				'default' => '',
				'filter' => 'text',
				'since' => '20.0',
			],
			'multisearchid' => [
				'required' => false,
				'name' => 'ID of MULTISEARCH block from which to render results',
				'description' => tra('This is for much better performance by doing one search for multiple LIST plugins together. Render results from previous {MULTISEARCH(id-x)}...{MULTISEARCH} block by providing the ID used in that block.'),
				'filter' => 'text',
				'since' => '20.0',
			],
		],
	];
}

function wikiplugin_list($data, $params)
{
	global $prefs;
	global $user;

	static $multisearchResults;
	static $originalQueries;
	static $i;
	$i++;

	if (! isset($params['cache'])) {
		if ($prefs['unified_list_cache_default_on'] == 'y') {
			$params['cache'] = 'y';
		} else {
			$params['cache'] = 'n';
		}
	}

	if ($params['cache'] == 'y') {
		// Exclude any type of admin from caching
		foreach (TikiLib::lib('user')->get_user_permissions($user) as $permission) {
			if (substr($permission, 0, 12) == 'tiki_p_admin') {
				$params['cache'] = 'n';
				break;
			}
		}
	}

	if (! isset($params['gui'])) {
		$params['gui'] = 1;
	}

	if ($prefs['wikiplugin_list_gui'] === 'y' && $params['gui']) {
		TikiLib::lib('header')
			->add_jsfile('lib/jquery_tiki/pluginedit_list.js')
			->add_jsfile('vendor_bundled/vendor/jquery-plugins/nestedsortable/jquery.ui.nestedSortable.js');
	}

	$tosearch = [];

	if (isset($params['multisearchid']) && $params['multisearchid'] > '') {
		// If 'multisearchid' is provided as a parameter to the LIST plugin, it means the list plugin
		// is to render the results of that ID specified in the MULTISEARCH block of the "pre-searching" LIST plugin.
		$renderMultisearch = true;
	} else {
		$renderMultisearch = false;
	}

	$now = TikiLib::lib('tiki')->now;
	$cachelib = TikiLib::lib('cache');
	$cacheType = 'listplugin';
	if ($user) {
		$cacheName = md5($data);
	} else {
		$cacheName = md5($data."loggedout");
	}
	if (isset($params['cacheexpiry'])) {
		$cacheExpiry = $params['cacheexpiry'];
	} else {
		$cacheExpiry = $prefs['unified_list_cache_default_expiry'];
	}

	// First need to check for {MULTISEARCH()} blocks as then will need to do all queries at the same time
	$multisearch = false;
	$matches = WikiParser_PluginMatcher::match($data);
	$offset_arg = 'offset';
	$argParser = new WikiParser_PluginArgumentParser();

	foreach ($matches as $match) {
		if ($match->getName() == 'multisearch') {
			if ($prefs['unified_engine'] != 'elastic') {
				return tra("Error: {MULTISEARCH(id=x)} requires use of Elasticsearch as the engine.");
			}
			$args = $argParser->parse($match->getArguments());
			if (!isset($args['id'])) {
				return tra("Error: {MULTISEARCH(id=x)} needs an ID to be specified.");
			}
			$tosearch[$args['id']] = $match->getBody();
			$multisearch = true;
		}
		if ($match->getName() == 'list' || $match->getName() == 'pagination') {
			$args = $argParser->parse($match->getArguments());
			if (!empty($args['offset_arg'])) {
				// Update cacheName by offset arg to have different cache for each page of paginated list
				$offset_arg = $args['offset_arg'];
			}
		}
	}
	if (!empty($_REQUEST[$offset_arg])) {
		$cacheName .= '_' . $args['offset_arg'] . '=' . $_REQUEST[$offset_arg];
	}
	if (!$multisearch) {
		$tosearch = [ $data ];
	}

	if ($params['cache'] == 'y') {
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
				if ($multisearch) {
					$multisearchResults = $out;
				} else {
					return $out;
				}
			} else {
				$cachelib->invalidate($cacheName, $cacheType);
			}
		}
	}

	$unifiedsearchlib = TikiLib::lib('unifiedsearch');

	if (! $index = $unifiedsearchlib->getIndex()) {
		return '';
	}

	if ($renderMultisearch && isset($originalQueries[$params['multisearchid']])) {
		// Skip searching if rendering already retrieved results.
		$query = $originalQueries[$params['multisearchid']];
		$result = $query->search($index, '', $multisearchResults[$params['multisearchid']]);
	} else {
		// Perform searching
		foreach ($tosearch as $id => $body) {
			if ($renderMultisearch) {
				// when rendering and if not already in $originalQueries, then just need to get the one that matches.
				if ($params['multisearchid'] != $id) {
					continue;
				}
			}
			// Handle each query. If not multisearch will just be one.
			$query = new Search_Query;
			if (! isset($params['searchable_only']) || $params['searchable_only'] == 1) {
				$query->filterIdentifier('y', 'searchable');
			}
			$unifiedsearchlib->initQuery($query);

			$matches = WikiParser_PluginMatcher::match($body);

			$builder = new Search_Query_WikiBuilder($query);
			$builder->enableAggregate();
			$builder->apply($matches);
			$tsret = $builder->applyTablesorter($matches);
			if (! empty($tsret['max']) || ! empty($_GET['numrows'])) {
				$max = !empty($_GET['numrows']) ? $_GET['numrows'] : $tsret['max'];
				$builder->wpquery_pagination_max($query, $max);
			}
			$paginationArguments = $builder->getPaginationArguments();

			if (! empty($_REQUEST[$paginationArguments['sort_arg']])) {
				$query->setOrder($_REQUEST[$paginationArguments['sort_arg']]);
			}

			PluginsLibUtil::handleDownload($query, $index, $matches);

			/* set up facets/aggregations */
			$facetsBuilder = new Search_Query_FacetWikiBuilder();
			$facetsBuilder->apply($matches);
			if ($facetsBuilder->getFacets()) {
				$facetsBuilder->build($query, $unifiedsearchlib->getFacetProvider());
			}

			if ($multisearch) {
				$originalQueries[$id] = $query;
				$query->search($index, (string)$id);
			} elseif ($renderMultisearch) {
				$result = $query->search($index, '', $multisearchResults[$params['multisearchid']]);
			} else {
				$result = $query->search($index);
			}
		} // END: Foreach loop of queries
		if ($multisearch) {
			// Now that all the queries are in the stack, the actual search can be performed
			$multisearchResults = $index->triggerMultisearch();
			if ($params['cache'] == 'y') {
				$cachelib->cacheItem($cacheName, serialize([$now, $multisearchResults]), $cacheType);
			}
			// No output is required when saving results of multisearch for later rendering on page by other LIST plugins
			return '';
		}
	} // END: Perform searching

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

	if (!empty($params['resultCallback']) && is_callable($params['resultCallback'])) {
		return $params['resultCallback']($formatter->getPopulatedList($result), $formatter);
	}

	$out = $formatter->format($result);

	if ($params['cache'] == 'y') {
		$cachelib->cacheItem($cacheName, serialize([$now, $out]), $cacheType);
	}

	return $out;
}
