<?php
// (c) Copyright 2002-2011 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');
$inputConfiguration = array(
	'id' => '',
	'sort_mode' => '',
);

$access->check_feature('wikiplugin_list', 'feature_ajax', 'wikiplugin_customsearch');
if (empty($_POST['basedata'])) {
	echo '<p>' . tra('Error in search query') . '</p>';
	die;
} else {
	$data = $_POST['basedata'];
}
if (isset($_POST['adddata'])) {
	$dataappend = array();	
	$adddata = json_decode($_POST['adddata'], true);
	if (!empty($_POST['searchid'])) {
		$id = $_POST['searchid'];
	} else {
		$id = '0';
	}
	// setup AJAX pagination
	$offset_jsvar = "customsearch_offset_$id";
	$onclick .= "$('#customsearch_$id" . "').submit();return false;";
	$dataappend['pagination'] = "{pagination offset_jsvar=\"$offset_jsvar\" onclick=\"$onclick\"}";

	if (!empty($_POST['groups'])) {
		$groups = json_decode($_POST['groups'], true);
	} else {
		$groups = array();
	}
	if (!empty($_POST['textrangegroups'])) {
		$textrangegroups = json_decode($_POST['textrangegroups'], true);
	} else {
		$textrangegroups = array();
	}
	if (!empty($_POST['daterangegroups'])) {
		$daterangegroups = json_decode($_POST['daterangegroups'], true);
	} else {
		$datarangegroups = array();
	}
	if (isset($_SESSION["customsearch_$id"])) {
		unset($_SESSION["customsearch_$id"]);
	}
	if (!empty($_REQUEST["maxRecords"])) {
		$_SESSION["customsearch_$id"]["maxRecords"] = $_REQUEST["maxRecords"];
	}
	if (!empty($_REQUEST["sort_mode"])) {
		$_SESSION["customsearch_$id"]["sort_mode"] = $_REQUEST["sort_mode"];
	}
	if (!empty($_REQUEST["offset"])) {
		$_SESSION["customsearch_$id"]["offset"] = $_REQUEST["offset"];
	}
	foreach ($adddata as $fieldid => $d) {
		$config = $d['config'];
		$name = $d['name'];
		$value = $d['value'];

		// save values entered as defaults while session lasts
		if (!empty($value)) {
			$_SESSION["customsearch_$id"][$fieldid] = $value;
		}	

		if (empty($config['type'])) {
			$config['type'] = $name;
		}

		if ($config['_filter'] == 'language') {
			$filter = 'language';
		} elseif ($config['_filter'] == 'type') {
			$filter = 'type';
		} elseif ($config['_filter'] == 'categories' || $name == 'categories')  {
			$filter = 'categories';	
		} else {
			$filter = 'content'; //default	
		}

		if (is_array($value) && count($value > 1)) {
			$value = implode(' ', $value);
		} elseif (is_array($value)) {
			$value = current($value);
		}

		$function = "cs_dataappend_{$filter}";
		if (function_exists($function) && $line = $function($config, $value)) {
			$dataappend[$fieldid] .= $line;
		}
	}

	// Reconstruct using boolean OR for grouped filters
	$grouped = cs_get_grouped($dataappend, $groups);
	$grouping_keys = array('categories', 'content', 'language'); // only these can be grouped
	$to_reconstruct = cs_process_group($dataappend, $grouped, $id, $grouping_keys);
	cs_reconstruct_group($dataappend, $to_reconstruct, $grouped, $id, $grouping_keys);

	// Reconstruct textrange from-to filters
	$grouped = cs_get_grouped($dataappend, $textrangegroups);
	$grouping_keys = array('content');
	$to_reconstruct = cs_process_group($dataappend, $grouped, $id, $grouping_keys, 2, 2, false, true); 
	cs_reconstruct_rangegroup($dataappend, $to_reconstruct, $grouped, $id, $grouping_keys, 'text');

	// Reconstruct daterange from-to filters
	$grouped = cs_get_grouped($dataappend, $daterangegroups);
	$grouping_keys = array('content');
	$to_reconstruct = cs_process_group($dataappend, $grouped, $id, $grouping_keys, 2, 2, false, true);
	cs_reconstruct_rangegroup($dataappend, $to_reconstruct, $grouped, $id, $grouping_keys, 'date');

	// Finally combine base filters with appended filters
	foreach ($dataappend as $d) {
		$data .= $d;
	}
}

require_once('lib/wiki-plugins/wikiplugin_list.php'); 
$results = wikiplugin_list($data, array());
$results = $tikilib->parse_data($results, array('is_html' => true));
echo $results;

function cs_get_grouped($dataappend, $groups) {
	$grouped = array();
	foreach ($dataappend as $fieldid => $data) {
		if (isset($groups[$fieldid]) && !isset($groupedids[$groups[$fieldid]])) {
			$grouped[$groups[$fieldid]] = array_keys($groups, $groups[$fieldid]);
		}
	}
	return $grouped; 
}

