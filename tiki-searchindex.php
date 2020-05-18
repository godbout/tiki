<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$inputConfiguration = [
  [ 'staticKeyFilters' => [
	'date' => 'digits',
	'maxRecords' => 'digits',
	'highlight' => 'text',
	'where' => 'text',
	'find' => 'text',
	'searchLang' => 'word',
	'words' => 'text',
	'boolean' => 'word',
	'storeAs' => 'int',
	]
  ]
];

$section = 'search';
require_once('tiki-setup.php');
$access->check_feature('feature_search');
$access->check_permission('tiki_p_search');
$smarty->assign('headtitle', tr('Search'));

//get_strings tra("Searchindex")
//ini_set('display_errors', true);
//error_reporting(E_ALL);

foreach (['find', 'highlight', 'where'] as $possibleKey) {
	if (empty($_REQUEST['filter']) && ! empty($_REQUEST[$possibleKey])) {
		$_REQUEST['filter']['content'] = $_REQUEST[$possibleKey];
	}
}
$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : [];
$postfilter = isset($_REQUEST['postfilter']) ? $_REQUEST['postfilter'] : [];
$facets = [];

if (count($filter) || count($postfilter)) {
	if (isset($_REQUEST['save_query'])) {
		$_SESSION['quick_search'][(int) $_REQUEST['save_query']] = $_REQUEST;
	}
	$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;
	$maxRecords = empty($_REQUEST['maxRecords']) ? $prefs['maxRecords'] : $_REQUEST['maxRecords'];

	if ($access->is_serializable_request(true)) {
		$jitRequest->replaceFilter('fields', 'word');
		$fetchFields = array_merge(['title', 'modification_date', 'url'], $jitRequest->asArray('fields', ','));
		;

		$results = tiki_searchindex_get_results($filter, $postfilter, $offset, $maxRecords);

		$smarty->loadPlugin('smarty_function_object_link');
		$smarty->loadPlugin('smarty_modifier_sefurl');
		foreach ($results as &$res) {
			foreach ($fetchFields as $f) {
				if (isset($res[$f])) {
					$res[$f]; // Dynamic load if applicable
				}
			}
			$res['link'] = smarty_function_object_link(
				[
					'type' => $res['object_type'],
					'id' => $res['object_id'],
					'title' => $res['title'],
				],
				$smarty->getEmptyInternalTemplate()
			);
			$res = array_filter(
				$res,
				function ($v) {
					return ! is_null($v);
				}
			);	// strip out null values
		}
		// add facet/aggregations to the serialised outout
		if ($prefs['search_use_facets'] == 'y') {
			$facets = array_map(
				function ($facet) {
					return $facet->getOptions();
				},
				$results->getFacets()
			);
			$resultArray = [
				'count' => $results->count(),
				'maxRecords' => $results->getMaxRecords(),
				'offset' => $results->getOffset(),
				'result' => (array) $results,
				'facets' => $facets,
			];
			$results = $resultArray;
		}

		$access->output_serialized(
			$results,
			[
				'feedTitle' => tr('%0: Results for "%1"', $prefs['sitetitle'], isset($filter['content']) ? $filter['content'] : ''),
				'feedDescription' => tr('Search Results'),
				'entryTitleKey' => 'title',
				'entryUrlKey' => 'url',
				'entryModificationKey' => 'modification_date',
				'entryObjectDescriptors' => ['object_type', 'object_id'],
			]
		);
		exit;
	} else {
		$cachelib = TikiLib::lib('cache');
		$cacheType = 'search';
		$cacheName = $user . '/' . $offset . '/' . $maxRecords . '/' . serialize($filter);
		$isCached = false;
		if (! empty($prefs['unified_user_cache']) && $cachelib->isCached($cacheName, $cacheType)) {
			list($date, $html) = $cachelib->getSerialized($cacheName, $cacheType);
			if ($date > $tikilib->now - $prefs['unified_user_cache'] * 60) {
				$isCached = true;
			}
		}
		$excludedFacets = tiki_searchindex_get_excluded_facets();
		if (! $isCached) {
			$results = tiki_searchindex_get_results($filter, $postfilter, $offset, $maxRecords);
			$facets = array_filter(array_map(
				function ($facet) use ($excludedFacets) {
					$name = $facet->getName();
					if (! in_array($name, $excludedFacets)) {
						return $name;
					} else {
						return '';
					}
				},
				$results->getFacets()
			));

			$plugin = new Search_Formatter_Plugin_SmartyTemplate('searchresults-plain.tpl');
			$plugin->setData(
				[
					'prefs' => $prefs,
				]
			);
			$fields = [
				'title' => null,
				'url' => null,
				'modification_date' => null,
				'highlight' => null,
			];
			if ($prefs['feature_search_show_visit_count'] === 'y') {
				$fields['visits'] = null;
			}
			$plugin->setFields($fields);

			$formatter = Search_Formatter_Factory::newFormatter($plugin);

			$wiki = $formatter->format($results);
			$parserLib = TikiLib::lib('parser');
			$wiki = $parserLib->searchFilePreview($wiki);
			$html = $parserLib->parse_data(
				$wiki,
				[
					'is_html' => true,
				]
			);
			if (! empty($prefs['unified_user_cache'])) {
				$cachelib->cacheItem($cacheName, serialize([$tikilib->now, $html]), $cacheType);
			}
		}
		$smarty->assign('results', $html);
	}
}

