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
$access->check_feature('feature_references');
$access->check_permission(['tiki_p_edit_references'], tra('Edit Library References'));

global $dbTiki;
$referenceslib = TikiLib::lib('references');

$getInput = function ($request, $key, $default = '') {
	return empty($request[$key]) ? $default : $request[$key];
};

$page = $getInput($_REQUEST, 'page');
$smarty->assign('page', $page);


$page_id = TikiLib::lib('tiki')->get_page_id_from_name($page);
$action = $getInput($_REQUEST, 'action');
$ref_id = $getInput($_REQUEST, 'referenceId');
$ref_auto_biblio_code = empty($_REQUEST['ref_auto_biblio_code']) ? 'off' : $_REQUEST['ref_auto_biblio_code'];
$ref_biblio_code = $getInput($_REQUEST, 'ref_biblio_code');
$ref_author = $getInput($_REQUEST, 'ref_author');
$ref_title = $getInput($_REQUEST, 'ref_title');
$ref_part = $getInput($_REQUEST, 'ref_part');
$ref_uri = $getInput($_REQUEST, 'ref_uri');
$ref_code = $getInput($_REQUEST, 'ref_code');
$ref_publisher = $getInput($_REQUEST, 'ref_publisher');
$ref_location = $getInput($_REQUEST, 'ref_location');
$ref_year = $getInput($_REQUEST, 'ref_year');
$ref_style = $getInput($_REQUEST, 'ref_style');
$ref_template = $getInput($_REQUEST, 'ref_template');

$find = empty($_REQUEST['find']) ? '' : $_REQUEST['find'];
$maxRecords = empty($_REQUEST['maxRecords']) ? 25 : $_REQUEST['maxRecords'];
$offset = empty($_REQUEST['offset']) ? 0 : $_REQUEST['offset'];

if (isset($_REQUEST['addreference'])) {
	$errors = [];

	if ($ref_auto_biblio_code !== 'on') {
		if ($ref_biblio_code == '') {
			$errors[] = 'Please enter Biblio Code.';
		}

		$exists = $referenceslib->check_lib_existence($ref_biblio_code);
		if ($exists > 0) {
			$errors[] = 'This reference already exists.';
		}
	}

	if (count($errors) < 1 && $access->checkCsrf()) {
		$id = $referenceslib->add_reference(
			null,
			$ref_biblio_code,
			$ref_author,
			$ref_title,
			$ref_part,
			$ref_uri,
			$ref_code,
			$ref_year,
			$ref_style,
			$ref_template,
			$ref_publisher,
			$ref_location
		);
		$record = $referenceslib->get_reference_from_id($id);
		$record = array_shift($record['data']);
		$record['success'] = true;
		$record['id'] = $record['ref_id'];
		if ($_REQUEST['response'] == 'json') {
			echo json_encode($record);
			return;
		}
		$cookietab = 1;
	} else {
		foreach ($errors as $error) {
			$msg .= tra($error);
		}
		if ($_REQUEST['response'] == 'json') {
			echo json_encode([
				'success' => false,
				'msg' => $msg
			]);
			return;
		}
		Feedback::error(['mes' => $msg]);
	}
}

if (isset($_REQUEST['editreference'])) {
	$errors = [];

	if ($ref_id == '') {
		$errors[] = 'Reference not found.';
	}
	if ($ref_biblio_code == '') {
		$errors[] = 'Please enter Biblio Code.';
	}
	if (isset($prefs['feature_library_references']) && $prefs['feature_library_references'] === 'y') {
		$currentlibreference = $referenceslib->get_reference_from_id($ref_id);
		$linkedreferences = $referenceslib->get_reference_from_code($currentlibreference['data'][0]['biblio_code']);

		foreach ($linkedreferences['data'] as $ref) {
			if (count($errors) < 1 && $access->checkCsrf()) {
				$referenceslib->edit_reference(
					$ref['ref_id'],
					$ref_biblio_code,
					$ref_author,
					$ref_title,
					$ref_part,
					$ref_uri,
					$ref_code,
					$ref_year,
					$ref_style,
					$ref_template,
					$ref_publisher,
					$ref_location
				);
				$cookietab = 1;
			} else {
				foreach ($errors as $error) {
					$msg .= tra($error);
				}
				Feedback::error(['mes' => $msg]);
			}
		}
	} else {
		if (count($errors) < 1 && $access->checkCsrf()) {
			$referenceslib->edit_reference(
				$ref_id,
				$ref_biblio_code,
				$ref_author,
				$ref_title,
				$ref_part,
				$ref_uri,
				$ref_code,
				$ref_year,
				$ref_style,
				$ref_template,
				$ref_publisher,
				$ref_location
			);
			$cookietab = 1;
		} else {
			foreach ($errors as $error) {
				$msg .= tra($error);
			}
			Feedback::error(['mes' => $msg]);
		}
	}
}

