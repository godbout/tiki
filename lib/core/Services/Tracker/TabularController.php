<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Tracker_TabularController
{
	function setUp()
	{
		Services_Exception_Disabled::check('tracker_tabular_enabled');
	}

	function action_manage($input)
	{
		Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

		$lib = TikiLib::lib('tabular');

		return [
			'title' => tr('Tabular Formats'),
			'list' => $lib->getList(),
		];
	}

	function action_delete($input)
	{
		$tabularId = $input->tabularId->int();

		Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $tabularId);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$lib = TikiLib::lib('tabular');
			$lib->remove($tabularId);
		}

		return [
			'title' => tr('Remove Format'),
			'tabularId' => $tabularId,
		];
	}

	function action_create($input)
	{
		Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$lib = TikiLib::lib('tabular');

			$tabularId = $lib->create($input->name->text(), $input->trackerId->int());

			return [
				'FORWARD' => [
					'controller' => 'tabular',
					'action' => 'edit',
					'tabularId' => $tabularId,
				],
			];
		}

		return [
			'title' => tr('Create Tabular Format'),
		];
	}

	function action_edit($input)
	{
		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($input->tabularId->int());
		$trackerId = $info['trackerId'];

		Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $info['tabularId']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$info['format_descriptor'] = json_decode($input->fields->none(), true);
			$info['filter_descriptor'] = json_decode($input->filters->none(), true);
			$schema = $this->getSchema($info);

			// FIXME : Blocks save and back does not restore changes, ajax validation required
			// $schema->validate();

			$lib->update($info['tabularId'], $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $input->config->none());

			return [
				'FORWARD' => [
					'controller' => 'tabular',
					'action' => 'manage',
				],
			];
		}

		$schema = $this->getSchema($info);

		return [
			'title' => tr('Edit Format: %0', $info['name']),
			'tabularId' => $info['tabularId'],
			'trackerId' => $info['trackerId'],
			'name' => $info['name'],
			'config' => $info['config'],
			'schema' => $schema,
			'filterCollection' => $schema->getFilterCollection(),
		];
	}

	/**
	 * Copy one format to another new one
	 *
	 * @param JitFilter $input
	 *
	 * @return array
	 * @throws Services_Exception_Denied
	 * @throws Services_Exception_NotFound
	 */
	function action_duplicate($input)
	{
		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($input->tabularId->int());

		if (! $info) {
			throw new Services_Exception_NotFound(tr('Format %0 not found', $input->tabularId->int()));
		}

		Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $info['tabularId']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$schema = $this->getSchema($info);

			$tabularId = $lib->create($input->name->text(), $info['trackerId']);

			$lib->update($tabularId, $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $info['config']);

			$referer = Services_Utilities::noJsPath();
			return Services_Utilities::refresh($referer);
		}

		return [
			'title' => tr('Duplicate Format: %0', $info['name']),
			'tabularId' => $info['tabularId'],
			'name' => tr('%0 copy', $info['name']),
		];
	}

	function action_select($input)
	{
		$permName = $input->permName->word();
		$trackerId = $input->trackerId->int();

		$tracker = \Tracker_Definition::get($trackerId);

		if (! $tracker) {
			throw new Services_Exception_NotFound;
		}

		Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);

		$schema = new \Tracker\Tabular\Schema($tracker);
		$local = $schema->getFieldSchema($permName);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$column = $schema->addColumn($permName, $input->mode->text());

			$return = [
				'field' => $column->getField(),
				'mode' => $column->getMode(),
				'label' => $column->getLabel(),
				'isReadOnly' => $column->isReadOnly(),
				'isPrimary' => $column->isPrimaryKey(),
			];
			if ($input->offsetExists('columnIndex')) {
				$return['columnIndex'] = $input->columnIndex->int();
			}

			return $return;
		}

		$return = [
			'title' => tr('Fields in %0', $tracker->getConfiguration('name')),
			'trackerId' => $trackerId,
			'permName' => $permName,
			'schema' => $local,
		];
		if ($input->offsetExists('columnIndex')) {
			$return['columnIndex'] = $input->columnIndex->int();
		}
		if ($input->offsetExists('mode')) {
			$return['mode'] = $input->mode->text();
		}
		return $return;
	}

	function action_select_filter($input)
	{
		$permName = $input->permName->word();
		$trackerId = $input->trackerId->int();

		$tracker = \Tracker_Definition::get($trackerId);

		if (! $tracker) {
			throw new Services_Exception_NotFound;
		}

		Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);

		$schema = new \Tracker\Filter\Collection($tracker);
		$local = $schema->getFieldCollection($permName);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$column = $schema->addFilter($permName, $input->mode->text());
			return [
				'field' => $column->getField(),
				'mode' => $column->getMode(),
				'label' => $column->getLabel(),
			];
		}

		return [
			'title' => tr('Fields in %0', $tracker->getConfiguration('name')),
			'trackerId' => $trackerId,
			'permName' => $permName,
			'collection' => $local,
		];
	}

	function action_export_full_csv($input)
	{
		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($input->tabularId->int());

		Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $info['tabularId']);

		$schema = $this->getSchema($info);
		$schema->validate();

		$source = new \Tracker\Tabular\Source\TrackerSource($schema);
		$writer = new \Tracker\Tabular\Writer\CsvWriter('php://output');

		$name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
		$writer->sendHeaders($name . '_export_full.csv');

		TikiLib::lib('tiki')->allocate_extra(
			'tracker_export_items',
			function () use ($writer, $source) {
				$writer->write($source);
			}
		);
		exit;
	}

	function action_export_partial_csv($input)
	{
		$tabularId = $input->tabularId->int();

		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($tabularId);
		$trackerId = $info['trackerId'];

		Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $tabularId);

		$schema = $this->getSchema($info);
		$collection = $schema->getFilterCollection();

		$collection->applyInput($input);

		if ($_SERVER['REQUEST_METHOD'] == 'POST' || $input->confirm->word() === 'export') {
			$search = TikiLib::lib('unifiedsearch');
			$query = $search->buildQuery([
				'type' => 'trackeritem',
				'tracker_id' => $trackerId,
			]);

			$collection->applyConditions($query);

			$source = new \Tracker\Tabular\Source\QuerySource($schema, $query);
			$writer = new \Tracker\Tabular\Writer\CsvWriter('php://output');

			$name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
			$writer->sendHeaders($name . '_export_partial.csv');

			TikiLib::lib('tiki')->allocate_extra(
				'tracker_export_items',
				function () use ($writer, $source) {
					$writer->write($source);
				}
			);
			exit;
		}

		return [
			'FORWARD' => [
				'controller' => 'tabular',
				'action' => 'filter',
				'tabularId' => $tabularId,
				'target' => 'export',
			],
		];
	}

	function action_export_search_csv($input)
	{
		$lib = TikiLib::lib('tabular');
		$trackerId = $input->trackerId->int();
		$tabularId = $input->tabularId->int();
		$conditions = array_filter([
			'trackerId' => $trackerId,
			'tabularId' => $tabularId,
		]);

		$formats = $lib->getList($conditions);

		if ($tabularId) {
			$info = $lib->getInfo($tabularId);
			$schema = $this->getSchema($info);
			$schema->validate();

			$trackerId = $info['trackerId'];

			Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $tabularId);

			$search = TikiLib::lib('unifiedsearch');
			$query = $search->buildQuery($input->filter->none() ?: []);

			// Force filters
			$query->filterType('trackeritem');
			$query->filterContent($trackerId, 'tracker_id');

			$source = new \Tracker\Tabular\Source\QuerySource($schema, $query);
			$writer = new \Tracker\Tabular\Writer\CsvWriter('php://output');

			$name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
			$writer->sendHeaders($name . '_export_search.csv');

			TikiLib::lib('tiki')->allocate_extra(
				'tracker_export_items',
				function () use ($writer, $source) {
					$writer->write($source);
				}
			);
			exit;
		} elseif (count($formats) === 0) {
			throw new Services_Exception(tr('No formats available.'));
		} else {
			if ($trackerId) {
				Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);
			} else {
				Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');
			}

			return [
				'title' => tr('Select Format'),
				'formats' => $formats,
				'filters' => $input->filter->none(),
			];
		}
	}

	function action_import_csv($input)
	{
		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($input->tabularId->int());
		$trackerId = $info['trackerId'];

		Services_Exception_Denied::checkObject('tiki_p_tabular_import', 'tabular', $info['tabularId']);

		$schema = $this->getSchema($info);
		$schema->validate();

		if (! $schema->getPrimaryKey()) {
			throw new Services_Exception_NotAvailable(tr('Primary Key required'));
		}

		$done = false;

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && is_uploaded_file($_FILES['file']['tmp_name'])) {
			$source = new \Tracker\Tabular\Source\CsvSource($schema, $_FILES['file']['tmp_name']);
			$writer = new \Tracker\Tabular\Writer\TrackerWriter;
			$done = $writer->write($source);

			unlink($_FILES['file']['tmp_name']);
		}

		return [
			'title' => tr('Import'),
			'tabularId' => $info['tabularId'],
			'completed' => $done,
		];
	}

	function action_filter($input)
	{
		$tabularId = $input->tabularId->int();

		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($tabularId);
		$trackerId = $info['trackerId'];

		Services_Exception_Denied::checkObject('tiki_p_tabular_list', 'tabular', $tabularId);

		$schema = $this->getSchema($info);
		$collection = $schema->getFilterCollection();

		$collection->applyInput($input);

		$search = TikiLib::lib('unifiedsearch');
		$query = $search->buildQuery([
			'type' => 'trackeritem',
			'tracker_id' => $trackerId,
		]);
		$query->setRange(1);
		$collection->applyConditions($query);
		$resultset = $query->search($search->getIndex());
		$collection->setResultSet($resultset);

		$target = $input->target->word();

		if ($target == 'list') {
			$title = tr('Filter %0', $info['name']);
			$method = 'get';
			$action = 'list';
			$label = tr('Filter');
		} elseif ($target = 'export') {
			$title = tr('Export %0', $info['name']);
			$method = 'post';
			$action = 'export_partial_csv';
			$label = tr('Export');
		} else {
			throw new Services_Exception_NotFound;
		}

		return [
			'title' => $title,
			'tabularId' => $tabularId,
			'method' => $method,
			'action' => $action,
			'label' => $label,
			'filters' => array_map(function ($filter) {
				if (! $filter->getControl()->isUsable()) {
					return false;
				}
				return [
					'id' => $filter->getControl()->getId(),
					'label' => $filter->getLabel(),
					'help' => $filter->getHelp(),
					'control' => $filter->getControl(),
				];
			}, $collection->getFilters()),
		];
	}

	function action_list($input)
	{
		$tabularId = $input->tabularId->int();

		$lib = TikiLib::lib('tabular');
		$info = $lib->getInfo($tabularId);
		$trackerId = $info['trackerId'];

		Services_Exception_Denied::checkObject('tiki_p_tabular_list', 'tabular', $tabularId);

		$schema = $this->getSchema($info);
		$collection = $schema->getFilterCollection();

		$collection->applyInput($input);

		$search = TikiLib::lib('unifiedsearch');
		$query = $search->buildQuery([
			'type' => 'trackeritem',
			'tracker_id' => $trackerId,
		]);
		$query->setRange($input->offset->int());

		$collection->applyConditions($query);

		$source = new \Tracker\Tabular\Source\PaginatedQuerySource($schema, $query);
		$writer = new \Tracker\Tabular\Writer\HtmlWriter();

		$columns = array_values(array_filter($schema->getColumns(), function ($c) {
			return ! $c->isExportOnly();
		}));
		$arguments = $collection->getQueryArguments();

		$collection->setResultSet($source->getResultSet());

		$template = ['controls' => [], 'usable' => false, 'selected' => false];
		$filters = ['default' => $template, 'primary' => $template, 'side' => $template];
		foreach ($collection->getFilters() as $filter) {
			// Exclude unusable controls
			if (! $filter->getControl()->isUsable()) {
				continue;
			}

			$pos = $filter->getPosition();

			$filters[$pos]['controls'][] = [
				'id' => $filter->getControl()->getId(),
				'label' => $filter->getLabel(),
				'help' => $filter->getHelp(),
				'control' => $filter->getControl(),
				'description' => $filter->getControl()->getDescription(),
				'selected' => $filter->getControl()->hasValue(),
			];

			$filters[$pos]['usable'] = true;
			if ($filter->getControl()->hasValue()) {
				$filters[$pos]['selected'] = true;
			}
		}

		return [
			'title' => tr($info['name']),
			'tabularId' => $tabularId,
			'filters' => $filters,
			'columns' => $columns,
			'data' => $writer->getData($source),
			'resultset' => $source->getResultSet(),
			'baseArguments' => $arguments,
		];
	}

	function action_create_tracker($input)
	{
		global $tikilib;

		$tabularlib = TikiLib::lib('tabular');
		Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

		$headerlib = TikiLib::lib('header');
		$headerlib->add_jsfile('vendor_bundled/vendor/plotly/plotly.js/dist/plotly-basic.min.js', true);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Create a tracker
			$trackerUtilities = new Services_Tracker_Utilities();
			$trackerData = [
				'name' => $input->tracker_name->text(),
				'description' => '',
				'descriptionIsParsed' => 'n'
			];
			$trackerId = $trackerUtilities->createTracker($trackerData);

			$fieldDescriptor = json_decode($input->fields->none(), true);

			$types = $trackerUtilities->getFieldTypes();

			foreach ($fieldDescriptor as $key => $field) {
				$fieldName = $field['label'];
				$fieldType = $field['type'];
				$typeInfo = $types[$fieldType];

				// Populate default field options
				$options = $trackerUtilities->parseOptions("{}", $typeInfo);
				$options = $trackerUtilities->buildOptions($options, $fieldType);

				$fieldData = [
					'trackerId' => $trackerId,
					'name' => $fieldName,
					'type' => $fieldType,
					'isMandatory' => ($field['isPrimary'] || $field['isUniqueKey']) ? true : false,
					'description' => '',
					'descriptionIsParsed' => '',
					'permName' => null,
					'options' => $options,
				];

				$fieldId = $trackerUtilities->createField($fieldData);

				$fieldDescriptor[$key]['field'] = ! empty($fieldData['permName']) ? $fieldData['permName'] : 'f_' . $fieldId;
				$fieldDescriptor[$key]['mode'] = $this->getFieldTypeDefaultMode($fieldType);

				// Force reload (number of fields and existing fields in tracker)
				if (isset(Tracker_Definition::$definitions[$trackerId])) {
					unset(Tracker_Definition::$definitions[$trackerId]);
				}
			}

			// Create tabular tracker
			$tabularId = $tabularlib->create($input->name->text(), $trackerId);

			$info = $tabularlib->getInfo($tabularId);

			$info['format_descriptor'] = $fieldDescriptor;
			$info['filter_descriptor'] = [];
			$info['config'] = $input->config->none();
			$schema = $this->getSchema($info);

			$tabularlib->update($info['tabularId'], $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $info['config']);

			// Import the loaded file

			// Force reload schema
			unset(Tracker_Definition::$definitions[$trackerId]);
			$schema = $this->getSchema($info);
			$schema->validate();

			if (! $schema->getPrimaryKey()) {
				Feedback::error(tr('Primary Key required'));
				return [
					'FORWARD' => [
						'controller' => 'tabular',
						'action' => 'edit',
						'tabularId' => $tabularId,
					],
				];
			}

			$done = false;

			if (is_uploaded_file($_FILES['file']['tmp_name'])) {
				try {
					$delimiter = $input->delimiter->text() == 'comma' ? ',' : ';';
					$source = new \Tracker\Tabular\Source\CsvSource($schema, $_FILES['file']['tmp_name'], $delimiter);
					$writer = new \Tracker\Tabular\Writer\TrackerWriter;
					$done = $writer->write($source);

					unlink($_FILES['file']['tmp_name']);
				} catch (Exception $e) {
					Feedback::error($e->getMessage());

					// Rollback changes
					$tabularlib->remove($tabularId);
					$trackerUtilities->removeTracker($trackerId);
				}
			}

			if ($done) {
				Feedback::success(tr('Your import was completed successfully.'));
				return [
					'FORWARD' => [
						'controller' => 'tabular',
						'action' => 'list',
						'tabularId' => $info['tabularId'],
					]
				];
			}
		}

		$uploadMaxFileSize = $tikilib->return_bytes(ini_get('upload_max_filesize'));

		return [
			'title' => tr('Create tabular format and tracker from file'),
			'types' => $this->getSupportedTabularFieldTypes(),
			'config' => [
				'import_update' => 1,
				'ignore_blanks' => 0,
				'import_transaction' => 0,
				'bulk_import' => 0,
				'upload_max_filesize' => $uploadMaxFileSize
			],
		];
	}

	private function getSchema(array $info)
	{
		$tracker = \Tracker_Definition::get($info['trackerId']);

		if (! $tracker) {
			throw new Services_Exception_NotFound;
		}

		$schema = new \Tracker\Tabular\Schema($tracker);
		$schema->loadFormatDescriptor($info['format_descriptor']);
		$schema->loadFilterDescriptor($info['filter_descriptor']);
		$schema->loadConfig($info['config']);

		return $schema;
	}

	/**
	 * Get the list of supported field types by tabular
	 * Info: Item Link and List are removed due to missing links on csv upload
	 *
	 * @return mixed
	 */
	private function getSupportedTabularFieldTypes()
	{
		$trackerUtilities = new Services_Tracker_Utilities();
		$types = $trackerUtilities->getFieldTypes();

		unset($types['A']); // Attachment (deprecated)
		unset($types['w']); // Dynamic Items List
		unset($types['g']); // Group Selector
		unset($types['h']); // Header
		unset($types['icon']); // Icon
		unset($types['LANG']); // Language
		unset($types['G']); // Location
		unset($types['k']); // Page Selector
		unset($types['REL']); // Relations
		unset($types['S']); // Static Text
		unset($types['r']); // Item Link
		unset($types['l']); // Items List

		return $types;
	}

	/**
	 * Get the default mode for a given field type to use in Tabular display fields
	 *
	 * @param string $fieldType Field type
	 * @return string The default mode to display
	 */
	private function getFieldTypeDefaultMode($fieldType)
	{

		switch ($fieldType) {
			case 'c': // Checkbox
				$mode  = 'y/n';
				break;
			case 'e': // Category
				$mode = 'id';
				break;
			case 'd': // Dropdown
			case 'D': // Dropdown + Other
			case 'R': // Radio Buttons
			case 'M': // MultiSelect
			case 'y': // Country Selector
				$mode = 'code';
				break;
			case 'f': // Datetime
			case 'j': // Datetime + Picker
				$mode = 'unix';
				break;
			case 'u': // User Selector
				$mode = 'username';
				break;
			default:
				$mode = 'default';
		}

		return $mode;
	}
}
