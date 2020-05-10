<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_BulkOperation
{
	private $count = 0;
	private $limit;
	private $callback;
	private $mapping_type;
	private $buffer = '';

	function __construct($limit, $callback, $mapping_type)
	{
		$this->limit = max(10, (int) $limit);
		$this->callback = $callback;
		$this->mapping_type = $mapping_type;
	}

	function flush()
	{
		if ($this->count > 0) {
			$callback = $this->callback;
			$callback($this->buffer);

			$this->buffer = '';
			$this->count = 0;
		}
	}

	function index($index, $type, $id, array $data)
	{
		$this->append(
			[
				['index' => $this->formatIndexId($index, $type, $id)],
				$data,
			]
		);
	}

	function unindex($index, $type, $id)
	{
		$this->append(
			[
				['delete' => $this->formatIndexId($index, $type, $id)],
			]
		);
	}

	private function append($lines)
	{
		$this->count += 1;
		foreach ($lines as $line) {
			$json = json_encode($line);
			if ($json) {
				$this->buffer .= $json . "\n";
			} else {
				if (isset($line['object_id'])) {
					$id = $line['object_type'] . '-' . $line['object_id'];
				} else {
					$id = 'unknown';
				}
				$message = tr('Failed to bulk index "%0" (%1)', $id, json_last_error_msg());
				trigger_error($message);
				Feedback::warning($message);
				$this->buffer .= "{}\n";	// avoid "failed to parse, document is empty" exception in es7
			}
		}

		if ($this->count >= $this->limit) {
			$this->flush();
		}
	}

	private function formatIndexId($index, $type, $id)
	{
		$index = [
			'_index' => $index,
			'_id' => $type.'-'.$id,
		];
		if (! empty($this->mapping_type)) {
			$index['_type'] = $this->mapping_type;
		}
		return $index;
	}
}
