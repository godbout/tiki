<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Writer;

class TrackerWriter
{
	function sendHeaders()
	{
	}

	function write(\Tracker\Tabular\Source\SourceInterface $source)
	{
		$utilities = new \Services_Tracker_Utilities;
		$schema = $source->getSchema();
		$bulkImport = $schema->useBulkImport();

		if ($bulkImport) {
			global $prefs;
			$prefs['categories_cache_refresh_on_object_cat'] = 'n';
		}

		$iterate = function ($callback) use ($source, $schema, $bulkImport) {
			$columns = $schema->getColumns();

			$tx = \TikiDb::get()->begin();

			$lookup = $this->getItemIdLookup($schema);

			$result = [];

			/** @var \Tracker\Tabular\Source\CsvSourceEntry $entry */
			foreach ($source->getEntries() as $line => $entry) {
				$info = [
					'itemId' => false,
					'fields' => [],
				];

				foreach ($columns as $column) {
					$entry->parseInto($info, $column);
				}

				$info['itemId'] = $lookup($info);

				if (! $schema->canImportUpdate() && $info['itemId']) {
					continue;
				}

				if ($schema->ignoreImportBlanks()) {
					$info['fields'] = array_filter($info['fields']);
				}

				if ($bulkImport) {
					$info['bulk_import'] = true;
				}

				$result[] = $callback($line, $info, $columns);
			}

			$tx->commit();

			if (! $result) {
				return $result;
			}

			return call_user_func_array('array_merge', $result);
		};

		if ($schema->isImportTransaction()) {
			$errors = $iterate(function ($line, $info, $columns) use ($utilities, $schema) {
				static $ids = [];
				if (! empty($info['itemId']) && in_array($info['itemId'], $ids)) {
					return [tr('Line %0:', $line + 1) . ' ' . tr('duplicate entry')];
				}
				foreach ($columns as $column) {
					if ($column->isUniqueKey()) {
						$table = \TikiDb::get()->table('tiki_tracker_item_fields');
						$definition = $schema->getDefinition();
						$f = $definition->getFieldFromPermName($column->getField());
						$fieldId = $f['fieldId'];
						$exists = $table->fetchOne('itemId', [
							'fieldId' => $fieldId,
							'value' => $info['fields'][$column->getField()],
						]);
						if ($exists) {
							return [tr('Line %0:', $line + 1) . ' ' . tr('duplicate entry for unique column %0', $column->getLabel())];
						}
					}
				}
				$ids[] = $info['itemId'];
				return array_map(
					function ($error) use ($line) {
						return tr('Line %0:', $line + 1) . ' ' . $error;
					},
					$utilities->validateItem($schema->getDefinition(), $info)
				);
			});

			if (count($errors) > 0) {
				\Feedback::error([
					'title' => tr('Import file contains errors. Please review and fix before importing.'),
					'mes' => $errors
				]);
				return false;
			}
		}

		$definition = $schema->getDefinition();
		$defaultStatus = $definition->getConfiguration('newItemStatus');

		$iterate(function ($line, $info, $columns) use ($utilities, $definition, $defaultStatus) {
			if (empty($info['status'])) {
				$info['status'] = $defaultStatus;
			}
			if ($info['itemId']) {
				$success = $utilities->updateItem($definition, $info);
			} else {
				$success = $utilities->insertItem($definition, $info);
			}
			if (! empty($info['postprocess'])) {
				foreach ((array) $info['postprocess'] as $postprocess) {
					if (is_callable($postprocess)) {
						$postprocess($success);
					}
				}
			}
			return [$success];
		});

		return true;
	}

	private function getItemIdLookup($schema)
	{
		$pk = $schema->getPrimaryKey();
		if (! $pk) {
			throw new \Exception(tr('Primary Key not defined'));
		}

		$pkField = $pk->getField();

		if ($pkField == 'itemId') {
			return function ($info) {
				return $info['itemId'];
			};
		} else {
			$table = \TikiDb::get()->table('tiki_tracker_item_fields');
			$definition = $schema->getDefinition();
			$f = $definition->getFieldFromPermName($pkField);
			$fieldId = $f['fieldId'];

			return function ($info) use ($table, $pkField, $fieldId) {
				return $table->fetchOne('itemId', [
					'fieldId' => $fieldId,
					'value' => $info['fields'][$pkField],
				]);
			};
		}
	}
}