$smarty->assign('filter', $filter);
$smarty->assign('postfilter', $postfilter);
$smarty->assign('facets', $facets);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

if ($prefs['search_use_facets'] == 'y' && $prefs['unified_engine'] === 'elastic') {
	$smarty->display("tiki-searchfacets.tpl");
} else {
	$smarty->display("tiki-searchindex.tpl");
}

/**
 * @param array $filter
 * @param array $postfilter
 * @param int $offset
 * @param int $maxRecords
 *
 * @return Search_ResultSet
 * @throws Exception
 */
function tiki_searchindex_get_results($filter, $postfilter, $offset, $maxRecords)
{
	global $prefs;

	$unifiedsearchlib = TikiLib::lib('unifiedsearch');

	$query = new Search_Query;
	$unifiedsearchlib->initQueryBase($query);
	$query = $unifiedsearchlib->buildQuery($filter, $query);
	$query->filterContent('y', 'searchable');

	if (count($postfilter)) {
		$unifiedsearchlib->buildQuery($postfilter, $query->getPostFilter());
	}

	if (isset($_REQUEST['sort_mode']) && $order = Search_Query_Order::parse($_REQUEST['sort_mode'])) {
		$query->setOrder($order);
	}

	if ($prefs['storedsearch_enabled'] == 'y' && ! empty($_POST['storeAs'])) {
		$storedsearch = TikiLib::lib('storedsearch');
		$storedsearch->storeUserQuery($_POST['storeAs'], $query);
		TikiLib::lib('smarty')->assign('display_msg', tr('Your query was stored.'));
	}

	$unifiedsearchlib->initQueryPermissions($query);

	$query->setRange($offset, $maxRecords);

	if ($prefs['feature_search_stats'] == 'y') {
		$stats = TikiLib::lib('searchstats');
		foreach ($query->getTerms() as $term) {
			$stats->register_term_hit($term);
		}
	}

	if ($prefs['search_use_facets'] == 'y') {
		$provider = $unifiedsearchlib->getFacetProvider();
		$facetLabels = [];

		if ($prefs['search_avoid_duplicated_facet_labels'] === 'y') {
			foreach ($provider->getFacets() as $facet) {
				$facetLabels[] = $facet->getLabel();
			}
		}
		$duplicateLabels = array_filter(
			array_count_values($facetLabels),
			function ($value) {
				return $value > 1;
			}
		);

		foreach ($provider->getFacets() as $facet) {
			$name = $facet->getName();
			if (! in_array($name, tiki_searchindex_get_excluded_facets())) {
				if ($prefs['search_avoid_duplicated_facet_labels'] === 'y') {
					$label = $facet->getLabel();
					if (key_exists($label, $duplicateLabels)) {
						// it's almost always tracker fields that are duplicated, so just them for now
						if (strpos($name, 'tracker_field_') === 0) {
							$field =TikiLib::lib('trk')->get_tracker_field(substr($name, 14));
							$definition = \Tracker_Definition::get($field['trackerId']);
							$facet->setLabel($label . ' (' . $definition->getConfiguration('name') . ')');
						}
					}
				}
				$query->requestFacet($facet);
			}
		}
	}


	if ($prefs['unified_highlight_results'] === 'y') {
		$query->applyTransform(
			new \Search\ResultSet\UrlHighlightTermsTransform(
				$query->getTerms()
			)
		);
	}

	try {
		if ($prefs['federated_enabled'] == 'y' && ! empty($filter['content'])) {
			$fed = TikiLib::lib('federatedsearch');
			$fed->augmentSimpleQuery($query, $filter['content']);
		}

		$resultset = $query->search($unifiedsearchlib->getIndex());

		return $resultset;
	} catch (Search_Elastic_TransportException $e) {
		Feedback::error(tr('Search functionality currently unavailable.'));
	} catch (Exception $e) {
		Feedback::error($e->getMessage());
	}

	return new Search_ResultSet([], 0, 0, -1);
}

/**
 * Temporarily exclude the two date range facets from tiki-searchindex.php as they don't work
 *
 * @return array
 */
function tiki_searchindex_get_excluded_facets()
{
	global $prefs;

	return array_merge(
		$prefs['search_excluded_facets'],
		[
			'date_histogram',
			'date_range',
		]
	);
}

