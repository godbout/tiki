<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\WikiPlugin\Reference;

function wikiplugin_addreference_info()
{
	return [
		'name' => tra('Add Reference'),
		'description' => tra('Add a bibliography reference.'),
		'format' => 'html',
		'introduced' => 10,
		'prefs' => ['wikiplugin_addreference', 'feature_references'],
		'iconname' => 'pencil',
		'params' => [
			'biblio_code' => [
				'required' => true,
				'name' => tra('Biblio Code'),
				'description' => tra('The code to be added as reference.'),
				'default' => '',
				'since' => '10.0',
				'filter' => 'word',
				'multiple' => true,
				'separator' => ',',
			],
		],
	];
}

function wikiplugin_addreference($data, $params)
{
	global $prefs;

	if ($prefs['wikiplugin_addreference'] == 'y') {
		/** @var ReferencesLib $referenceslib */
		$referenceslib = TikiLib::lib('references');

		if (! isset($GLOBALS['referencesData'])) {
			$GLOBALS['referencesData'] = [];
		}

		$data = trim($data);

		if (strstr($_SERVER['SCRIPT_NAME'], 'tiki-print.php')) {
			$page = urldecode($_REQUEST['page']);
			$page_id = TikiLib::lib('tiki')->get_page_id_from_name($page);
			$page_info = TikiLib::lib('tiki')->get_page_info($page);
		} else {
			$object = current_object();
			$page_id = TikiLib::lib('tiki')->get_page_id_from_name($object['object']);
			$page_info = TikiLib::lib('tiki')->get_page_info($object['object']);
		}

		if (empty($params['biblio_code']) || (is_array($params['biblio_code']) && count($params['biblio_code']) == 0)) {
			return;
		}
		if (! is_array($params['biblio_code'])) {
			$params['biblio_code'] = [$params['biblio_code']];
		}
		$cleanBiblioCode = [];
		foreach ($params['biblio_code'] as $code) {
			$code = Reference::trimBibliographicCode($code);
			if (! empty($code)) {
				$cleanBiblioCode[] = $code;
			}
		}
		$params['biblio_code'] = $cleanBiblioCode;

		extract($params, EXTR_SKIP);

		$matchedReferences = Reference::extractBibliographicCodesFromText($page_info['data']);

		$temp = [];
		$curr_matches = [];
		$temp = array_unique($matchedReferences);
		$i = 0;
		foreach ($temp as $k => $v) {
			if (strlen(trim($v)) > 0) {
				$curr_matches[$i] = $v;
				$i++;
			}
		}
		unset($temp);

		$matchedRecords = [];
		foreach ($params['biblio_code'] as $code) {
			$index = 0;
			foreach ($curr_matches as $key => $val) {
				if (strlen(trim($val)) > 0) {
					if ($val == $code) {
						$index = $key + 1;
						break;
					}
				}
			}
			$matchedRecords[] = [
				'biblio_code' => $code,
				'index' => $index,
			];
		}

		$GLOBALS['referencesData'] = $curr_matches;

		$referenceStyle = Reference::getCitationStyle();
		$displayPopover = (! empty($prefs['feature_references_popover']) && $prefs['feature_references_popover'] === 'y');

		$referenceOutputList = [];
		foreach ($matchedRecords as $biblioCode) {
			$url = $GLOBALS['base_uri'] . "#" . $biblioCode['biblio_code'];

			if ($displayPopover || $referenceStyle == Reference::STYLE_MLA) {
				$referenceList = $referenceslib->get_reference_from_code_and_page(
					[$biblioCode['biblio_code']],
					$page_id
				);
			}

			$extraTitle = "";
			$class = "";
			if ($displayPopover) {
				$referenceText = Reference::parseTemplate(
					Reference::getTagsToParse(),
					$biblioCode['biblio_code'],
					$referenceList['data'],
					$referenceStyle
				);
				if (empty($referenceText)) {
					$referenceText = $biblioCode['biblio_code'] . ': ' . tr('missing bibliography definition');
				}
				$baseLink = "<a href=&#039;" . $url . "&#039; title=&#039;" . $biblioCode['biblio_code'] . "&#039;>" . $referenceText . "</a>";
				$extraTitle = '|' . $baseLink;
				$class = "class='tips'";
			}

			if (! empty($biblioCode['biblio_code']) && $referenceStyle == Reference::STYLE_MLA) {
				if (empty($referenceList['data'][$biblioCode['biblio_code']])) {
					$referenceOutputList[] = "<a href='" . $url . "' " . $class . " title='" . $biblioCode['biblio_code'] . $extraTitle . "'>" . $biblioCode['biblio_code'] . "</a>";
				} else {
					$reference = $referenceList['data'][$biblioCode['biblio_code']];
					$authorTemp = preg_split('/[\W]+/', $reference['author']);
					$author = empty($authorTemp[0]) ? '' : $authorTemp[0];
					$sep = (empty($author) || empty($reference['year'])) ? '' : ' ';

					$referenceOutputList[] = "<a href='" . $url . "' " . $class . " title='" . $biblioCode['biblio_code'] . $extraTitle . "'>" . $author . $sep . $reference['year'] . "</a>";
				}
			} else {
				$referenceOutputList[] = "<a href='" . $url . "' " . $class . " title='" . $biblioCode['biblio_code'] . $extraTitle . "'><sup>" . $biblioCode['index'] . "</sup></a>";
			}
		}

		if (! empty($prefs['feature_references_style']) && $prefs['feature_references_style'] === 'mla') {
			$data .= '(' . implode(', ', $referenceOutputList) . ')';
		} else {
			$data .= implode('<sup>,</sup> ', $referenceOutputList);
		}

		return $data;
	}
}
