<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');

$access->check_permission('tiki_p_admin');

if (! empty($_REQUEST['submit'])) {
	$smarty->assign('searched', true);

	$trklib = TikiLib::lib('trk');
	$trackers = $trklib->list_trackers();
	$trackers = array_combine(
		array_map(
			function($tracker) {
				return $tracker['trackerId'];
			}, $trackers['data']
		),
		array_map(
			function($tracker) {
				return $tracker['name'];
			}, $trackers['data']
		)
	);

	$fields = [];
	foreach ($trackers as $trackerId => $trackerName) {
		$result = $trklib->list_tracker_fields($trackerId);
		$fields = array_merge($fields, $result['data']);
	}
	$permanentNames = array_map(function($field) {
		return $field['permName'];
	}, $fields);

	$suffixes = "/_(text|json|[a-z]{2}|exact|base|raw|[a-z]{2}_raw|creation_date|modification_date|freshness_days|names|paths|calitemid|recurrenceId|multi|plain|count|sum|unstemmed|n?desc|n?asc|base_n?desc|base_n?asc)$/";

	$results = [];

	if (! empty($_REQUEST['search']) && in_array('wiki_pages', $_REQUEST['search'])) {
		$smarty->assign('wiki_pages_checked', true);
		$wikilib = TikiLib::lib('wiki');
		$result = $wikilib->get_pages_contains('tracker_field_');
		foreach ($result['data'] as $page) {
			preg_match_all("/tracker_field_([a-z0-9_\-]+)/i", $page['data'], $matches);
			foreach ($matches[1] as $possiblePermName) {
				$possiblePermNameBase = preg_replace($suffixes, "", $possiblePermName);
				if (! in_array($possiblePermName, $permanentNames) && ! in_array($possiblePermNameBase, $permanentNames)) {
					$results[] = [
						'page' => $page['pageName'],
						'permanentName' => $possiblePermName
					];
				}
			}
		}
	}

	if (! empty($_REQUEST['search']) && in_array('tracker_fields', $_REQUEST['search'])) {
		$smarty->assign('tracker_fields_checked', true);
		foreach ($fields as $field) {
			preg_match_all("/tracker_field_([a-z0-9_\-]+)/i", $field['options'], $matches);
			foreach ($matches[1] as $possiblePermName) {
				$possiblePermNameBase = preg_replace($suffixes, "", $possiblePermName);
				if (! in_array($possiblePermName, $permanentNames) && ! in_array($possiblePermNameBase, $permanentNames)) {
					$results[] = [
						'trackerId' => $field['trackerId'],
						'trackerName' => $trackers[$field['trackerId']],
						'fieldId' => $field['fieldId'],
						'fieldName' => $field['name'],
						'permanentName' => $possiblePermName
					];
				}
			}
			if ($field['type'] == 'math') {
				$runner = Tracker_Field_Math::getRunner();
				$runner->setFormula($field['options_map']['calculation']);
				foreach ($runner->inspect() as $possiblePermName) {
					if (! is_string($possiblePermName)) {
						continue;
					}
					if (! in_array($possiblePermName, $permanentNames)) {
						$results[] = [
							'trackerId' => $field['trackerId'],
							'trackerName' => $trackers[$field['trackerId']],
							'fieldId' => $field['fieldId'],
							'fieldName' => $field['name'],
							'permanentName' => $possiblePermName
						];
					}
				}
			}
		}
	}

	$smarty->assign('results', $results);
} else {
	$smarty->assign('wiki_pages_checked', true);
	$smarty->assign('tracker_fields_checked', true);
}

// Display the template
$smarty->assign('mid', 'tiki-admin_orphaned.tpl');
$smarty->display("tiki.tpl");
