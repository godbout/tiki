<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for Freetags
 *
 * Letter key: ~F~
 *
 */
class Tracker_Field_Freetags extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
	public static function getTypes()
	{
		return [
			'F' => [
				'name' => tr('Tags'),
				'description' => tr('Allow tags to be shown or added for tracker items.'),
				'prefs' => ['trackerfield_freetags', 'feature_freetags'],
				'tags' => ['advanced'],
				'default' => 'y',
				'params' => [
					'size' => [
						'name' => tr('Size'),
						'description' => tr('Visible size of the input field'),
						'filter' => 'int',
						'legacy_index' => 0,
					],
					'hidehelp' => [
						'name' => tr('Help'),
						'description' => tr('Hide or show the input help'),
						'default' => '',
						'filter' => 'alpha',
						'options' => [
							'' => tr('Show'),
							'y' => tr('Hide'),
						],
						'legacy_index' => 1,
					],
					'hidesuggest' => [
						'name' => tr('Suggest'),
						'description' => tr('Hide or show the tag suggestions'),
						'default' => '',
						'filter' => 'alpha',
						'options' => [
							'' => tr('Show'),
							'y' => tr('Hide'),
							'all' => tr('Show them all, ordered by popularity'),
						],
						'legacy_index' => 2,
					],
				],
			],
		];
	}

	function getFieldData(array $requestData = [])
	{
		$data = [];

		$ins_id = $this->getInsertId();

		if (isset($requestData[$ins_id])) {
			$data['value'] = $requestData[$ins_id];
		} else {
			global $prefs;

			$data['value'] = $this->getValue();

			$langutil = new Services_Language_Utilities;
			$itemLang = null;
			if ($this->getItemId()) {
				try {
					$itemLang = $langutil->getLanguage('trackeritem', $this->getItemId());
				} catch (Services_Exception $e) {
					$itemLang = null;
				}
			}
			$freetaglib = TikiLib::lib('freetag');
			$data['freetags'] = $freetaglib->_parse_tag($data['value']);
			if ($this->getOption('hidesuggest') == '') {
				$data['tag_suggestion'] = $freetaglib->get_tag_suggestion(
					implode(' ', $data['freetags']),
					$prefs['freetags_browse_amount_tags_suggestion'],
					$itemLang
				);
			} else {
				$data['all_tags'] = $freetaglib->silly_list(-1);
			}
		}

		return $data;
	}

	function addValue($value) {
		$freetaglib = TikiLib::lib('freetag');
		$tags = $freetaglib->_parse_tag($this->getValue());
		if (! in_array($value, $tags)) {
			$tags[] = $value;
		}
		return implode(' ', array_map(function($t){
			return strstr($t, ' ') ? '"'.$t.'"' : $t;
		}, $tags));
	}

	function removeValue($value) {
		$freetaglib = TikiLib::lib('freetag');
		$tags = $freetaglib->_parse_tag($this->getValue());
		$tags = array_filter($tags, function($t) use ($value) {
			return $t != $value;
		});
		return implode(' ', array_map(function($t){
			return strstr($t, ' ') ? '"'.$t.'"' : $t;
		}, $tags));
	}

	function renderInput($context = [])
	{
		return $this->renderTemplate('trackerinput/freetags.tpl', $context);
	}

	function renderOutput($context = [])
	{
		return $this->renderTemplate('trackeroutput/freetags.tpl', $context);
	}

	function importRemote($value)
	{
		return $value;
	}

	function exportRemote($value)
	{
		return $value;
	}

	function importRemoteField(array $info, array $syncInfo)
	{
		return $info;
	}

	function getTabularSchema()
	{
		$schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');

		$schema->addNew($permName, 'default')
			->setLabel($name)
			->setRenderTransform(function ($value) {
				return $value;
			})
			->setParseIntoTransform(function (& $info, $value) use ($permName) {
				$info['fields'][$permName] = $value;
			});

		return $schema;
	}

	function getFilterCollection()
	{
		$filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');
		$baseKey = $this->getBaseKey();


		$filters->addNew($permName, 'lookup')
			->setLabel($name)
			->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_lookup"))
			->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
				$value = $control->getValue();

				if ($value) {
					$query->filterContent($value, $baseKey);
				}
			})
			;

		return $filters;
	}
}
