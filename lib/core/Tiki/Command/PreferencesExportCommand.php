<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
/*
 * Command work like:
 * php console.php preference:export filename.csv for all preferences
 * php console.php preference:export filename.csv --fields=pref1,pref2 for specific preferences
 *
 * php console.php preference:export filename --wiki=1 for all preferences in wiki syntax
 * php console.php preference:export filename.csv --fields=pref1,pref2 --wiki=1 for specific preferences in wiki syntax
 */

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

class PreferencesExportCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('preferences:export')
			->setDescription('Export preferences')
			->addArgument(
				'filename',
				InputArgument::REQUIRED,
				'File to export preferences'
			)
			->addOption(
				'fields',
				null,
				InputOption::VALUE_OPTIONAL,
				'Preferences fields to export'
			)
			->addOption(
				'wiki',
				null,
				InputOption::VALUE_OPTIONAL,
				'Option to specify if export will be in wiki syntax'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$filename = $input->getArgument('filename');

		$output->writeln("Exporting preferences...");

		$defaultValues = get_default_prefs();

		$inputfields = $input->getOption('fields');
		$wikiexport = $input->getOption('wiki');

		if(isset($inputfields))
			$inputfields = explode(",", $inputfields);

		$fields = [
			'preference' => '',
			'hard_to_search' => false,
			'duplicate_name' => 0,
			'duplicate_description' => 0,
			'word_count' => 0,
			'filter' => '',
			'name' => '',
			'help' => '',
			'default' => '',
			'description' => '',
			'locations' => '',
			'dependencies' => '',
			'type' => '',
			'options' => '',
			'admin' => '',
			'module' => '',
			'view' => '',
			'permission' => '',
			'plugin' => '',
			'extensions' => '',
			'tags' => '',
			'parameters' => '',
			'detail' => '',
			'warning' => '',
			'hint' => '',
			'shorthint' => '',
			'perspective' => '',
			'separator' => '',
		];

		$stopWords = ['', 'in', 'and', 'a', 'to', 'be', 'of', 'on', 'the', 'for', 'as', 'it', 'or', 'with', 'by', 'is', 'an'];

		$data = [];
		error_reporting(E_ALL);
		ini_set('display_errors', 'on');

		$data = $this->collect_raw_data($fields);
		$this->remove_fake_descriptions($data);
		$this->set_default_values($data, $defaultValues);
		$this->collect_locations($data);
		$index = [
			'name' => $this->index_data($data, 'name'),
			'description' => $this->index_data($data, 'description'),
		];
		$this-> update_search_flag($data, $index, $stopWords);

		$export_file = fopen($filename, 'w');

		//error on opening file
		if($export_file == null){
			die();
		}

		//export only specifics fields
		if(isset($inputfields)) {
			$fields_keys = array_keys($fields);
			foreach ($fields_keys as $key) {
				if (!in_array($key, $inputfields)) {
					unset($fields[$key]);
				}
			}
		}

		if(!isset($wikiexport))
			fputcsv($export_file, array_keys($fields), ";");
		foreach ($data as $datakey => $values) {

			//export only values of input fields
			if(isset($inputfields)) {
				foreach ($values as $valuekey => $value) {
					if (!in_array($valuekey, $inputfields)) {
						unset($values[$valuekey]);
						unset($data[$datakey][$valuekey]);
					}
				}
			}

			if(!isset($wikiexport))
				fputcsv($export_file, array_values($values),";");
		}

		if(isset($wikiexport)) {
			$filename .= ".tiki";
			$this->export_wiki($filename, array_keys($fields), $data);
		}

		$output->writeln(sprintf("Preferences exported in %s",$filename));
	}

	/**
	 * @param $export_file
	 * @param $fields
	 * @param $data
	 */
	function export_wiki($export_file,$fields,$data)
	{
		$header_fields = implode("|",$fields);
		$header = "{FANCYTABLE(head=\"". $header_fields . "\")}";
		$body = "";
		$footer = "{FANCYTABLE}";

		foreach ($data as $values) {
			$values = array_values($values);
			$body .= implode("|",$values) . "\n";
		}

		$content = $header . "\n" . $body . "\n" . $footer;
		file_put_contents($export_file,$content);
	}

	/**
	 * @param $fields
	 * @return array
	 */
	function collect_raw_data($fields)
	{
		$data = [];

		foreach (glob('lib/prefs/*.php') as $file) {
			$name = substr(basename($file), 0, -4);
			$function = "prefs_{$name}_list";

			if ($name == 'index') {
				continue;
			}

			include $file;
			$list = $function();

			foreach ($list as $name => $raw) {
				$entry = $fields;

				$entry['preference'] = $name;
				$entry['name'] = isset($raw['name']) ? $raw['name'] : '';
				$entry['description'] = isset($raw['description']) ? $raw['description'] : '';
				$entry['filter'] = isset($raw['filter']) ? $raw['filter'] : '';
				$entry['help'] = isset($raw['help']) ? $raw['help'] : '';
				$entry['dependencies'] = ! empty($raw['dependencies']) ? implode(',', (array) $raw['dependencies']) : '';
				$entry['type'] = isset($raw['type']) ? $raw['type'] : '';
				$entry['options'] = isset($raw['options']) ? implode(',', $raw['options']) : '';
				$entry['admin'] = isset($raw['admin']) ? $raw['admin'] : '';
				$entry['module'] = isset($raw['module']) ? $raw['module'] : '';
				$entry['view'] = isset($raw['view']) ? $raw['view'] : '';
				$entry['permission'] = isset($raw['permission']) ? implode(',', $raw['permission']) : '';
				$entry['plugin'] = isset($raw['plugin']) ? $raw['plugin'] : '';
				$entry['extensions'] = isset($raw['extensions']) ? implode(',', $raw['extensions']) : '';
				$entry['tags'] = isset($raw['tags']) ? implode(',', $raw['tags']) : '';
				$entry['parameters'] = isset($raw['parameters']) ? implode(',', $raw['parameters']) : '';
				$entry['detail'] = isset($raw['detail']) ? $raw['detail'] : '';
				$entry['warning'] = isset($raw['warning']) ? $raw['warning'] : '';
				$entry['hint'] = isset($raw['hint']) ? $raw['hint'] : '';
				$entry['shorthint'] = isset($raw['shorthint']) ? $raw['shorthint'] : '';
				$entry['perspective'] = isset($raw['perspective']) ? $raw['perspective'] ? 'true' : 'false' : '';
				$entry['separator'] = isset($raw['separator']) ? $raw['separator'] : '';
				$data[] = $entry;
			}
		}

		return $data;
	}

	/**
	 * @param $data
	 */
	function remove_fake_descriptions(& $data)
	{
		foreach ($data as & $row) {
			if ($row['name'] == $row['description']) {
				$row['description'] = '';
			}
		}
	}

	/**
	 * @param $data
	 * @param $prefs
	 */
	function set_default_values(& $data, $prefs)
	{
		foreach ($data as & $row) {
			$row['default'] = isset($prefs[$row['preference']]) ? $prefs[$row['preference']] : '';

			if (is_array($row['default'])) {
				$row['default'] = implode($row['separator'], $row['default']);
			}
		}
	}

	/**
	 * @param $data
	 * @param $field
	 * @return array
	 */
	function index_data($data, $field)
	{
		$index = [];

		foreach ($data as $row) {
			$value = strtolower($row[$field]);

			if (! isset($index[$value])) {
				$index[$value] = 0;
			}

			$index[$value]++;
		}

		return $index;
	}

	/**
	 * @param $data
	 */
	function collect_locations(& $data)
	{
		$prefslib = TikiLib::lib('prefs');

		foreach ($data as & $row) {
			$pages = $prefslib->getPreferenceLocations($row['preference']);
			foreach ($pages as & $page) {
				$page = $page[0] . '/' . $page[1];
			}
			$row['locations'] = implode(', ', $pages);
		}
	}

	/**
	 * @param $data
	 * @param $index
	 * @param $stopWords
	 */
	function update_search_flag(& $data, $index, $stopWords)
	{
		foreach ($data as & $row) {
			$name = strtolower($row['name']);
			$description = strtolower($row['description']);

			$words = array_diff(explode(' ', $name . ' ' . $description), $stopWords);

			$row['duplicate_name'] = $index['name'][$name];
			if (! empty($description)) {
				$row['duplicate_description'] = $index['description'][$description];
			}
			$row['word_count'] = count($words);

			if (count($words) < 5) {
				$row['hard_to_search'] = 'X';
			} elseif ($index['name'][$name] > 2) {
				$row['hard_to_search'] = 'X';
			} elseif ($index['description'][$description] > 2) {
				$row['hard_to_search'] = 'X';
			}
		}
	}
}