function cs_process_group(&$dataappend, $grouped, $id, $grouping_keys, $min_match = 2, $max_match = 99, $checksimilar = true, $drop_if_no_match = false) {
	$parser = new WikiParser_PluginArgumentParser;
	$to_reconstruct = array();
	foreach ($grouped as $group_id => $grp) {
		if (count($grp) > 1) {
			$args = array();
			$args_checked = array(); // just for consistency checking
			$query_vals = array();
			foreach ($grp as $g) {
				$matches = WikiParser_PluginMatcher::match($dataappend[$g]);
				foreach ($matches as $match) {	
					if ($match->getName() != 'filter') {
						$query_vals = array();
						break 2;
					}
					$args = $parser->parse($match->getArguments());
					// double check that they are the same filter other than the query itself, to avoid errornous mixing
					if ($checksimilar) {
						$args_to_check = $args;
						foreach ($grouping_keys as $k) {
							unset($args_to_check[$k]);
						}
						if (!empty($args_checked) && $args_checked != $args_to_check) {
							$query_vals = array();
							break 2;
						} else {
							$args_checked = $args_to_check;
						}
					}
					foreach ($grouping_keys as $k) {
						if (array_key_exists($k, $args)) {
							$query_vals[] = $args[$k];
							break;
						}
					}
				}
			}
			if (count($query_vals) >= $min_match && count($query_vals) <= $max_match) {
				$to_reconstruct[$group_id] = array('args' => $args, 'query_vals' => $query_vals);
			} elseif ($drop_if_no_match) {
				foreach ($grouped[$group_id] as $to_drop) {
					unset($dataappend[$to_drop]);
				}
			}
		}
	}
	return $to_reconstruct;
}

function cs_reconstruct_group(&$dataappend, $to_reconstruct, $grouped, $id, $grouping_keys) {
	foreach ($to_reconstruct as $group_id => $recon) {
		$new_query_val = implode(' ', $recon['query_vals']);
		foreach ($grouping_keys as $k) {
			if (array_key_exists($k, $recon['args'])) {
				$recon['args'][$k] = $new_query_val;	
				break;
			}
		}
		foreach ($grouped[$group_id] as $to_drop) {
			unset($dataappend[$to_drop]);
		}
		$filter = '{filter ';
		foreach ($recon['args'] as $k => $v) {
			$filter .= $k . '="' . $v . '" ';
		}
		$filter .= '}';
		$dataappend["customsearch_$id" . "_gr$group_id"] = $filter;
	}
}

function cs_reconstruct_rangegroup(&$dataappend, $to_reconstruct, $grouped, $id, $grouping_keys, $mode = 'text') {
	foreach ($to_reconstruct as $group_id => $recon) {
		sort($recon['query_vals'], SORT_STRING); // Lucene is a string only engine
		$from = $recon['query_vals'][0];
		$to = $recon['query_vals'][1];
		if (!empty($recon['args']['field'])) {
			$field = $recon['args']['field'];
		} else {
			$field = 'content';
		}	
		foreach ($grouped[$group_id] as $to_drop) {
			unset($dataappend[$to_drop]);
		}
		if ($mode == 'date') {
			$filter = '{filter range="';
		} else {
			$filter = '{filter textrange="';
		}
		$filter .= $field . '" from="' . $from . '" to="' . $to . '"}';
		$dataappend["customsearch_$id" . "_$mode" . "range$group_id"] = $filter;
	}
}

function cs_dataappend_language($config, $value) {
	if ($config['type'] != 'text') {
		if (!empty($config['_value'])) {
			$value = $config['_value'];
			return '{filter language="' . $value . '"}';	
		} elseif ($value) {
			return '{filter language="' . $value . '"}';
		} else {
			return false;
		}
	}
	return false;
}

function cs_dataappend_type($config, $value) {
	if ($config['type'] != 'text') {
		if (!empty($config['_value'])) {
			$value = $config['_value'];
			return '{filter type="' . $value . '"}';
		} else {
			return false;
		}
	}
	return false;
}

function cs_dataappend_content($config, $value) {
	if ($value) { 
		if ($config['type'] == 'checkbox') {
			if (empty($config['_field'])) {
				return false;
			}
			if (!empty($config['_value'])) {
				if ($config['_value'] == 'n') {
					$config['_value'] = 'NOT y';
				}
				return '{filter content="' . $config['_value'] . '" field="' . $config['_field'] . '"}';
			} else {
				return '{filter content="y" field="' . $config['_field'] . '"}';
			}
		} elseif ($config['type'] == 'radio' && !empty($config['_value'])) {
			if (empty($config['_field'])) {
				return '{filter content="' . $config['_value'] . '"}';
			} else {
				return '{filter content="' . $config['_value'] . '" field="' . $config['_field'] . '"}';	
			}
		} else {
			// covers everything else including radio that have no _value set (use sent value)
			if (empty($config['_field'])) {
				return '{filter content="' . $value . '"}';	
			} else {
				return '{filter content="' . $value . '" field="' . $config['_field'] . '"}'; 
			}
		} 
	}
	return false;
}

function cs_dataappend_categories($config, $value) {
	if (isset($config['_filter']) && $config['_filter'] == 'categories' && $config['type'] != 'text') {
		if (!empty($config['_value'])) {
			$value = $config['_value'];
		}	
	} elseif (!isset($config['_style'])) {
		return false;	
	}
	if ($value) {
		if (isset($config['_deep']) && $config['_deep'] != 'n') {
			return '{filter deepcategories="' . $value . '"}';
		} else {
			return '{filter categories="' . $value . '"}';
		}
	}
	return false;
}
