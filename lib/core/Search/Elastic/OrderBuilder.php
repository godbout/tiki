<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_OrderBuilder
{
	private $index;

	function __construct(Search_Elastic_Index $index = null)
	{
		$this->index = $index;
	}

	function build(Search_Query_Order $order)
	{
		$component = '_score';
		$field = $order->getField();

		if ($order->getMode() == Search_Query_Order::MODE_SCRIPT) {
			$arguments = $order->getArguments();

			$component = [
				"_script" => [
					'type'   => $arguments['type'],
					'script' => [
						'lang'   => $arguments['lang'],
						'source' => $arguments['source'],
					],
					'order'  => $arguments['order'],
				],
			];
		} else if ($field !== Search_Query_Order::FIELD_SCORE) {
			$this->ensureHasField($field);
			if ($order->getMode() == Search_Query_Order::MODE_NUMERIC) {
				$component = [
					"$field.nsort" => $order->getOrder(),
				];
			} elseif ($order->getMode() == Search_Query_Order::MODE_DISTANCE) {
				$arguments = $order->getArguments();

				$component = [
					"_geo_distance" => [
						'geo_point' => [
							'lat' => $arguments['lat'],
							'lon' => $arguments['lon'],
						],
						'order' => $order->getOrder(),
						'unit' => $arguments['unit'],
						'distance_type' => $arguments['distance_type'],
					],
				];
			} else {
				$component = [
					"$field.sort" => $order->getOrder(),
				];
			}
		}

		return [
			"sort" => [
				$component,
			],
		];
	}

	function ensureHasField($field)
	{
		global $prefs;

		$mapping = $this->index ? $this->index->getFieldMapping($field) : new stdClass;
		if ((empty($mapping) || empty((array)$mapping)) && $prefs['search_error_missing_field'] === 'y') {
			if (preg_match('/^tracker_field_/', $field)) {
				$msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', $field);
				if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
					$msg .= ' '.tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
				}
			} else {
				$msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', $field, $field);
			}
			$e = new Search_Elastic_QueryParsingException($msg);
			if ($field == 'tracker_id') {
				$e->suppress_feedback = true;
			}
			throw $e;
		}
	}
}
