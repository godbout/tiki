<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_EngineResult_Elastic implements Search_EngineResult_Interface
{
	private $index = null;

	public function __construct(Search_Elastic_Index $index)
	{
		$this->index = $index;
	}

	/**
	 * Count the amount of fields used by the elastic search engine
	 * @return int
	 */
	public function getEngineFieldsCount()
	{
		$engineFieldsCount = 0;
		$fieldMappings = $this->index->getFieldMappings();

		foreach ($fieldMappings as $unique_item_type) {
			if (empty($unique_item_type)) {
				continue;
			}

			foreach ($unique_item_type as $item) {
				if (! isset($item['fields'])) {
					continue;
				}

				$engineFieldsCount += count($item['fields']);
			}
		}

		return $engineFieldsCount;
	}
}