if (isset($_REQUEST['action']) && isset($ref_id)) {
	$errors = [];
	if (isset($prefs['feature_library_references']) && $prefs['feature_library_references'] === 'y') {
		$currentlibreference = $referenceslib->get_reference_from_id($ref_id);
		$linkedreferences = $referenceslib->get_reference_from_code($currentlibreference['data'][0]['biblio_code']);

		if (count($linkedreferences['data']) === 1) {
			if ($_REQUEST['action'] == 'delete' && $access->checkCsrfForm(tra('Delete reference?'))) {
				$referenceslib->remove_reference($ref_id);
			}
		} else {
			$errors[] = 'This library reference can not be deleted because is still being used in some pages, please unlink the reference from the pages first.';
			foreach ($errors as $error) {
				$msg .= tra($error);
			}
			Feedback::error(['mes' => $msg]);
		}
	} else {
		if ($_REQUEST['action'] == 'delete' && $access->checkCsrfForm(tra('Delete reference?'))) {
			$referenceslib->remove_reference($ref_id);
		}
	}
}

$references = $referenceslib->list_lib_references($find, $maxRecords, $offset);
$smarty->assign('references', $references['data']);
$smarty->assign('cant', $references['cant']);
if (! empty($ref_id)) {
	$currentlibreference = $referenceslib->get_reference_from_id($ref_id);
	if (isset($currentlibreference['data']) && isset($currentlibreference['data'][0]) && $currentlibreference['data'][0]['page_id'] == null) {
		$referenceInfo = $currentlibreference['data'][0];
		$smarty->assign('referenceinfo', $referenceInfo);
		$pageReferences = $referenceslib->get_references_from_biblio($currentlibreference['data'][0]['biblio_code']);
		if (! empty($pageReferences['data'])) {
			/** @var TikiLib $tikiLib */
			$tikiLib = TikiLib::lib('tiki');
			$pagesNames = [];
			foreach ($pageReferences['data'] as $pageReference) {
				if ($pageReference['page_id']) {
					$page = $tikiLib->get_page_name_from_id($pageReference['page_id']);
					if (! empty($page)) {
						$pagesNames[] = ['pageName' => $page];
					}
				}
			}
			$pagesNames = Perms::filter([ 'type' => 'wiki page' ], 'object', $pagesNames, ['object' => 'pageName'], ['view', 'wiki_view_ref']);
			$smarty->assign('pagereferences', $pagesNames);
		}
	}
} else {
	if (! empty($_REQUEST['addreference']) && count($errors) > 0) {
		$referenceInfo = [
			'biblio_code' => $ref_biblio_code,
			'author' => $ref_author,
			'title' => $ref_title,
			'part' => $ref_part,
			'uri' => $ref_uri,
			'code' => $ref_code,
			'year' => $ref_year,
			'publisher' => $ref_publisher,
			'location' => $ref_location,
			'style' => $ref_style,
			'template' => $ref_template
		];
		$smarty->assign('referenceinfo', $referenceInfo);
	}
}

if ($getInput($_REQUEST, 'details')) {
	$cookietab = '2';
} elseif ($getInput($_REQUEST, 'usage')) {
	$cookietab = '3';
}

$smarty->assign('find', $find);
$smarty->assign('maxRecords', $maxRecords);
$smarty->assign('offset', $offset);

// Display the template
$smarty->assign('mid', 'tiki-references.tpl');
$smarty->display('tiki.tpl');
