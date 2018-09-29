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
				['index' => [
						'_index' => $index,
						'_type' => $this->mapping_type,
						'_id' => $type.'-'.$id,
					]
				],
				$data,
			]
		);
	}

	function unindex($index, $type, $id)
	{
		$this->append(
			[
				['delete' => [
						'_index' => $index,
						'_type' => $this->mapping_type,
						'_id' => $type.'-'.$id,
					]
				],
			]
		);
	}

	private function append($lines)
	{
		$this->count += 1;
		foreach ($lines as $line) {
			$this->buffer .= json_encode($line) . "\n";
		}

		if ($this->count >= $this->limit) {
			$this->flush();
		}
	}
}
