<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use \Symfony\Component\Console\Helper\FormatterHelper;

class Services_Search_Controller
{
	function action_help($input)
	{
		return [
			'title' => tr('Help'),
		];
	}

	function action_rebuild($input)
	{
		global $num_queries;
		global $prefs;

		Services_Exception_Denied::checkGlobal('admin');

		$timer = new \timer();
		$timer->start();

		$memory_peak_usage_before = memory_get_peak_usage();

		$num_queries_before = $num_queries;

		$unifiedsearchlib = TikiLib::lib('unifiedsearch');
		$stat = null;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Apply 'Search index rebuild memory limit' setting if available
			if (! empty($prefs['allocate_memory_unified_rebuild'])) {
				$memory_limiter = new Tiki_MemoryLimit($prefs['allocate_memory_unified_rebuild']);
			}

			$stat = $unifiedsearchlib->rebuild($input->loggit->int());

			TikiLib::lib('cache')->empty_type_cache('search_valueformatter');

			// Also rebuild admin index
			TikiLib::lib('prefs')->rebuildIndex();

			// Back up original memory limit if possible
			if (isset($memory_limiter)) {
				unset($memory_limiter);
			}

			//clean error messages related with search index
			$removeIndexErrorsCallback = function ($item) {
				if ($item['type'] == 'error') {
					foreach ($item['mes'] as $me) {
						if (strpos($me, 'does not exist in the current index') !== false) {
							return true;
						}
					}
				}
				return false;
			};

			Feedback::removeIf($removeIndexErrorsCallback);
		}

		$num_queries_after = $num_queries;

		list($engine, $version, $index) = $unifiedsearchlib->getCurrentEngineDetails();

		$lastLogItem = $unifiedsearchlib->getLastLogItem();
		list($fallbackEngine, $fallbackEngineName, $fallbackVersion, $fallbackIndex) = $unifiedsearchlib->getFallbackEngineDetails();

		return [
			'title' => tr('Rebuild Index'),
			'stat' => $stat['default']['counts'],
			'search_engine' => $engine,
			'search_version' => $version,
			'search_index' => $index,
			'fallback_search_set' => $fallbackEngine != null,
			'fallback_search_indexed' => ! empty($stat['fallback']),
			'fallback_search_engine' => isset($fallbackEngineName) ? $fallbackEngineName : '',
			'fallback_search_version' => isset($fallbackVersion) ? $fallbackVersion : '',
			'fallback_search_index' => isset($fallbackIndex) ? $fallbackIndex : '',
			'queue_count' => $unifiedsearchlib->getQueueCount(),
			'execution_time' => FormatterHelper::formatTime($timer->stop()),
			'memory_usage' => FormatterHelper::formatMemory(memory_get_usage()),
			'memory_peak_usage_before' => FormatterHelper::formatMemory($memory_peak_usage_before),
			'memory_peak_usage_after' => FormatterHelper::formatMemory(memory_get_peak_usage()),
			'num_queries' => ($num_queries_after - $num_queries_before),
			'log_file_browser' => $unifiedsearchlib->getLogFilename(1),
			'log_file_console' => $unifiedsearchlib->getLogFilename(2),
			'lastLogItemWeb' => $lastLogItem['web'] ?: tr('Unable to get info from log file.'),
			'lastLogItemConsole' => $lastLogItem['console'] ?: tr('Unable to get info from log file.'),
		];
	}

	function action_process_queue($input)
	{
		Services_Exception_Denied::checkGlobal('admin');

		$batch = $input->batch->int() ?: 0;

		$unifiedsearchlib = TikiLib::lib('unifiedsearch');
		$stat = null;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			@ini_set('max_execution_time', 0);
			@ini_set('memory_limit', -1);
			$stat = $unifiedsearchlib->processUpdateQueue($batch);
		}

		return [
			'title' => tr('Process Update Queue'),
			'stat' => $stat,
			'queue_count' => $unifiedsearchlib->getQueueCount(),
			'batch' => $batch,
		];
	}

	function action_lookup($input)
	{
		global $prefs;

		$smarty = TikiLib::lib('smarty');
		$smarty->loadPlugin('smarty_function_tracker_item_status_icon');

		try {
			$filter = $input->filter->none() ?: [];
			$format = $input->format->text() ?: '{title}';

			$lib = TikiLib::lib('unifiedsearch');

			if (! empty($filter['title']) && preg_match_all('/\{(\w+)\}/', $format, $matches)) {
				// formatted object_selector search results should also search in formatted fields besides the title
				$titleFilter = $filter['title'];
				unset($filter['title']);
				$query = $lib->buildQuery($filter);
				$query->filterContent($titleFilter, $matches[1]);
			} else {
				$query = $lib->buildQuery($filter);
			}

			$query->setOrder($input->sort_order->text() ?: 'title_asc');
			$query->setRange($input->offset->int(), $input->maxRecords->int() ?: $prefs['maxRecords']);

			$result = $query->search($lib->getIndex());

			$result->applyTransform(function ($item) use ($format, $smarty) {
				$transformed = [
					'object_type' => $item['object_type'],
					'object_id' => $item['object_id'],
					'parent_id' => $item['gallery_id'],
					'title' => preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($item, $format) {
						$key = $matches[1];
						if (isset($item[$key])) {
							// if this is a trackeritem we do not want only the name but also the trackerid listed when setting up a field
							// otherwise its hard to distingish which field that is if multiple tracker use the same fieldname
							// example: setup of trackerfield item-link: choose some fields from a list. currently this list show all fields of all trackers
							if ($item['object_type'] == 'trackerfield') {
								return $item[$key] . ' (Tracker-' . $item['tracker_id'] . ', Field-' . $item['object_id'] . ')';
							} else {
								return $item[$key];
							}
						} elseif ($format == '{title}') {
							return tr('empty');
						} else {
							return '';
						}
					}, $format),
				];
				if ($item['object_type'] == 'trackeritem') {
					$transformed['status_icon'] = smarty_function_tracker_item_status_icon(['item' => $item['object_id']], $smarty->getEmptyInternalTemplate());
				}
				return $transformed;
			});

			return [
				'title' => tr('Lookup Result'),
				'resultset' => $result,
			];
		} catch (Search_Elastic_TransportException $e) {
			throw new Services_Exception_NotAvailable('Search functionality currently unavailable.');
		} catch (Exception $e) {
			throw new Services_Exception_NotAvailable($e->getMessage());
		}
	}
}
