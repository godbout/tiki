<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\WikiPlugin\Reference;

function wikiplugin_showreference_info()
{
	return [
		'name' => tra('Add Bibliography'),
		'documentation' => 'PluginShowReference',
		'description' => tra('Add bibliography listing in the footer of a wiki page'),
		'format' => 'html',
		'iconname' => 'list',
		'introduced' => 10,
		'prefs' => ['wikiplugin_showreference','feature_references'],
		'params' => [
			'title' => [
				'required' => false,
				'name' => tra('Title'),
				'description' => tr(
					'Title to be displayed in the bibliography listing. Default is %0Bibliography%1.',
					'<code>',
					'</code>'
				),
				'since' => '10.0',
				'default' => 'Bibliography',
				'filter' => 'text',
			],
			'showtitle' => [
				'required' => false,
				'name' => tra('Show Title'),
				'description' => tra('Show bibliography title. Title is shown by default.'),
				'since' => '10.0',
				'filter' => 'word',
				'options' => [
					['text' => tra(''), 'value' => ''],
					['text' => tra('Yes'), 'value' => 'yes'],
					['text' => tra('No'), 'value' => 'no'],
				],
				'default' => '',
			],
			'hlevel' => [
				'required' => false,
				'name' => tra('Header Tag'),
				'description' => tr('The HTML header tag level of the title. Default: %01%1', '<code>', '</code>'),
				'since' => '10.0',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => '0', 'value' => '0'],
					['text' => '1', 'value' => '1'],
					['text' => '2', 'value' => '2'],
					['text' => '3', 'value' => '3'],
					['text' => '4', 'value' => '4'],
					['text' => '5', 'value' => '5'],
					['text' => '6', 'value' => '6'],
					['text' => '7', 'value' => '7'],
					['text' => '8', 'value' => '8'],
				],
				'filter' => 'digits',
				'default' => '',
			],
			'removelines' => [
				'required' => false,
				'name' => tra('Remove the lines around the list of references'),
				'description' => tr('Remove the horizontal lines displayed above and below the list of references.'),
				'since' => '18.0',
				'options' => [
					['text' => tra(''), 'value' => ''],
					['text' => tra('Yes'), 'value' => 'yes'],
					['text' => tra('No'), 'value' => 'no'],
				],
				'default' => '',
			],
			// Add new parameter pageid
			'pageid' => [
				'required' => false,
				'name' => tra('Page identifier'),
				'description' => tr('Provide the page id'),
				'since' => '18.0',
				'default' => '',
			],
		],
	];
}

function wikiplugin_showreference($data, $params)
{

	global $prefs;

	$referenceStyle = (! empty($prefs['feature_references_style']) && $prefs['feature_references_style'] === 'mla') ? 'mla' : 'ama';

	$params['title'] = empty($params['title']) ? '' : trim($params['title']);
	$params['hlevel'] = empty($params['hlevel']) ? '' : trim($params['hlevel']);
	$params['removelines'] = empty($params['removelines']) ? '' : trim($params['removelines']);
	$params['pageid'] = empty($params['pageid']) ? '' : trim($params['pageid']);

	$title = empty($params['title']) ? tr('Bibliography') : $params['title'];
	$showtitle = empty($params['showtitle']) || trim($params['showtitle']) !== 'no';

	if (isset($params['hlevel']) && $params['hlevel'] != '') {
		if ($params['hlevel'] != '0') {
			$hlevel_start = '<h' . $params['hlevel'] . '>';
			$hlevel_end = '</h' . $params['hlevel'] . '>';
		} else {
			$hlevel_start = '<p>';
			$hlevel_end = '</p>';
		}
	} else {
		$hlevel_start = '<p>';
		$hlevel_end = '</p>';
	}

	if ($prefs['wikiplugin_showreference'] == 'y') {
		// Check first if the param pageid is passed.
		// If not then check the global info:page_id
		if (strlen($params['pageid']) == 0) {
			if (empty($GLOBALS['info']) || empty($GLOBALS['info']['page_id'])) {
				return 'error';
			} else {
				$page_id = $GLOBALS['info']['page_id'];
			}
		} else {
			$page_id = $params['pageid'];
		}

		$tags = Reference::getTagsToParse();

		$htm = '';

		$referenceslib = TikiLib::lib('references');
		$references = $referenceslib->list_assoc_references($page_id);

		// Return empty html if no references are associated
		if (count($references['data']) == 0) {
			return '';
		}

		$referencesData = [];
		$is_global = 1;
		if (isset($GLOBALS['referencesData']) && is_array($GLOBALS['referencesData'])) {
			$referencesData = $GLOBALS['referencesData'];
			$is_global = 1;
		} else {
			foreach ($references['data'] as $data) {
				array_push($referencesData, $data['biblio_code']);
			}
			$is_global = 0;
		}

		if (is_array($referencesData)) {
			$referencesData = array_unique($referencesData);

			$htm .= '<div class="references">';

			if ($showtitle) {
				$htm .= $hlevel_start . $title . $hlevel_end;
			}

			if ($params['removelines'] !== 'yes') {
				$htm .= '<hr>';
			}

			$htm .= '<ul style="list-style: none outside none;">';

			if (count($referencesData)) {
				$values = $referenceslib->get_reference_from_code_and_page($referencesData, $page_id);
			} else {
				$values = [];
			}

			if ($is_global) {
				$excluded = [];
				foreach ($references['data'] as $key => $value) {
					if (! array_key_exists($key, $values['data'])) {
						$excluded[$key] = $references['data'][$key]['biblio_code'];
					}
				}
				foreach ($excluded as $ex) {
					array_push($referencesData, $ex);
				}
			}

			foreach ($referencesData as $index => $ref) {
				$ref_no = $index + 1;

				$text = '';
				$cssClass = '';
				if (array_key_exists($ref, $values['data'])) {
					if ($values['data'][$ref]['style'] != '') {
						$cssClass = $values['data'][$ref]['style'];
					}

					$text = Reference::parseTemplate($tags, $ref, $values['data'], $referenceStyle);
				} else {
					if (array_key_exists($ref, $excluded)) {
						$text = Reference::parseTemplate($tags, $ref, $references['data'], $referenceStyle);
					}
				}
				$anchor = "<a name='" . $ref . "'>&nbsp;</a>";
				if ($referenceStyle === 'mla') {
					if (strlen($text)) {
						$htm .= "<li class='" . $cssClass . "'>" . $text . $anchor . '</li>';
					} else {
						$htm .= "<li class='" . $cssClass . "' style='font-style:italic'>" .
							$ref . ': ' . tr('missing bibliography definition') . $anchor .
							'</li>';
					}
				} else {
					if (strlen($text)) {
						$htm .= "<li class='" . $cssClass . "'>" . $ref_no . ". " . $text . $anchor . '</li>';
					} else {
						$htm .= "<li class='" . $cssClass . "' style='font-style:italic'>" .
							$ref_no . '. ' . tr('missing bibliography definition') . $anchor .
							'</li>';
					}
				}
			}

			$htm .= '</ul>';

			if ($params['removelines'] !== 'yes') {
				$htm .= '<hr>';
			}

			$htm .= '</div>';
		}
		return $htm;
	}
	return "not showing plugin";
}
